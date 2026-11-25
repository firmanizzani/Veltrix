<?php

namespace App\Jobs;

use App\Models\AiMessage;
use App\Models\User;
use App\Services\BusinessAnalyticsTool;
use App\Services\BusinessAssistant;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class AnswerBusinessQuestion implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;
    public int $timeout = 150;

    public function __construct(public int $messageId) {}

    public function handle(): void
    {
        $message = AiMessage::findOrFail($this->messageId);
        if ($message->status !== 'pending') {
            return;
        }
        $thread = $message->thread;
        $user = User::find($thread->user_id);
        if (
            !$user ||
            !$user->is_active ||
            !$user->isOwner() ||
            !config('intelligence.enabled') ||
            !config('ai.providers.gemini.key')
        ) {
            $this->failed();
            return;
        }
        try {
            $tool = new BusinessAnalyticsTool(
                $user,
                $thread->start,
                $thread->end,
                $thread->branch_id,
            );
            $snapshot = $tool->read(['metric' => 'summary']);
            $history = $thread
                ->messages()
                ->where('id', '<', $message->id)
                ->where('status', 'completed')
                ->latest('id')
                ->limit(13)
                ->get()
                ->reverse()
                ->values();
            $question = $history->pop();
            $agent = new BusinessAssistant(
                $tool,
                $history
                    ->map(fn($row) => ['role' => $row->role, 'content' => $row->content])
                    ->all(),
                $snapshot,
            );
            $response = $agent->prompt(
                $question->content,
                provider: 'gemini',
                model: config('intelligence.model'),
                timeout: config('intelligence.timeout'),
            );
            if (trim($response->text) === '') {
                $this->failed();
                return;
            }
            $message->fill([
                'status' => 'completed',
                'content' => $response->text,
                'evidence' => $tool->evidence,
                'input_tokens' => $response->usage->inputTokens,
                'output_tokens' => $response->usage->outputTokens,
                'model' => config('intelligence.model'),
            ]);
            AiMessage::whereKey($message->id)
                ->where('status', 'pending')
                ->update($message->getDirty());
            $thread->touch();
        } catch (Throwable $exception) {
            Log::warning('Business assistant request failed.', [
                'message_id' => $this->messageId,
                'exception_type' => $exception::class,
            ]);
            $this->failed();
        }
    }

    public function failed(?Throwable $exception = null): void
    {
        AiMessage::whereKey($this->messageId)
            ->where('status', 'pending')
            ->update([
                'status' => 'failed',
                'content' =>
                    'Jawaban belum tersedia. Layanan AI tidak dapat menyelesaikan permintaan. Silakan coba lagi; data bisnis tidak berubah.',
            ]);
    }
}

<?php

namespace App\Jobs;

use App\Enums\Role;
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
        $scope = $message->scope ?? $thread->only('start', 'end', 'branch_id');
        $user = User::find($thread->user_id);
        if (
            !$user ||
            !$user->is_active ||
            (!$user->isOwner() &&
                ($user->role !== Role::Manager ||
                    $user->branch_id === null ||
                    $scope['branch_id'] !== $user->branch_id ||
                    ($scope['audience'] ?? null) !== 'manager')) ||
            !config('intelligence.enabled') ||
            !config('ai.providers.gemini.key')
        ) {
            $this->failed();
            return;
        }
        try {
            $history = $thread
                ->messages()
                ->where('id', '<', $message->id)
                ->where('status', 'completed')
                ->when(
                    !$user->isOwner(),
                    fn($query) => $query
                        ->where('scope->audience', 'manager')
                        ->whereJsonContains('scope->branch_id', $user->branch_id),
                )
                ->latest('id')
                ->limit(13)
                ->get()
                ->reverse()
                ->values();
            $question = $history->pop();
            $periodPattern =
                '/\b(?:hari|sehari|minggu|seminggu|pekan|bulan|sebulan|tahun|setahun|' .
                'kemarin|senin|selasa|rabu|kamis|jumat|sabtu|januari|februari|maret|april|mei|' .
                'juni|juli|agustus|september|oktober|november|desember|jan|feb|mar|apr|jun|jul|' .
                'agu|sep|okt|nov|des|today|yesterday|week|month|year)\b|' .
                '\d{4}-\d{2}-\d{2}|\b\d{1,2}[\/\-]\d{1,2}\b|\btanggal\s+\d{1,2}\b/iu';
            $explicitPeriod = preg_match($periodPattern, $question->content) === 1;
            $tool = new BusinessAnalyticsTool(
                $user,
                $scope['start'],
                $scope['end'],
                $scope['branch_id'],
                lockPeriod: !$explicitPeriod,
            );
            $snapshot = $tool->read(['metric' => 'summary']);
            $agent = new BusinessAssistant(
                $tool,
                $history
                    ->map(
                        fn($row) => [
                            'role' => $row->role,
                            'content' => $row->content,
                            'scope' => $row->scope,
                        ],
                    )
                    ->all(),
                $snapshot,
            );
            $response = $agent->prompt(
                'Cakupan pilihan untuk pertanyaan INI: ' .
                    json_encode($scope, JSON_THROW_ON_ERROR) .
                    '. Gunakan cakupan ini kecuali pertanyaan pengguna secara eksplisit menyebut tanggal atau periode lain.' .
                    "\nPertanyaan pengguna: " .
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

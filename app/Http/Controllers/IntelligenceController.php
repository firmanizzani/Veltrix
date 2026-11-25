<?php

namespace App\Http\Controllers;

use App\Jobs\AnswerBusinessQuestion;
use App\Jobs\GenerateBusinessInsights;
use App\Models\AiMessage;
use App\Models\AiThread;
use App\Models\Branch;
use App\Models\BusinessInsight;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class IntelligenceController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorizeOwner($request);
        $thread = $request->filled('thread')
            ? AiThread::where('user_id', $request->user()->id)->findOrFail(
                $request->integer('thread'),
            )
            : null;
        if ($thread) {
            $thread
                ->messages()
                ->where('status', 'pending')
                ->where('created_at', '<', now()->subMinutes(5))
                ->update([
                    'status' => 'failed',
                    'content' =>
                        'Pemrosesan melewati batas waktu. Coba kirim kembali pertanyaan Anda.',
                ]);
        }
        $branchId =
            $request->validate(['branch_id' => 'nullable|integer|exists:branches,id'])[
                'branch_id'
            ] ?? null;
        return Inertia::render('Intelligence', [
            'available' => $this->available(),
            'dailyLimit' => config('intelligence.daily_limit'),
            'usedToday' => $this->usage($request->user()->id),
            'threads' => fn() => AiThread::where('user_id', $request->user()->id)
                ->latest('updated_at')
                ->limit(20)
                ->get(),
            'conversation' => $thread
                ? [
                    ...$thread->toArray(),
                    'messages' => $thread
                        ->messages()
                        ->latest('id')
                        ->limit(40)
                        ->get()
                        ->reverse()
                        ->values(),
                ]
                : null,
            'branches' => fn() => Branch::select('id', 'name')->orderBy('name')->get(),
            'insights' => fn() => BusinessInsight::query()
                ->when($branchId, fn($query) => $query->where('branch_id', $branchId))
                ->orderByDesc('end')
                ->orderByDesc('id')
                ->paginate(12)
                ->withQueryString(),
            'filters' => ['branch_id' => $branchId ?? ''],
            'tab' => $request->query('tab') === 'insights' ? 'insights' : 'assistant',
        ]);
    }

    public function message(Request $request): RedirectResponse
    {
        $this->authorizeOwner($request);
        if (!$this->available()) {
            throw ValidationException::withMessages([
                'message' =>
                    'Gemini belum dikonfigurasi. Insight berbasis aturan tetap tersedia.',
            ]);
        }
        $data = $request->validate([
            'message' => 'required|string|max:2000',
            'thread_id' => 'nullable|integer',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'start' => 'required|date_format:Y-m-d|before_or_equal:today',
            'end' =>
                'required|date_format:Y-m-d|after_or_equal:start|before_or_equal:today',
        ]);
        if (CarbonImmutable::parse($data['start'])->diffInDays($data['end']) > 89) {
            throw ValidationException::withMessages([
                'start' => 'Pilih rentang maksimal 90 hari.',
            ]);
        }
        [$thread, $answer] = DB::transaction(function () use ($request, $data) {
            User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            if (
                $this->usage($request->user()->id) >= config('intelligence.daily_limit')
            ) {
                throw ValidationException::withMessages([
                    'message' => 'Batas pertanyaan harian tercapai. Coba lagi besok.',
                ]);
            }
            $thread = !empty($data['thread_id'])
                ? AiThread::where('user_id', $request->user()->id)->findOrFail(
                    $data['thread_id'],
                )
                : AiThread::create([
                    'user_id' => $request->user()->id,
                    'branch_id' => $data['branch_id'] ?? null,
                    'start' => $data['start'],
                    'end' => $data['end'],
                    'title' => Str::limit(trim($data['message']), 100),
                ]);
            if ($thread->messages()->where('status', 'pending')->exists()) {
                throw ValidationException::withMessages([
                    'message' => 'Tunggu jawaban sebelumnya selesai.',
                ]);
            }
            $scope = [
                'start' => $data['start'],
                'end' => $data['end'],
                'branch_id' => $data['branch_id'] ?? null,
                'branch' => !empty($data['branch_id'])
                    ? Branch::findOrFail($data['branch_id'])->name
                    : 'Seluruh cabang',
            ];
            $thread->update(collect($scope)->except('branch')->all());
            $thread->messages()->create([
                'role' => 'user',
                'content' => trim($data['message']),
                'scope' => $scope,
            ]);
            $answer = $thread->messages()->create([
                'role' => 'assistant',
                'status' => 'pending',
                'scope' => $scope,
            ]);
            $thread->touch();
            return [$thread, $answer];
        });
        AnswerBusinessQuestion::dispatch($answer->id)->onConnection('database');
        return redirect('/intelligence?thread=' . $thread->id);
    }

    public function generate(Request $request): RedirectResponse
    {
        $this->authorizeOwner($request);
        GenerateBusinessInsights::dispatch()->onConnection('database');
        return redirect('/intelligence?tab=insights')->with(
            'success',
            'Analisis dijadwalkan. Muat ulang setelah proses selesai.',
        );
    }

    public function status(Request $request, BusinessInsight $insight): RedirectResponse
    {
        $this->authorizeOwner($request);
        $insight->update(
            $request->validate(['status' => 'required|in:new,read,dismissed']),
        );
        return back();
    }

    public function explain(Request $request, BusinessInsight $insight): RedirectResponse
    {
        $this->authorizeOwner($request);
        $request->merge([
            'thread_id' => null,
            'branch_id' => $insight->branch_id,
            'start' => $insight->start,
            'end' => $insight->end,
            'message' =>
                'Jelaskan insight otomatis "' .
                $insight->title .
                '". ' .
                'Bandingkan data pendukung dan sarankan langkah pemeriksaan ' .
                'tanpa menganggap penyebabnya pasti.',
        ]);
        return $this->message($request);
    }

    private function authorizeOwner(Request $request): void
    {
        abort_unless($request->user()->is_active && $request->user()->isOwner(), 403);
    }

    private function available(): bool
    {
        return (bool) config('intelligence.enabled') &&
            filled(config('ai.providers.gemini.key'));
    }

    private function usage(int $userId): int
    {
        return AiMessage::where('role', 'assistant')
            ->where('created_at', '>=', now()->startOfDay())
            ->whereHas('thread', fn($query) => $query->where('user_id', $userId))
            ->count();
    }
}

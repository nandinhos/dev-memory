<?php

namespace Tests\Feature;

use App\Enums\CaptureStatus;
use App\Jobs\CurateCaptureJob;
use App\Livewire\Admin\CapturesInbox;
use App\Models\Capture;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class Fase2RobustnessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    private function makeFailed(int $n): void
    {
        for ($i = 0; $i < $n; $i++) {
            Capture::create([
                'source_system' => 'test',
                'raw_content' => "conteudo {$i}",
                'sanitized_content' => "conteudo {$i}",
                'idempotency_key' => "k{$i}",
                'status' => CaptureStatus::FAILED,
            ]);
        }
    }

    public function test_failed_captures_are_shown_and_retriable(): void
    {
        Queue::fake();
        $this->makeFailed(3);

        Livewire::test(CapturesInbox::class)
            ->assertViewHas('failedCount', 3)
            ->assertSee('falharam a curadoria')
            ->call('retryFailed')
            ->assertDispatched('show-toast');

        // Resetadas para SANITIZED e re-enfileiradas (raw_content preservado).
        $this->assertSame(0, Capture::where('status', CaptureStatus::FAILED)->count());
        $this->assertSame(3, Capture::where('status', CaptureStatus::SANITIZED)->count());
        Queue::assertPushed(CurateCaptureJob::class, 3);
    }

    public function test_process_captures_retry_failed_flag_recovers(): void
    {
        Queue::fake();
        $this->makeFailed(2);

        // Sem a flag: não toca em FAILED.
        $this->artisan('memory:process-captures')->assertSuccessful();
        $this->assertSame(2, Capture::where('status', CaptureStatus::FAILED)->count());

        // Com a flag: recupera.
        $this->artisan('memory:process-captures --retry-failed')->assertSuccessful();
        $this->assertSame(0, Capture::where('status', CaptureStatus::FAILED)->count());
        Queue::assertPushed(CurateCaptureJob::class, 2);
    }

    public function test_recovery_command_is_scheduled(): void
    {
        $schedule = app(Schedule::class);

        $found = collect($schedule->events())->contains(
            fn ($e) => str_contains($e->command ?? '', 'memory:process-captures')
                && str_contains($e->command ?? '', '--retry-failed')
        );

        $this->assertTrue($found, 'O comando de recuperação deve estar agendado.');
    }
}

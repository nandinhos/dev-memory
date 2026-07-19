<?php

namespace App\Livewire\Admin;

use App\Enums\CaptureStatus;
use App\Jobs\CurateCaptureJob;
use App\Models\Capture;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Capturas')]
class CapturesInbox extends Component
{
    use WithPagination;

    public ?string $expandedId = null;

    public function toggle(string $id): void
    {
        $this->expandedId = $this->expandedId === $id ? null : $id;
    }

    /**
     * Reenfileira as captures que falharam a curadoria (motor fora do ar ou
     * timeout): reseta para SANITIZED e despacha de novo. O raw_content é
     * preservado, então nada se perde.
     */
    public function retryFailed(): void
    {
        $failed = Capture::where('status', CaptureStatus::FAILED)->get();

        foreach ($failed as $capture) {
            $capture->update(['status' => CaptureStatus::SANITIZED]);
            CurateCaptureJob::dispatch($capture);
        }

        $this->dispatch('show-toast', message: "{$failed->count()} captura(s) reenfileirada(s) para curadoria", type: 'aviso');
    }

    public function render()
    {
        return view('livewire.admin.captures-inbox', [
            'captures' => Capture::with('memory:id,title')
                ->latest()
                ->paginate(15),
            'failedCount' => Capture::where('status', CaptureStatus::FAILED)->count(),
        ]);
    }
}

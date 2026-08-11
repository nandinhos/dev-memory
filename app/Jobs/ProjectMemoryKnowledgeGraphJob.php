<?php

namespace App\Jobs;

use App\Models\Memory;
use App\Services\KnowledgeGraph\KnowledgeGraphProjector;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProjectMemoryKnowledgeGraphJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(
        public string $memoryId,
    ) {}

    public function handle(KnowledgeGraphProjector $projector): void
    {
        $memory = Memory::find($this->memoryId);

        if ($memory === null) {
            return;
        }

        $projector->projectMemory($memory);
    }
}

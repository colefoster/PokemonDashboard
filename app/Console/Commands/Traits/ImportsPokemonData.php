<?php

namespace App\Console\Commands\Traits;

use App\Services\ParallelImporter;
use App\Services\PokeApiService;
use App\Services\PokemonImportProgressService;

trait ImportsPokemonData
{
    protected PokeApiService $api;
    protected int $delay;
    protected int $successCount = 0;
    protected int $errorCount = 0;
    protected bool $isWorker = false;
    protected ?int $workerId = null;
    protected ?PokemonImportProgressService $progressService = null;

    protected function initializeImporter(int $delay): void
    {
        $this->api = app(PokeApiService::class);
        $this->delay = $delay;
        $this->successCount = 0;
        $this->errorCount = 0;
        $this->isWorker = $this->option('worker-id') !== null;
        $this->workerId = $this->option('worker-id') ? (int) $this->option('worker-id') : null;
    }

    protected function applyDelay(): void
    {
        usleep($this->delay * 1000);
    }

    protected function recordSuccess(): void
    {
        $this->successCount++;
    }

    protected function recordError(): void
    {
        $this->errorCount++;
    }

    protected function showStats(string $entityName, int $totalCount): void
    {
        $this->newLine();
        $prefix = $this->isWorker ? "[Worker {$this->workerId}] " : "";
        $this->info("{$prefix}{$entityName} imported: {$totalCount}");
        $this->info("{$prefix}Success: {$this->successCount} | Errors: {$this->errorCount}");
    }

    protected function shouldRunParallel(): bool
    {
        $threads = (int) $this->option('threads');
        return $threads > 1 && !$this->isWorker;
    }

    protected function runInParallel(string $endpoint, string $commandName, array $additionalOptions = []): int
    {
        $threads = (int) $this->option('threads');
        $limit = (int) $this->option('limit');

        $totalCount = ParallelImporter::getEstimatedCount($endpoint, $this->api);

        $parallelImporter = new ParallelImporter($this);
        $success = $parallelImporter->runParallel(
            $commandName,
            $totalCount,
            $threads,
            $limit,
            $additionalOptions
        );

        return $success ? self::SUCCESS : self::FAILURE;
    }

    protected function getStartOffset(): int
    {
        return $this->option('offset') ? (int) $this->option('offset') : 0;
    }

    protected function getMaxItems(): ?int
    {
        return $this->option('max-items') ? (int) $this->option('max-items') : null;
    }

    protected function setProgressService(?PokemonImportProgressService $service): void
    {
        $this->progressService = $service;
    }

    protected function updateProgress(int $processed, ?int $total = null): void
    {
        if ($this->progressService && !$this->isWorker) {
            $this->progressService->updateStepProgress($processed, $total);
        }
    }

    protected function incrementProgress(int $amount = 1): void
    {
        if ($this->progressService && !$this->isWorker) {
            $this->progressService->incrementStepProgress($amount);
        }
    }
}

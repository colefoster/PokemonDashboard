<?php

namespace Database\Seeders\Traits;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

/**
 * Trait ParallelSeeder
 *
 * Provides parallel processing capabilities for Laravel Artisan Commands.
 * This trait should be used in Command classes that extend Illuminate\Console\Command.
 *
 * Requirements:
 * - The command must define --threads, --worker-id, --offset, and --max-items options
 * - The using class must have access to option(), info(), error(), newLine() methods
 *
 * @mixin Command
 */
trait ParallelSeeder
{
    protected int $threads = 1;
    protected bool $isWorker = false;
    protected ?int $workerId = null;
    protected int $offset = 0;
    protected ?int $maxItems = null;

    /**
     * Initialize parallel processing options from command input
     */
    protected function initializeParallelProcessing(): void
    {
        $this->threads = (int) ($this->option('threads') ?? 1);
        $this->isWorker = $this->option('worker-id') !== null;
        $this->workerId = $this->option('worker-id') ? (int) $this->option('worker-id') : null;
        $this->offset = (int) ($this->option('offset') ?? 0);
        $this->maxItems = $this->option('max-items') ? (int) $this->option('max-items') : null;
    }

    /**
     * Check if the command should run in parallel mode
     */
    protected function shouldRunInParallel(): bool
    {
        return $this->threads > 1 && !$this->isWorker;
    }

    /**
     * Execute the seeder/command in parallel using multiple worker processes
     *
     * @param string $seederClass The seeder class name
     * @param int $totalItems Total number of items to process
     * @param int $limit Items per page
     * @return bool True if all workers succeeded
     */
    protected function runInParallel(string $seederClass, int $totalItems, int $limit = 100): bool
    {
        $this->info("🚀 Starting parallel processing with {$this->threads} threads...");

        $itemsPerThread = (int) ceil($totalItems / $this->threads);
        $processes = [];

        // Spawn worker processes
        for ($i = 0; $i < $this->threads; $i++) {
            $offset = $i * $itemsPerThread;
            $maxItems = min($itemsPerThread, $totalItems - $offset);

            if ($maxItems <= 0) {
                break;
            }

            $command = [
                PHP_BINARY,
                'artisan',
                'db:seed',
                '--class=' . $seederClass,
                '--threads=1',
                '--worker-id=' . $i,
                '--offset=' . $offset,
                '--max-items=' . $maxItems,
            ];

            $process = new Process($command, base_path());
            $process->setTimeout(3600); // 1 hour timeout
            $process->start();

            $processes[] = [
                'id' => $i,
                'process' => $process,
                'offset' => $offset,
                'maxItems' => $maxItems,
            ];

            $this->info("[Worker {$i}] Started: offset={$offset}, items={$maxItems}");
        }

        // Wait for all processes to complete
        $this->newLine();
        $this->info('⏳ Waiting for workers to complete...');

        $allSuccessful = true;
        foreach ($processes as $workerData) {
            $process = $workerData['process'];
            $workerId = $workerData['id'];

            $process->wait(function ($type, $buffer) use ($workerId) {
                if (Process::ERR === $type) {
                    $this->error("[Worker {$workerId}] ERROR: " . $buffer);
                } else {
                    // Only show important output to reduce clutter
                    if (str_contains($buffer, 'imported:') || str_contains($buffer, 'Error')) {
                        $this->line("[Worker {$workerId}] " . trim($buffer));
                    }
                }
            });

            if (!$process->isSuccessful()) {
                $this->error("[Worker {$workerId}] ❌ Failed with exit code: " . $process->getExitCode());
                $allSuccessful = false;
            } else {
                $this->info("[Worker {$workerId}] ✅ Completed successfully");
            }
        }

        if ($allSuccessful) {
            $this->newLine();
            $this->info('✅ All workers completed successfully!');
        } else {
            $this->newLine();
            $this->error('❌ Some workers failed. Check the output above for details.');
        }

        return $allSuccessful;
    }

    /**
     * Get worker prefix for log messages
     */
    protected function getWorkerPrefix(): string
    {
        return $this->isWorker ? "[Worker {$this->workerId}] " : "";
    }

    // Abstract method declarations for IDE support
    abstract public function option($key = null);
    abstract public function info($string, $verbosity = null);
    abstract public function error($string, $verbosity = null);
    abstract public function newLine($count = 1);
    abstract public function line($string, $style = null, $verbosity = null);
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class SeederProgressService
{
    private const CACHE_KEY = 'seeder_progress';
    private const CACHE_TTL = 3600; // 1 hour

    public function start(string $step, int $total): void
    {
        $this->updateProgress([
            'current_step' => $step,
            'total' => $total,
            'current' => 0,
            'message' => "Starting {$step}...",
        ]);
    }

    public function advance(string $message = ''): void
    {
        $progress = $this->getProgress();
        $progress['current'] = ($progress['current'] ?? 0) + 1;

        if ($message) {
            $progress['message'] = $message;
        }

        $this->updateProgress($progress['progress'] ?? [], $progress['successCount'] ?? 0, $progress['errorCount'] ?? 0);
    }

    public function success(): void
    {
        $data = $this->getProgress();
        $data['successCount'] = ($data['successCount'] ?? 0) + 1;
        Cache::put(self::CACHE_KEY, $data, self::CACHE_TTL);
    }

    public function error(string $message = ''): void
    {
        $data = $this->getProgress();
        $data['errorCount'] = ($data['errorCount'] ?? 0) + 1;

        if ($message) {
            $data['progress']['message'] = $message;
        }

        Cache::put(self::CACHE_KEY, $data, self::CACHE_TTL);
    }

    public function updateProgress(array $progress, int $successCount = null, int $errorCount = null): void
    {
        $data = $this->getProgress();

        $data['progress'] = array_merge($data['progress'] ?? [], $progress);

        if ($successCount !== null) {
            $data['successCount'] = $successCount;
        }

        if ($errorCount !== null) {
            $data['errorCount'] = $errorCount;
        }

        Cache::put(self::CACHE_KEY, $data, self::CACHE_TTL);
    }

    public function complete(string $step): void
    {
        $data = $this->getProgress();
        $data['progress']['current_step'] = $step === 'all' ? 'complete' : $step;
        $data['progress']['message'] = $step === 'all' ? 'Import complete!' : "Completed {$step}";
        Cache::put(self::CACHE_KEY, $data, self::CACHE_TTL);
    }

    public function getProgress(): array
    {
        return Cache::get(self::CACHE_KEY, [
            'progress' => [
                'current_step' => 'start',
                'total' => 0,
                'current' => 0,
                'message' => 'Ready to import',
            ],
            'successCount' => 0,
            'errorCount' => 0,
        ]);
    }

    public function reset(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
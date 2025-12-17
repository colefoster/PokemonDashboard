<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PokeApiService
{
    private const BASE_URL = 'https://pokeapi.co/api/v2';

    public function fetch(string $endpoint): array
    {
        $url = str_starts_with($endpoint, 'http') ? $endpoint : self::BASE_URL . $endpoint;

        $response = Http::get($url);

        if ($response->failed()) {
            throw new \Exception("Failed to fetch from PokeAPI: {$url}");
        }

        return $response->json();
    }

    public function extractIdFromUrl(string $url): int
    {
        preg_match('/\/(\d+)\/$/', $url, $matches);

        if (!isset($matches[1])) {
            throw new \Exception("Could not extract ID from URL: {$url}");
        }

        return (int) $matches[1];
    }

    public function getEnglishEffect(array $entries): ?array
    {
        foreach ($entries as $entry) {
            if (isset($entry['language']['name']) && $entry['language']['name'] === 'en') {
                return $entry;
            }
        }

        return null;
    }

    public function getEnglishFlavorText(array $entries): ?array
    {
        foreach ($entries as $entry) {
            if (isset($entry['language']['name']) && $entry['language']['name'] === 'en') {
                return $entry;
            }
        }

        return null;
    }
}
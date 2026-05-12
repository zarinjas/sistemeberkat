<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class IpGeolocationService
{
    /**
     * @return array{country:?string,region:?string,city:?string,isp:?string,location_summary:?string}
     */
    public function locate(?string $ipAddress): array
    {
        if (! $this->isPublicIp($ipAddress)) {
            return $this->emptyResult();
        }

        $cacheKey = 'ip_geo:'.$ipAddress;

        return Cache::remember($cacheKey, now()->addHours(12), function () use ($ipAddress) {
            try {
                $response = Http::timeout(2)
                    ->acceptJson()
                    ->get("https://ipwho.is/{$ipAddress}");

                if (! $response->successful()) {
                    return $this->emptyResult();
                }

                $json = $response->json();

                if (! is_array($json) || ! ($json['success'] ?? false)) {
                    return $this->emptyResult();
                }

                $country = $this->normalize($json['country'] ?? null);
                $region = $this->normalize($json['region'] ?? null);
                $city = $this->normalize($json['city'] ?? null);
                $isp = $this->normalize(data_get($json, 'connection.isp'));

                $parts = array_values(array_filter([$city, $region, $country]));
                $locationSummary = $parts !== [] ? implode(', ', $parts) : null;

                return [
                    'country' => $country,
                    'region' => $region,
                    'city' => $city,
                    'isp' => $isp,
                    'location_summary' => $locationSummary,
                ];
            } catch (\Throwable) {
                return $this->emptyResult();
            }
        });
    }

    private function isPublicIp(?string $ipAddress): bool
    {
        if (! $ipAddress || ! filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            return false;
        }

        return (bool) filter_var(
            $ipAddress,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }

    /**
     * @return array{country:?string,region:?string,city:?string,isp:?string,location_summary:?string}
     */
    private function emptyResult(): array
    {
        return [
            'country' => null,
            'region' => null,
            'city' => null,
            'isp' => null,
            'location_summary' => null,
        ];
    }

    private function normalize(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $normalized = trim($value);

        return $normalized !== '' ? $normalized : null;
    }
}

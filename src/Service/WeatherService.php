<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

class WeatherService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $apiKey,
    ) {}

    public function getWeather(string $city): array
    {
        $response = $this->httpClient->request(
            'GET',
            'https://api.openweathermap.org/data/2.5/weather?q=' . urlencode($city) . ',FR&appid=' . $this->apiKey . '&units=metric&lang=fr'
        );

        $statusCode = $response->getStatusCode();

        if ($statusCode !== 200) {
            if ($statusCode === 404) {
                $status = JsonResponse::HTTP_NOT_FOUND;
                $message = 'La ville ' . $city . ' est introuvable.';
            } else {
                $status = JsonResponse::HTTP_BAD_GATEWAY;
                $message = 'La récupération des données météo a échoué.';
            }

            throw new HttpException($status, $message);
        }

        $content = $response->toArray();
        $clouds = isset($content['clouds']['all']) && $content['clouds']['all'] > 50 ? $content['clouds']['all'] : null;
        $rain = isset($content['rain']['1h']) && $content['rain']['1h'] > 0 ? $content['rain']['1h'] : null;

        $weather = [
            'city' => $content['name'] ?? $city,
            'description' => $content['weather'][0]['description'] ?? 'N/A',
            'clouds' => $clouds,
            'temperature' => [
                'min' => $content['main']['temp_min'] ?? null,
                'max' => $content['main']['temp_max'] ?? null,
            ],
            'wind' => [
                'speed' => $content['wind']['speed'] ?? null,
                'gust' => $content['wind']['gust'] ?? null,
            ],
            'humidity' => $content['main']['humidity'] ?? null,
            'rain_last_hour' => $rain,
        ];

        return $weather;    
    }
}

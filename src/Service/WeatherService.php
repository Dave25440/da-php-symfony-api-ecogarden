<?php

namespace App\Service;

use JMS\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class WeatherService
{
    public function __construct(
        private readonly string $apiKey,
        private readonly SerializerInterface $serializer,
    ) {}

    public function getWeather(HttpClientInterface $httpClient, string $city): JsonResponse
    {
        $response = $httpClient->request(
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

            return new JsonResponse(['error' => $message], $status);
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

        $jsonWeather = $this->serializer->serialize($weather, 'json');

        return new JsonResponse($jsonWeather, Response::HTTP_OK, [], true);    
    }
}

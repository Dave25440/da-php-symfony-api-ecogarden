<?php

namespace App\Controller;

use App\Service\WeatherService;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Attribute\Route;

final class WeatherController extends AbstractController
{
    public function __construct(
        private readonly SerializerInterface $serializer,
    ) {}

    #[Route('/api/weather', name: 'weather_index', methods: ['GET'])]
    public function index(WeatherService $weatherService): JsonResponse
    {
        $user = $this->getUser();

        if (!$user || !$user->getCity()) {
            throw new HttpException(Response::HTTP_BAD_REQUEST, 'La ville de votre compte est introuvable.');
        }

        $city = $user->getCity();
        $weather = $weatherService->getWeather($city);

        $jsonWeather = $this->serializer->serialize($weather, 'json');

        return new JsonResponse($jsonWeather, Response::HTTP_OK, [], true); 
    }

    #[Route('/api/weather/{city}', name: 'weather_show', methods: ['GET'], requirements: ['city' => '[a-zA-ZÀ-ÿ\' \-]+'])]
    public function show(WeatherService $weatherService, string $city): JsonResponse
    {
        $weather = $weatherService->getWeather($city);
        $jsonWeather = $this->serializer->serialize($weather, 'json');

        return new JsonResponse($jsonWeather, Response::HTTP_OK, [], true); 
    }
}

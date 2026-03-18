<?php

namespace App\Controller;

use App\Repository\TipRepository;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TipController extends AbstractController
{
    public function __construct(
        private readonly SerializerInterface $serializer,
    ) {}

    #[Route('/api/tips', name: 'tips_index', methods: ['GET'])]
    public function index(TipRepository $tipRepository): JsonResponse
    {
        $currentMonth = (int) date('n');
        $tips = $tipRepository->findByMonth($currentMonth);

        $context = SerializationContext::create()->setGroups(['tip_list']);
        $jsonTips = $this->serializer->serialize($tips, 'json', $context);

        return new JsonResponse($jsonTips, Response::HTTP_OK, [], true);
    }
}

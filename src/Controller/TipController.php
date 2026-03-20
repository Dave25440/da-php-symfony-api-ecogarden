<?php

namespace App\Controller;

use App\Entity\Tip;
use App\Repository\MonthRepository;
use App\Repository\TipRepository;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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

    #[Route('/api/tips/{month}', name: 'tips_show', methods: ['GET'], requirements: ['month' => '^(1[0-2]|[1-9])$'])]
    public function show(TipRepository $tipRepository, int $month): JsonResponse
    {
        $tips = $tipRepository->findByMonth($month);

        $context = SerializationContext::create()->setGroups(['tip_detail']);
        $jsonTips = $this->serializer->serialize($tips, 'json', $context);

        return new JsonResponse($jsonTips, Response::HTTP_OK, [], true);
    }

    #[Route('/api/tips', name: 'tips_create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour ajouter un conseil.')]
    public function create(
        Request $request,
        MonthRepository $monthRepository,
        ValidatorInterface $validator,
        EntityManagerInterface $manager
    ): JsonResponse
    {
        $content = $request->toArray();

        $tip = new Tip();
        $tip->setContent($content['content'] ?? '');
        $monthNumbers = $content['monthNumbers'] ?? [];

        foreach ($monthNumbers as $number) {
            $month = $monthRepository->findOneBy(['number' => (int) $number]);

            if (!$month) {
                return new JsonResponse(
                    json_encode(['error' => 'Le mois numéro ' . $number . ' est invalide.']),
                    JsonResponse::HTTP_BAD_REQUEST,
                    [],
                    true
                );
            }

            $tip->addMonth($month);
        }

        $errors = $validator->validate($tip);

        if ($errors->count() > 0) {
            return new JsonResponse(
                $this->serializer->serialize($errors, 'json'),
                JsonResponse::HTTP_BAD_REQUEST,
                [],
                true
            );
        }

        $manager->persist($tip);
        $manager->flush();

        $context = SerializationContext::create()->setGroups(['tip_list']);
        $jsonTip = $this->serializer->serialize($tip, 'json', $context);

        return new JsonResponse($jsonTip, Response::HTTP_CREATED, [], true);	
    }

    #[Route('/api/tips/{id}', name: 'tips_update', methods: ['PUT'], requirements: ['id' => '\d+'],)]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour ajouter un conseil.')]
    public function update(
        Request $request,
        Tip $tip,
        MonthRepository $monthRepository,
        ValidatorInterface $validator,
        EntityManagerInterface $manager,
    ): JsonResponse
    {
        $updatedTip = $this->serializer->deserialize($request->getContent(), Tip::class, 'json');

        if($updatedTip->getContent() !== null) {
            $tip->setContent($updatedTip->getContent());
        }

        $content = $request->toArray();
        $monthNumbers = $content['monthNumbers'] ?? null;

        if (is_array($monthNumbers)) {
            $existingMonths = array_map(fn($month) => $month->getNumber(), $tip->getMonths()->toArray());

            $toAdd = array_diff($monthNumbers, $existingMonths);
            $toRemove = array_diff($existingMonths, $monthNumbers);

            foreach ($toAdd as $number) {
                $month = $monthRepository->findOneBy(['number' => (int) $number]);

                if ($month) {
                    $tip->addMonth($month);
                }
            }

            foreach ($tip->getMonths() as $month) {
                if (in_array($month->getNumber(), $toRemove)) {
                    $tip->removeMonth($month);
                }
            }
        }

        $errors = $validator->validate($tip);

        if ($errors->count() > 0) {
            return new JsonResponse(
                $this->serializer->serialize($errors, 'json'),
                JsonResponse::HTTP_BAD_REQUEST,
                [],
                true
            );
        }

        $manager->persist($tip);
        $manager->flush();

        $context = SerializationContext::create()->setGroups(['tip_list']);
        $jsonTip = $this->serializer->serialize($tip, 'json', $context);

        return new JsonResponse($jsonTip, Response::HTTP_OK, [], true);	
    }
}

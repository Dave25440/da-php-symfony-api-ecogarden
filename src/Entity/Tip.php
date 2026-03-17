<?php

namespace App\Entity;

use App\Repository\TipRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TipRepository::class)]
class Tip
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id')]
    private ?int $id = null;

    #[ORM\Column(name: 'content', type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Le conseil est obligatoire.')]
    private ?string $content = null;

    #[ORM\Column(name: 'months', type: Types::SIMPLE_ARRAY)]
    #[Assert\Count(min: 1, minMessage: 'Au moins un mois doit être sélectionné.')]
    #[Assert\All([
        new Assert\Choice(choices: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12], message: 'Le mois numéro {{ value }} est invalide.')
    ])]
    private array $months = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getMonths(): array
    {
        return $this->months;
    }

    public function setMonths(array $months): static
    {
        $this->months = $months;

        return $this;
    }
}

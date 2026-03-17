<?php

namespace App\Entity;

use App\Repository\TipRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TipRepository::class)]
class Tip
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id')]
    private ?int $id = null;

    #[ORM\Column(name: 'content', type: Types::TEXT)]
    private ?string $content = null;

    #[ORM\Column(name: 'months', type: Types::SIMPLE_ARRAY)]
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

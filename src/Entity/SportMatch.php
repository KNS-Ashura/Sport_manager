<?php

namespace App\Entity;

use App\Repository\SportMatchRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SportMatchRepository::class)]
class SportMatch
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTime $matchDate = null;

    #[ORM\Column]
    private int $scorePlayer1 = 0;

    #[ORM\Column]
    private int $scorePlayer2 = 0;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\ManyToOne(inversedBy: 'games')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Tournament $tournament = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $player1 = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $player2 = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMatchDate(): ?\DateTime
    {
        return $this->matchDate;
    }

    public function setMatchDate(\DateTime $matchDate): static
    {
        $this->matchDate = $matchDate;

        return $this;
    }

    public function getScorePlayer1(): int
    {
        return $this->scorePlayer1;
    }

    public function setScorePlayer1(int $scorePlayer1): static
    {
        $this->scorePlayer1 = $scorePlayer1;

        return $this;
    }

    public function getScorePlayer2(): int
    {
        return $this->scorePlayer2;
    }

    public function setScorePlayer2(int $scorePlayer2): static
    {
        $this->scorePlayer2 = $scorePlayer2;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getTournament(): ?Tournament
    {
        return $this->tournament;
    }

    public function setTournament(Tournament $tournament): static
    {
        $this->tournament = $tournament;

        return $this;
    }

    public function getPlayer1(): ?User
    {
        return $this->player1;
    }

    public function setPlayer1(User $player1): static
    {
        $this->player1 = $player1;

        return $this;
    }

    public function getPlayer2(): ?User
    {
        return $this->player2;
    }

    public function setPlayer2(User $player2): static
    {
        $this->player2 = $player2;

        return $this;
    }
}

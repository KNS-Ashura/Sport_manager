<?php

namespace App\Entity;

use App\Repository\TournamentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TournamentRepository::class)]
class Tournament
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom du tournoi est obligatoire.')]
    private ?string $tournamentName = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'La date de début est obligatoire.')]
    #[Assert\Type("\DateTimeInterface")]
    private ?\DateTime $startDate = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'La date de fin est obligatoire.')]
    #[Assert\Type("\DateTimeInterface")]
    #[Assert\GreaterThanOrEqual(propertyPath: 'startDate', message: 'La date de fin doit être postérieure ou égale à la date de début.')]
    private ?\DateTime $endDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'La description est obligatoire.')]
    private ?string $description = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'Le nombre de participants est obligatoire.')]
    #[Assert\Positive(message: 'Le nombre de participants doit être supérieur à 0.')]
    private ?int $maxParticipants = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le sport est obligatoire.')]
    private ?string $sport = null;

    #[ORM\ManyToOne(inversedBy: 'organizedTournaments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $organizer = null;

    #[ORM\ManyToOne(inversedBy: 'wonTournaments')]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $winner = null;

    /**
     * @var Collection<int, SportMatch>
     */
    #[ORM\OneToMany(targetEntity: SportMatch::class, mappedBy: 'tournament', orphanRemoval: true)]
    private Collection $games;

    /**
     * @var Collection<int, Registration>
     */
    #[ORM\OneToMany(targetEntity: Registration::class, mappedBy: 'tournament', orphanRemoval: true)]
    private Collection $registrations;

    public function __construct()
    {
        $this->games = new ArrayCollection();
        $this->registrations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTournamentName(): ?string
    {
        return $this->tournamentName;
    }

    public function setTournamentName(string $tournamentName): static
    {
        $this->tournamentName = $tournamentName;

        return $this;
    }

    public function getStartDate(): ?\DateTime
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTime $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTime
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTime $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): static
    {
        $this->location = $location;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getMaxParticipants(): ?int
    {
        return $this->maxParticipants;
    }

    public function setMaxParticipants(int $maxParticipants): static
    {
        $this->maxParticipants = $maxParticipants;

        return $this;
    }

    public function getSport(): ?string
    {
        return $this->sport;
    }

    public function setSport(string $sport): static
    {
        $this->sport = $sport;

        return $this;
    }

    public function getOrganizer(): ?User
    {
        return $this->organizer;
    }

    public function setOrganizer(User $organizer): static
    {
        $this->organizer = $organizer;

        return $this;
    }

    public function getWinner(): ?User
    {
        return $this->winner;
    }

    public function setWinner(?User $winner): static
    {
        $this->winner = $winner;

        return $this;
    }

    /**
     * @return Collection<int, SportMatch>
     */
    public function getGames(): Collection
    {
        return $this->games;
    }

    public function addGame(SportMatch $game): static
    {
        if (!$this->games->contains($game)) {
            $this->games->add($game);
            $game->setTournament($this);
        }

        return $this;
    }

    public function removeGame(SportMatch $game): static
    {
        $this->games->removeElement($game);

        return $this;
    }

    /**
     * @return Collection<int, Registration>
     */
    public function getRegistrations(): Collection
    {
        return $this->registrations;
    }

    public function addRegistration(Registration $registration): static
    {
        if (!$this->registrations->contains($registration)) {
            $this->registrations->add($registration);
            $registration->setTournament($this);
        }

        return $this;
    }

    public function removeRegistration(Registration $registration): static
    {
        $this->registrations->removeElement($registration);

        return $this;
    }

    public function getStatus(): string
    {
        $now = new \DateTime();

        if ($this->winner !== null) {
            return 'finished';
        }

        if ($now < $this->startDate) {
            return 'upcoming';
        }

        if ($now > $this->endDate) {
            return 'finished';
        }

        return 'ongoing';
    }
}

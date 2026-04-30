<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $lastName = null;

    #[ORM\Column(length: 255)]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    private ?string $username = null;

    #[ORM\Column(length: 255)]
    private ?string $emailAddress = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    /**
     * @var Collection<int, Tournament>
     */
    #[ORM\OneToMany(targetEntity: Tournament::class, mappedBy: 'organizer')]
    private Collection $organizedTournaments;

    /**
     * @var Collection<int, Tournament>
     */
    #[ORM\OneToMany(targetEntity: Tournament::class, mappedBy: 'winner')]
    private Collection $wonTournaments;

    /**
     * @var Collection<int, Registration>
     */
    #[ORM\OneToMany(targetEntity: Registration::class, mappedBy: 'player', orphanRemoval: true)]
    private Collection $registrations;

    public function __construct()
    {
        $this->organizedTournaments = new ArrayCollection();
        $this->wonTournaments = new ArrayCollection();
        $this->registrations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getEmailAddress(): ?string
    {
        return $this->emailAddress;
    }

    public function setEmailAddress(string $emailAddress): static
    {
        $this->emailAddress = $emailAddress;

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

    /**
     * @return Collection<int, Tournament>
     */
    public function getOrganizedTournaments(): Collection
    {
        return $this->organizedTournaments;
    }

    public function addOrganizedTournament(Tournament $tournament): static
    {
        if (!$this->organizedTournaments->contains($tournament)) {
            $this->organizedTournaments->add($tournament);
            $tournament->setOrganizer($this);
        }

        return $this;
    }

    public function removeOrganizedTournament(Tournament $tournament): static
    {
        $this->organizedTournaments->removeElement($tournament);

        return $this;
    }

    /**
     * @return Collection<int, Tournament>
     */
    public function getWonTournaments(): Collection
    {
        return $this->wonTournaments;
    }

    public function addWonTournament(Tournament $tournament): static
    {
        if (!$this->wonTournaments->contains($tournament)) {
            $this->wonTournaments->add($tournament);
            $tournament->setWinner($this);
        }

        return $this;
    }

    public function removeWonTournament(Tournament $tournament): static
    {
        if ($this->wonTournaments->removeElement($tournament)) {
            if ($tournament->getWinner() === $this) {
                $tournament->setWinner(null);
            }
        }

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
            $registration->setPlayer($this);
        }

        return $this;
    }

    public function removeRegistration(Registration $registration): static
    {
        $this->registrations->removeElement($registration);

        return $this;
    }
}

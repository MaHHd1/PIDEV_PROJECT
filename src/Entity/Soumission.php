<?php

namespace App\Entity;

use App\Repository\SoumissionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SoumissionRepository::class)]
class Soumission
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'soumissions')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "L'évaluation est obligatoire")]
    private ?Evaluation $evaluation = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: "L'identifiant étudiant est obligatoire")]
    private ?string $idEtudiant = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(
        max: 255,
        maxMessage: "L'URL ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $fichierSoumissionUrl = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        max: 1000,
        maxMessage: "Le commentaire ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $commentaireEtudiant = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateSoumission = null;

    #[ORM\Column(length: 50)]
    #[Assert\Choice(
        choices: ['soumise', 'en_retard', 'non_soumise'],
        message: "Le statut doit être : soumise, en_retard ou non_soumise"
    )]
    private string $statut = 'non_soumise';

    // 🔥 UNE SOUMISSION = UN SCORE
    #[ORM\OneToOne(mappedBy: 'soumission', targetEntity: Score::class, cascade: ['remove'])]
    private ?Score $score = null;

    public function __construct()
    {
        $this->dateSoumission = new \DateTime();
    }

    // ================= GETTERS & SETTERS =================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEvaluation(): ?Evaluation
    {
        return $this->evaluation;
    }

    public function setEvaluation(?Evaluation $evaluation): static
    {
        $this->evaluation = $evaluation;
        return $this;
    }

    public function getIdEtudiant(): ?string
    {
        return $this->idEtudiant;
    }

    public function setIdEtudiant(string $idEtudiant): static
    {
        $this->idEtudiant = $idEtudiant;
        return $this;
    }

    public function getFichierSoumissionUrl(): ?string
    {
        return $this->fichierSoumissionUrl;
    }

    public function setFichierSoumissionUrl(?string $fichierSoumissionUrl): static
    {
        $this->fichierSoumissionUrl = $fichierSoumissionUrl;
        return $this;
    }

    public function getCommentaireEtudiant(): ?string
    {
        return $this->commentaireEtudiant;
    }

    public function setCommentaireEtudiant(?string $commentaireEtudiant): static
    {
        $this->commentaireEtudiant = $commentaireEtudiant;
        return $this;
    }

    public function getDateSoumission(): ?\DateTimeInterface
    {
        return $this->dateSoumission;
    }

    public function setDateSoumission(\DateTimeInterface $dateSoumission): static
    {
        $this->dateSoumission = $dateSoumission;
        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getScore(): ?Score
    {
        return $this->score;
    }

    public function setScore(?Score $score): static
    {
        $this->score = $score;
        return $this;
    }
}

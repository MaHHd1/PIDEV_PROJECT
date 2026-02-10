<?php

namespace App\Entity;

use App\Repository\ScoreRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: ScoreRepository::class)]
class Score
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // 🔥 UNE SOUMISSION = UN SEUL SCORE
    #[ORM\OneToOne(inversedBy: 'score', targetEntity: Soumission::class)]
    #[ORM\JoinColumn(nullable: false, unique: true)]
    #[Assert\NotNull(message: "La soumission est obligatoire")]
    private ?Soumission $soumission = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    #[Assert\NotBlank(message: "La note est obligatoire")]
    #[Assert\PositiveOrZero(message: "La note doit être positive ou zéro")]
    private ?string $note = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    #[Assert\NotBlank(message: "La note sur est obligatoire")]
    #[Assert\Positive(message: "La note sur doit être positive")]
    private ?string $noteSur = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        max: 2000,
        maxMessage: "Le commentaire ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $commentaireEnseignant = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateCorrection = null;

    #[ORM\Column(length: 50)]
    #[Assert\Choice(
        choices: ['a_corriger', 'corrige'],
        message: "Le statut doit être : a_corriger ou corrige"
    )]
    private string $statutCorrection = 'a_corriger';

    public function __construct()
    {
        $this->dateCorrection = new \DateTime();
    }

    // ================= GETTERS & SETTERS =================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSoumission(): ?Soumission
    {
        return $this->soumission;
    }

    public function setSoumission(Soumission $soumission): static
    {
        $this->soumission = $soumission;
        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(string $note): static
    {
        $this->note = $note;
        return $this;
    }

    public function getNoteSur(): ?string
    {
        return $this->noteSur;
    }

    public function setNoteSur(string $noteSur): static
    {
        $this->noteSur = $noteSur;
        return $this;
    }

    public function getCommentaireEnseignant(): ?string
    {
        return $this->commentaireEnseignant;
    }

    public function setCommentaireEnseignant(?string $commentaireEnseignant): static
    {
        $this->commentaireEnseignant = $commentaireEnseignant;
        return $this;
    }

    public function getDateCorrection(): ?\DateTimeInterface
    {
        return $this->dateCorrection;
    }

    public function setDateCorrection(\DateTimeInterface $dateCorrection): static
    {
        $this->dateCorrection = $dateCorrection;
        return $this;
    }

    public function getStatutCorrection(): string
    {
        return $this->statutCorrection;
    }

    public function setStatutCorrection(string $statutCorrection): static
    {
        $this->statutCorrection = $statutCorrection;
        return $this;
    }

    // ================= VALIDATION =================

    #[Assert\Callback]
    public function validateNote(ExecutionContextInterface $context): void
    {
        if ($this->note !== null && $this->noteSur !== null) {
            if ((float)$this->note > (float)$this->noteSur) {
                $context->buildViolation(
                    'La note obtenue ne peut pas être supérieure à la note maximale'
                )
                ->atPath('note')
                ->addViolation();
            }
        }
    }

    // ================= MÉTHODES MÉTIER =================

    public function getPourcentage(): ?float
    {
        if ($this->note !== null && $this->noteSur !== null && (float)$this->noteSur > 0) {
            return round(((float)$this->note / (float)$this->noteSur) * 100, 2);
        }
        return null;
    }

    public function getMention(): ?string
    {
        $p = $this->getPourcentage();

        return match (true) {
            $p >= 90 => 'Excellent',
            $p >= 80 => 'Très bien',
            $p >= 70 => 'Bien',
            $p >= 60 => 'Assez bien',
            $p >= 50 => 'Passable',
            default => 'Insuffisant',
        };
    }

    public function estReussi(): bool
    {
        return $this->getPourcentage() !== null && $this->getPourcentage() >= 50;
    }

    public function __toString(): string
    {
        return sprintf(
            '%s : %s/%s',
            $this->soumission?->getEvaluation()?->getTitre() ?? 'Évaluation',
            $this->note ?? '?',
            $this->noteSur ?? '?'
        );
    }
}

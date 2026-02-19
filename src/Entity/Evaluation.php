<?php
// src/Entity/Evaluation.php
namespace App\Entity;

use App\Repository\EvaluationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EvaluationRepository::class)]
class Evaluation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le titre est obligatoire")]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: "Le titre doit contenir au moins {{ limit }} caractères",
        maxMessage: "Le titre ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $titre = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: "Le type d'évaluation est obligatoire")]
    #[Assert\Choice(
        choices: ['projet', 'examen'],
        message: "Le type doit être : projet ou examen"
    )]
    private ?string $typeEvaluation = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        max: 2000,
        maxMessage: "La description ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $description = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: "Le cours est obligatoire")]
    private ?string $idCours = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: "L'enseignant est obligatoire")]
    private ?string $idEnseignant = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotBlank(message: "La date limite est obligatoire")]
    #[Assert\GreaterThan(
        value: "today",
        message: "La date limite doit être supérieure à aujourd'hui"
    )]
    private ?\DateTimeInterface $dateLimite = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    #[Assert\NotBlank(message: "La note maximale est obligatoire")]
    #[Assert\Positive(message: "La note maximale doit être positive")]
    #[Assert\Range(
        min: 1,
        max: 100,
        notInRangeMessage: "La note maximale doit être entre {{ min }} et {{ max }}"
    )]
    private ?string $noteMax = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: "Le mode de remise est obligatoire")]
    #[Assert\Choice(
        choices: ['en_ligne', 'presentiel'],
        message: "Le mode doit être : en_ligne ou presentiel"
    )]
    private ?string $modeRemise = null;

    #[ORM\Column(length: 50)]
    #[Assert\Choice(
        choices: ['ouverte', 'fermee',],
        message: "Le statut doit être : ouverte ou fermee"
    )]
    private ?string $statut = 'ouverte';

    #[ORM\OneToMany(mappedBy: 'evaluation', targetEntity: Soumission::class, cascade: ['remove'])]
    private Collection $soumissions;

    public function __construct()
    {
        $this->soumissions = new ArrayCollection();
        $this->dateCreation = new \DateTime();
    }

    // Getters et Setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;
        return $this;
    }

    public function getTypeEvaluation(): ?string
    {
        return $this->typeEvaluation;
    }

    public function setTypeEvaluation(string $typeEvaluation): static
    {
        $this->typeEvaluation = $typeEvaluation;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getIdCours(): ?string
    {
        return $this->idCours;
    }

    public function setIdCours(string $idCours): static
    {
        $this->idCours = $idCours;
        return $this;
    }

    public function getIdEnseignant(): ?string
    {
        return $this->idEnseignant;
    }

    public function setIdEnseignant(string $idEnseignant): static
    {
        $this->idEnseignant = $idEnseignant;
        return $this;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeInterface $dateCreation): static
    {
        $this->dateCreation = $dateCreation;
        return $this;
    }

    public function getDateLimite(): ?\DateTimeInterface
    {
        return $this->dateLimite;
    }

    public function setDateLimite(\DateTimeInterface $dateLimite): static
    {
        $this->dateLimite = $dateLimite;
        return $this;
    }

    public function getNoteMax(): ?string
    {
        return $this->noteMax;
    }

    public function setNoteMax(string $noteMax): static
    {
        $this->noteMax = $noteMax;
        return $this;
    }

    public function getModeRemise(): ?string
    {
        return $this->modeRemise;
    }

    public function setModeRemise(string $modeRemise): static
    {
        $this->modeRemise = $modeRemise;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    /**
     * @return Collection<int, Soumission>
     */
    public function getSoumissions(): Collection
    {
        return $this->soumissions;
    }

    public function addSoumission(Soumission $soumission): static
    {
        if (!$this->soumissions->contains($soumission)) {
            $this->soumissions->add($soumission);
            $soumission->setEvaluation($this);
        }
        return $this;
    }

    public function removeSoumission(Soumission $soumission): static
    {
        if ($this->soumissions->removeElement($soumission)) {
            if ($soumission->getEvaluation() === $this) {
                $soumission->setEvaluation(null);
            }
        }
        return $this;
    }
}
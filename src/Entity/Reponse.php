<?php

namespace App\Entity;

use App\Repository\ReponseRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReponseRepository::class)]
class Reponse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_reponse = null;

    #[ORM\ManyToOne(targetEntity: Question::class)]
    #[ORM\JoinColumn(name: 'id_question', referencedColumnName: 'id_question', nullable: false)]
    private ?Question $question = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $texte_reponse = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $est_correcte = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $ordre_affichage = null;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, nullable: true)]
    private ?string $pourcentage_points = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $feedback_specifique = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $media_url = null;

    /* ================= GETTERS / SETTERS ================= */

    public function getIdReponse(): ?int
    {
        return $this->id_reponse;
    }

    public function getQuestion(): ?Question
    {
        return $this->question;
    }

    public function setQuestion(?Question $question): self
    {
        $this->question = $question;
        return $this;
    }

    public function getTexteReponse(): ?string
    {
        return $this->texte_reponse;
    }

    public function setTexteReponse(?string $texte_reponse): self
    {
        $this->texte_reponse = $texte_reponse;
        return $this;
    }

    public function getEstCorrecte(): ?bool
    {
        return $this->est_correcte;
    }

    public function setEstCorrecte(?bool $est_correcte): self
    {
        $this->est_correcte = $est_correcte;
        return $this;
    }

    public function getOrdreAffichage(): ?int
    {
        return $this->ordre_affichage;
    }

    public function setOrdreAffichage(?int $ordre_affichage): self
    {
        $this->ordre_affichage = $ordre_affichage;
        return $this;
    }

    public function getPourcentagePoints(): ?string
    {
        return $this->pourcentage_points;
    }

    public function setPourcentagePoints(?string $pourcentage_points): self
    {
        $this->pourcentage_points = $pourcentage_points;
        return $this;
    }

    public function getFeedbackSpecifique(): ?string
    {
        return $this->feedback_specifique;
    }

    public function setFeedbackSpecifique(?string $feedback_specifique): self
    {
        $this->feedback_specifique = $feedback_specifique;
        return $this;
    }

    public function getMediaUrl(): ?string
    {
        return $this->media_url;
    }

    public function setMediaUrl(?string $media_url): self
    {
        $this->media_url = $media_url;
        return $this;
    }
}

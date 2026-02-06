<?php

namespace App\Entity;

use App\Repository\QuestionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QuestionRepository::class)]
class Question
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_question = null;

    #[ORM\ManyToOne(targetEntity: Quiz::class)]
    #[ORM\JoinColumn(name: 'id_quiz', referencedColumnName: 'id_quiz', nullable: false)]
    private ?Quiz $quiz = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $enonce = null;

    #[ORM\Column(type: 'string', columnDefinition: "ENUM('choix_multiple','vrai_faux','texte_libre')", nullable: true)]
    private ?string $type_question = null;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, nullable: true)]
    private ?string $points = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $ordre_affichage = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $media_url = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $feedback_correct = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $feedback_incorrect = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $temps_suggere_secondes = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $competences_cibles = null;

    /* ================= GETTERS / SETTERS ================= */

    public function getIdQuestion(): ?int
    {
        return $this->id_question;
    }

    public function getQuiz(): ?Quiz
    {
        return $this->quiz;
    }

    public function setQuiz(?Quiz $quiz): self
    {
        $this->quiz = $quiz;
        return $this;
    }

    public function getEnonce(): ?string
    {
        return $this->enonce;
    }

    public function setEnonce(?string $enonce): self
    {
        $this->enonce = $enonce;
        return $this;
    }

    public function getTypeQuestion(): ?string
    {
        return $this->type_question;
    }

    public function setTypeQuestion(?string $type_question): self
    {
        $this->type_question = $type_question;
        return $this;
    }

    public function getPoints(): ?string
    {
        return $this->points;
    }

    public function setPoints(?string $points): self
    {
        $this->points = $points;
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

    public function getMediaUrl(): ?string
    {
        return $this->media_url;
    }

    public function setMediaUrl(?string $media_url): self
    {
        $this->media_url = $media_url;
        return $this;
    }

    public function getFeedbackCorrect(): ?string
    {
        return $this->feedback_correct;
    }

    public function setFeedbackCorrect(?string $feedback_correct): self
    {
        $this->feedback_correct = $feedback_correct;
        return $this;
    }

    public function getFeedbackIncorrect(): ?string
    {
        return $this->feedback_incorrect;
    }

    public function setFeedbackIncorrect(?string $feedback_incorrect): self
    {
        $this->feedback_incorrect = $feedback_incorrect;
        return $this;
    }

    public function getTempsSuggereSecondes(): ?int
    {
        return $this->temps_suggere_secondes;
    }

    public function setTempsSuggereSecondes(?int $temps_suggere_secondes): self
    {
        $this->temps_suggere_secondes = $temps_suggere_secondes;
        return $this;
    }

    public function getCompetencesCibles(): ?string
    {
        return $this->competences_cibles;
    }

    public function setCompetencesCibles(?string $competences_cibles): self
    {
        $this->competences_cibles = $competences_cibles;
        return $this;
    }
}


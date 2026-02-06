<?php

namespace App\Entity;

use App\Repository\QuizRepository;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity]
class Quiz
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_quiz = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $titre = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private int $id_createur;

    #[ORM\Column(nullable: false)]
    private int $id_cours;

    #[ORM\Column(type: 'string', columnDefinition: "ENUM('formative','sommative','diagnostique')", nullable: true)]
    private ?string $type_quiz = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $date_creation = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $date_debut_disponibilite = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $date_fin_disponibilite = null;

    #[ORM\Column(nullable: true)]
    private ?int $duree_minutes = null;

    #[ORM\Column(nullable: true)]
    private ?int $nombre_tentatives_autorisees = null;

    #[ORM\Column(type: 'decimal', precision: 3, scale: 2, nullable: true)]
    private ?string $difficulte_moyenne = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $instructions = null;

    #[ORM\Column(type: 'string', columnDefinition: "ENUM('immédiat','date','jamais')", nullable: true)]
    private ?string $afficher_correction_apres = null;
}


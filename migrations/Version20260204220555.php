<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260204220555 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE quiz (id_quiz INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, id_createur INT NOT NULL, id_cours INT NOT NULL, type_quiz ENUM(\'formative\',\'sommative\',\'diagnostique\'), date_creation DATETIME DEFAULT NULL, date_debut_disponibilite DATETIME DEFAULT NULL, date_fin_disponibilite DATETIME DEFAULT NULL, duree_minutes INT DEFAULT NULL, nombre_tentatives_autorisees INT DEFAULT NULL, difficulte_moyenne NUMERIC(3, 2) DEFAULT NULL, instructions LONGTEXT DEFAULT NULL, afficher_correction_apres ENUM(\'immédiat\',\'date\',\'jamais\'), PRIMARY KEY (id_quiz)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE quiz');
        $this->addSql('DROP TABLE messenger_messages');
    }
}

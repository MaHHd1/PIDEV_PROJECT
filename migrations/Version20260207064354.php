<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260207064354 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE evaluation (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, type_evaluation VARCHAR(50) NOT NULL, description LONGTEXT DEFAULT NULL, id_cours VARCHAR(100) NOT NULL, id_enseignant VARCHAR(100) NOT NULL, date_creation DATETIME NOT NULL, date_limite DATETIME NOT NULL, note_max NUMERIC(5, 2) NOT NULL, mode_remise VARCHAR(50) NOT NULL, statut VARCHAR(50) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE score (id INT AUTO_INCREMENT NOT NULL, note NUMERIC(5, 2) NOT NULL, note_sur NUMERIC(5, 2) NOT NULL, commentaire_enseignant LONGTEXT DEFAULT NULL, date_correction DATETIME DEFAULT NULL, statut_correction VARCHAR(50) NOT NULL, soumission_id INT NOT NULL, INDEX IDX_32993751801F9DE6 (soumission_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE soumission (id INT AUTO_INCREMENT NOT NULL, id_etudiant VARCHAR(100) NOT NULL, fichier_soumission_url VARCHAR(255) DEFAULT NULL, commentaire_etudiant LONGTEXT DEFAULT NULL, date_soumission DATETIME NOT NULL, statut VARCHAR(50) NOT NULL, evaluation_id INT NOT NULL, INDEX IDX_9495AA2E456C5646 (evaluation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE score ADD CONSTRAINT FK_32993751801F9DE6 FOREIGN KEY (soumission_id) REFERENCES soumission (id)');
        $this->addSql('ALTER TABLE soumission ADD CONSTRAINT FK_9495AA2E456C5646 FOREIGN KEY (evaluation_id) REFERENCES evaluation (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE score DROP FOREIGN KEY FK_32993751801F9DE6');
        $this->addSql('ALTER TABLE soumission DROP FOREIGN KEY FK_9495AA2E456C5646');
        $this->addSql('DROP TABLE evaluation');
        $this->addSql('DROP TABLE score');
        $this->addSql('DROP TABLE soumission');
        $this->addSql('DROP TABLE messenger_messages');
    }
}

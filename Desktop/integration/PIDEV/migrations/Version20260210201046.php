<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260210201046 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE evenement (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, type_evenement VARCHAR(50) NOT NULL, date_debut DATETIME NOT NULL, date_fin DATETIME NOT NULL, lieu VARCHAR(255) DEFAULT NULL, capacite_max INT DEFAULT NULL, statut VARCHAR(50) NOT NULL, visibilite VARCHAR(50) NOT NULL, createur_id INT NOT NULL, INDEX IDX_B26681E73A201E5 (createur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE participation_evenement (id INT AUTO_INCREMENT NOT NULL, statut VARCHAR(50) NOT NULL, date_inscription DATETIME NOT NULL, heure_arrivee DATETIME DEFAULT NULL, heure_depart DATETIME DEFAULT NULL, feedback_note INT DEFAULT NULL, feedback_commentaire LONGTEXT DEFAULT NULL, evenement_id INT NOT NULL, utilisateur_id INT NOT NULL, INDEX IDX_65A14675FD02F13 (evenement_id), INDEX IDX_65A14675FB88E14F (utilisateur_id), UNIQUE INDEX unique_participation (evenement_id, utilisateur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE evenement ADD CONSTRAINT FK_B26681E73A201E5 FOREIGN KEY (createur_id) REFERENCES Utilisateur (id)');
        $this->addSql('ALTER TABLE participation_evenement ADD CONSTRAINT FK_65A14675FD02F13 FOREIGN KEY (evenement_id) REFERENCES evenement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE participation_evenement ADD CONSTRAINT FK_65A14675FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES Utilisateur (id) ON DELETE CASCADE');
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('DROP TABLE user');
        $this->addSql('ALTER TABLE question CHANGE createur_id createur_id INT DEFAULT NULL, CHANGE type_quiz type_quiz VARCHAR(255) DEFAULT NULL, CHANGE type_question type_question VARCHAR(255) DEFAULT NULL, CHANGE metadata metadata JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE quiz CHANGE titre titre VARCHAR(255) NOT NULL, CHANGE id_createur id_createur INT DEFAULT NULL, CHANGE type_quiz type_quiz VARCHAR(50) DEFAULT NULL, CHANGE difficulte_moyenne difficulte_moyenne DOUBLE PRECISION DEFAULT NULL, CHANGE afficher_correction_apres afficher_correction_apres VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE score DROP INDEX IDX_32993751801F9DE6, ADD UNIQUE INDEX UNIQ_32993751801F9DE6 (soumission_id)');
        $this->addSql('ALTER TABLE score CHANGE date_correction date_correction DATETIME NOT NULL');
        $this->addSql('ALTER TABLE score ADD CONSTRAINT FK_32993751801F9DE6 FOREIGN KEY (soumission_id) REFERENCES soumission (id)');
        $this->addSql('ALTER TABLE sessions CHANGE sess_data sess_data BLOB NOT NULL');
        $this->addSql('ALTER TABLE soumission ADD CONSTRAINT FK_9495AA2E456C5646 FOREIGN KEY (evaluation_id) REFERENCES evaluation (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, headers LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, queue_name VARCHAR(190) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, password VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE evenement DROP FOREIGN KEY FK_B26681E73A201E5');
        $this->addSql('ALTER TABLE participation_evenement DROP FOREIGN KEY FK_65A14675FD02F13');
        $this->addSql('ALTER TABLE participation_evenement DROP FOREIGN KEY FK_65A14675FB88E14F');
        $this->addSql('DROP TABLE evenement');
        $this->addSql('DROP TABLE participation_evenement');
        $this->addSql('ALTER TABLE question CHANGE type_quiz type_quiz ENUM(\'formative\', \'sommative\', \'diagnostique\') DEFAULT NULL, CHANGE type_question type_question VARCHAR(255) NOT NULL, CHANGE metadata metadata LONGTEXT DEFAULT NULL, CHANGE createur_id createur_id INT NOT NULL');
        $this->addSql('ALTER TABLE quiz CHANGE titre titre VARCHAR(255) DEFAULT NULL, CHANGE type_quiz type_quiz ENUM(\'formative\', \'sommative\', \'diagnostique\') DEFAULT NULL, CHANGE difficulte_moyenne difficulte_moyenne NUMERIC(3, 2) DEFAULT NULL, CHANGE afficher_correction_apres afficher_correction_apres ENUM(\'immédiat\', \'date\', \'jamais\') DEFAULT NULL, CHANGE id_createur id_createur INT NOT NULL');
        $this->addSql('ALTER TABLE score DROP INDEX UNIQ_32993751801F9DE6, ADD INDEX IDX_32993751801F9DE6 (soumission_id)');
        $this->addSql('ALTER TABLE score DROP FOREIGN KEY FK_32993751801F9DE6');
        $this->addSql('ALTER TABLE score CHANGE date_correction date_correction DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE sessions CHANGE sess_data sess_data BLOB NOT NULL');
        $this->addSql('ALTER TABLE soumission DROP FOREIGN KEY FK_9495AA2E456C5646');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260204223746 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE reponse (id_reponse INT AUTO_INCREMENT NOT NULL, texte_reponse LONGTEXT DEFAULT NULL, est_correcte TINYINT DEFAULT NULL, ordre_affichage INT DEFAULT NULL, pourcentage_points NUMERIC(5, 2) DEFAULT NULL, feedback_specifique LONGTEXT DEFAULT NULL, media_url VARCHAR(500) DEFAULT NULL, id_question INT NOT NULL, INDEX IDX_5FB6DEC7E62CA5DB (id_question), PRIMARY KEY (id_reponse)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE reponse ADD CONSTRAINT FK_5FB6DEC7E62CA5DB FOREIGN KEY (id_question) REFERENCES question (id_question)');
        $this->addSql('ALTER TABLE question CHANGE type_question type_question ENUM(\'choix_multiple\',\'vrai_faux\',\'texte_libre\'), CHANGE points points NUMERIC(5, 2) DEFAULT NULL, CHANGE media_url media_url VARCHAR(500) DEFAULT NULL');
        $this->addSql('ALTER TABLE quiz CHANGE titre titre VARCHAR(255) DEFAULT NULL, CHANGE type_quiz type_quiz ENUM(\'formative\',\'sommative\',\'diagnostique\'), CHANGE date_creation date_creation DATETIME DEFAULT NULL, CHANGE date_debut_disponibilite date_debut_disponibilite DATETIME DEFAULT NULL, CHANGE date_fin_disponibilite date_fin_disponibilite DATETIME DEFAULT NULL, CHANGE difficulte_moyenne difficulte_moyenne NUMERIC(3, 2) DEFAULT NULL, CHANGE afficher_correction_apres afficher_correction_apres ENUM(\'immédiat\',\'date\',\'jamais\')');
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reponse DROP FOREIGN KEY FK_5FB6DEC7E62CA5DB');
        $this->addSql('DROP TABLE reponse');
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE question CHANGE type_question type_question ENUM(\'choix_multiple\', \'vrai_faux\', \'texte_libre\') DEFAULT \'NULL\', CHANGE points points NUMERIC(5, 2) DEFAULT \'NULL\', CHANGE media_url media_url VARCHAR(500) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE quiz CHANGE titre titre VARCHAR(255) DEFAULT \'NULL\', CHANGE type_quiz type_quiz ENUM(\'formative\', \'sommative\', \'diagnostique\') DEFAULT \'NULL\', CHANGE date_creation date_creation DATETIME DEFAULT \'NULL\', CHANGE date_debut_disponibilite date_debut_disponibilite DATETIME DEFAULT \'NULL\', CHANGE date_fin_disponibilite date_fin_disponibilite DATETIME DEFAULT \'NULL\', CHANGE difficulte_moyenne difficulte_moyenne NUMERIC(3, 2) DEFAULT \'NULL\', CHANGE afficher_correction_apres afficher_correction_apres ENUM(\'immédiat\', \'date\', \'jamais\') DEFAULT \'NULL\'');
    }
}

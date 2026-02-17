<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260217181647 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE commentaire_forum (id_commentaire INT AUTO_INCREMENT NOT NULL, contenu LONGTEXT NOT NULL, date_publication DATETIME NOT NULL, date_modification DATETIME DEFAULT NULL, likes INT NOT NULL, dislikes INT NOT NULL, signalements INT NOT NULL, statut VARCHAR(20) NOT NULL, est_modifie TINYINT NOT NULL, nb_reponses INT NOT NULL, id_forum INT NOT NULL, id_utilisateur INT NOT NULL, id_parent INT DEFAULT NULL, INDEX IDX_A776D16BAEFFFD (id_forum), INDEX IDX_A776D150EAE44 (id_utilisateur), INDEX IDX_A776D11BB9D5A2 (id_parent), PRIMARY KEY (id_commentaire)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE forum_discussion (id_forum INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, id_cours INT DEFAULT NULL, date_creation DATETIME NOT NULL, type VARCHAR(20) NOT NULL, statut VARCHAR(20) NOT NULL, nombre_vues INT NOT NULL, derniere_activite DATETIME DEFAULT NULL, tags JSON DEFAULT NULL, regles_moderation LONGTEXT DEFAULT NULL, image_couverture_url VARCHAR(255) DEFAULT NULL, likes INT NOT NULL, dislikes INT NOT NULL, signalements INT NOT NULL, est_modifie TINYINT NOT NULL, date_modification DATETIME DEFAULT NULL, id_createur INT NOT NULL, INDEX IDX_428F444AAA033611 (id_createur), PRIMARY KEY (id_forum)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE message (id INT AUTO_INCREMENT NOT NULL, objet VARCHAR(255) NOT NULL, contenu LONGTEXT NOT NULL, date_envoi DATETIME NOT NULL, date_lecture DATETIME DEFAULT NULL, statut VARCHAR(20) NOT NULL, priorite VARCHAR(20) NOT NULL, piece_jointe_url VARCHAR(255) DEFAULT NULL, categorie VARCHAR(20) NOT NULL, est_archive_expediteur TINYINT NOT NULL, est_archive_destinataire TINYINT NOT NULL, est_supprime_expediteur TINYINT NOT NULL, est_supprime_destinataire TINYINT NOT NULL, expediteur_id INT NOT NULL, destinataire_id INT NOT NULL, parent_id INT DEFAULT NULL, INDEX IDX_B6BD307F10335F61 (expediteur_id), INDEX IDX_B6BD307FA4F84F6E (destinataire_id), INDEX IDX_B6BD307F727ACA70 (parent_id), INDEX idx_message_date_envoi (date_envoi), INDEX idx_message_statut (statut), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE commentaire_forum ADD CONSTRAINT FK_A776D16BAEFFFD FOREIGN KEY (id_forum) REFERENCES forum_discussion (id_forum) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commentaire_forum ADD CONSTRAINT FK_A776D150EAE44 FOREIGN KEY (id_utilisateur) REFERENCES Utilisateur (id)');
        $this->addSql('ALTER TABLE commentaire_forum ADD CONSTRAINT FK_A776D11BB9D5A2 FOREIGN KEY (id_parent) REFERENCES commentaire_forum (id_commentaire) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE forum_discussion ADD CONSTRAINT FK_428F444AAA033611 FOREIGN KEY (id_createur) REFERENCES Utilisateur (id)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F10335F61 FOREIGN KEY (expediteur_id) REFERENCES Utilisateur (id)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FA4F84F6E FOREIGN KEY (destinataire_id) REFERENCES Utilisateur (id)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F727ACA70 FOREIGN KEY (parent_id) REFERENCES message (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE sessions CHANGE sess_data sess_data BLOB NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commentaire_forum DROP FOREIGN KEY FK_A776D16BAEFFFD');
        $this->addSql('ALTER TABLE commentaire_forum DROP FOREIGN KEY FK_A776D150EAE44');
        $this->addSql('ALTER TABLE commentaire_forum DROP FOREIGN KEY FK_A776D11BB9D5A2');
        $this->addSql('ALTER TABLE forum_discussion DROP FOREIGN KEY FK_428F444AAA033611');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307F10335F61');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307FA4F84F6E');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307F727ACA70');
        $this->addSql('DROP TABLE commentaire_forum');
        $this->addSql('DROP TABLE forum_discussion');
        $this->addSql('DROP TABLE message');
        $this->addSql('ALTER TABLE sessions CHANGE sess_data sess_data BLOB NOT NULL');
    }
}

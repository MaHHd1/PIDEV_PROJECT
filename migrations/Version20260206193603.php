<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260206193603 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, username VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE message ADD objet VARCHAR(255) NOT NULL, ADD contenu LONGTEXT NOT NULL, ADD date_envoi DATETIME NOT NULL, ADD date_lecture DATETIME DEFAULT NULL, ADD statut VARCHAR(20) NOT NULL, ADD priorite VARCHAR(20) NOT NULL, ADD piece_jointe_url VARCHAR(255) DEFAULT NULL, ADD categorie VARCHAR(20) NOT NULL, ADD est_archive_expediteur TINYINT(1) NOT NULL, ADD est_archive_destinataire TINYINT(1) NOT NULL, ADD expediteur_id INT NOT NULL, ADD destinataire_id INT NOT NULL, ADD parent_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F10335F61 FOREIGN KEY (expediteur_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FA4F84F6E FOREIGN KEY (destinataire_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F727ACA70 FOREIGN KEY (parent_id) REFERENCES message (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_B6BD307F10335F61 ON message (expediteur_id)');
        $this->addSql('CREATE INDEX IDX_B6BD307FA4F84F6E ON message (destinataire_id)');
        $this->addSql('CREATE INDEX IDX_B6BD307F727ACA70 ON message (parent_id)');
        $this->addSql('CREATE INDEX idx_message_date_envoi ON message (date_envoi)');
        $this->addSql('CREATE INDEX idx_message_statut ON message (statut)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE user');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307F10335F61');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307FA4F84F6E');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307F727ACA70');
        $this->addSql('DROP INDEX IDX_B6BD307F10335F61 ON message');
        $this->addSql('DROP INDEX IDX_B6BD307FA4F84F6E ON message');
        $this->addSql('DROP INDEX IDX_B6BD307F727ACA70 ON message');
        $this->addSql('DROP INDEX idx_message_date_envoi ON message');
        $this->addSql('DROP INDEX idx_message_statut ON message');
        $this->addSql('ALTER TABLE message DROP objet, DROP contenu, DROP date_envoi, DROP date_lecture, DROP statut, DROP priorite, DROP piece_jointe_url, DROP categorie, DROP est_archive_expediteur, DROP est_archive_destinataire, DROP expediteur_id, DROP destinataire_id, DROP parent_id');
    }
}

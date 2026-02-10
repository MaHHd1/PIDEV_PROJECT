<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260208202501 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE participation_evenement (id INT AUTO_INCREMENT NOT NULL, statut VARCHAR(50) NOT NULL, date_inscription DATETIME NOT NULL, heure_arrivee DATETIME DEFAULT NULL, heure_depart DATETIME DEFAULT NULL, feedback_note INT DEFAULT NULL, feedback_commentaire LONGTEXT DEFAULT NULL, evenement_id INT NOT NULL, utilisateur_id INT NOT NULL, INDEX IDX_65A14675FD02F13 (evenement_id), INDEX IDX_65A14675FB88E14F (utilisateur_id), UNIQUE INDEX unique_participation (evenement_id, utilisateur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE utilisateur (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE participation_evenement ADD CONSTRAINT FK_65A14675FD02F13 FOREIGN KEY (evenement_id) REFERENCES evenement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE participation_evenement ADD CONSTRAINT FK_65A14675FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE evenement ADD titre VARCHAR(255) NOT NULL, ADD description LONGTEXT DEFAULT NULL, ADD type_evenement VARCHAR(50) NOT NULL, ADD date_debut DATETIME NOT NULL, ADD date_fin DATETIME NOT NULL, ADD lieu VARCHAR(255) DEFAULT NULL, ADD capacite_max INT DEFAULT NULL, ADD statut VARCHAR(50) NOT NULL, ADD visibilite VARCHAR(50) NOT NULL, ADD createur_id INT NOT NULL');
        $this->addSql('ALTER TABLE evenement ADD CONSTRAINT FK_B26681E73A201E5 FOREIGN KEY (createur_id) REFERENCES utilisateur (id)');
        $this->addSql('CREATE INDEX IDX_B26681E73A201E5 ON evenement (createur_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE participation_evenement DROP FOREIGN KEY FK_65A14675FD02F13');
        $this->addSql('ALTER TABLE participation_evenement DROP FOREIGN KEY FK_65A14675FB88E14F');
        $this->addSql('DROP TABLE participation_evenement');
        $this->addSql('DROP TABLE utilisateur');
        $this->addSql('ALTER TABLE evenement DROP FOREIGN KEY FK_B26681E73A201E5');
        $this->addSql('DROP INDEX IDX_B26681E73A201E5 ON evenement');
        $this->addSql('ALTER TABLE evenement DROP titre, DROP description, DROP type_evenement, DROP date_debut, DROP date_fin, DROP lieu, DROP capacite_max, DROP statut, DROP visibilite, DROP createur_id');
    }
}

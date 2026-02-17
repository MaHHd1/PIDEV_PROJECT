<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260205133652 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE Administrateur (departement VARCHAR(100) NOT NULL, fonction VARCHAR(100) NOT NULL, telephone VARCHAR(20) DEFAULT NULL, date_nomination DATETIME NOT NULL, actif TINYINT NOT NULL, id INT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE Enseignant (matricule_enseignant VARCHAR(50) NOT NULL, diplome VARCHAR(100) NOT NULL, specialite VARCHAR(100) NOT NULL, annees_experience INT NOT NULL, type_contrat VARCHAR(50) NOT NULL, taux_horaire NUMERIC(10, 2) DEFAULT NULL, disponibilites LONGTEXT DEFAULT NULL, statut VARCHAR(20) NOT NULL, id INT NOT NULL, UNIQUE INDEX UNIQ_CEFA2C712CC00DC0 (matricule_enseignant), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE Etudiant (matricule VARCHAR(50) NOT NULL, niveau_etude VARCHAR(100) NOT NULL, specialisation VARCHAR(100) NOT NULL, date_naissance DATE NOT NULL, telephone VARCHAR(20) NOT NULL, adresse LONGTEXT NOT NULL, date_inscription DATETIME NOT NULL, statut VARCHAR(20) NOT NULL, id INT NOT NULL, UNIQUE INDEX UNIQ_880840B512B2DC9C (matricule), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE Utilisateur (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, prenom VARCHAR(100) NOT NULL, email VARCHAR(180) NOT NULL, motDePasse VARCHAR(255) NOT NULL, date_creation DATETIME NOT NULL, reset_token VARCHAR(255) DEFAULT NULL, reset_token_expires_at DATETIME DEFAULT NULL, last_login DATETIME DEFAULT NULL, typeUtilisateur VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_9B80EC64E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE Administrateur ADD CONSTRAINT FK_FF8F2A30BF396750 FOREIGN KEY (id) REFERENCES Utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE Enseignant ADD CONSTRAINT FK_CEFA2C71BF396750 FOREIGN KEY (id) REFERENCES Utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE Etudiant ADD CONSTRAINT FK_880840B5BF396750 FOREIGN KEY (id) REFERENCES Utilisateur (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE Administrateur DROP FOREIGN KEY FK_FF8F2A30BF396750');
        $this->addSql('ALTER TABLE Enseignant DROP FOREIGN KEY FK_CEFA2C71BF396750');
        $this->addSql('ALTER TABLE Etudiant DROP FOREIGN KEY FK_880840B5BF396750');
        $this->addSql('DROP TABLE Administrateur');
        $this->addSql('DROP TABLE Enseignant');
        $this->addSql('DROP TABLE Etudiant');
        $this->addSql('DROP TABLE Utilisateur');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260210121619 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE score DROP INDEX IDX_32993751801F9DE6, ADD UNIQUE INDEX UNIQ_32993751801F9DE6 (soumission_id)');
        $this->addSql('ALTER TABLE score CHANGE date_correction date_correction DATETIME NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE score DROP INDEX UNIQ_32993751801F9DE6, ADD INDEX IDX_32993751801F9DE6 (soumission_id)');
        $this->addSql('ALTER TABLE score CHANGE date_correction date_correction DATETIME DEFAULT NULL');
    }
}

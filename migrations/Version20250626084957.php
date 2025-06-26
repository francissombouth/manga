<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250626084957 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE oeuvre ADD statut VARCHAR(50) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE oeuvre ADD original_language TEXT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE oeuvre ADD demographic TEXT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE oeuvre ADD content_rating TEXT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE oeuvre ADD alternative_titles JSON DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE oeuvre ADD last_volume TEXT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE oeuvre ADD last_chapter TEXT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE oeuvre ADD year INT DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE oeuvre DROP statut
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE oeuvre DROP original_language
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE oeuvre DROP demographic
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE oeuvre DROP content_rating
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE oeuvre DROP alternative_titles
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE oeuvre DROP last_volume
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE oeuvre DROP last_chapter
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE oeuvre DROP year
        SQL);
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250811115054 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE oeuvre_view (id SERIAL NOT NULL, oeuvre_id INT NOT NULL, user_id INT DEFAULT NULL, viewed_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, ip_address VARCHAR(45) DEFAULT NULL, user_agent VARCHAR(500) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_95A71C888194DE8 ON oeuvre_view (oeuvre_id)');
        $this->addSql('CREATE INDEX IDX_95A71C8A76ED395 ON oeuvre_view (user_id)');
        $this->addSql('CREATE INDEX idx_oeuvre_view_date ON oeuvre_view (viewed_at)');
        $this->addSql('CREATE INDEX idx_oeuvre_view_oeuvre_date ON oeuvre_view (oeuvre_id, viewed_at)');
        $this->addSql('CREATE INDEX idx_oeuvre_view_user_date ON oeuvre_view (user_id, viewed_at)');
        $this->addSql('COMMENT ON COLUMN oeuvre_view.viewed_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE oeuvre_view ADD CONSTRAINT FK_95A71C888194DE8 FOREIGN KEY (oeuvre_id) REFERENCES oeuvre (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE oeuvre_view ADD CONSTRAINT FK_95A71C8A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE oeuvre_view DROP CONSTRAINT FK_95A71C888194DE8');
        $this->addSql('ALTER TABLE oeuvre_view DROP CONSTRAINT FK_95A71C8A76ED395');
        $this->addSql('DROP TABLE oeuvre_view');
    }
}

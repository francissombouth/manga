<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250702094738 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE commentaire_like (id SERIAL NOT NULL, user_id INT NOT NULL, commentaire_id INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_459B84E1A76ED395 ON commentaire_like (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_459B84E1BA9CD190 ON commentaire_like (commentaire_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX unique_user_commentaire ON commentaire_like (user_id, commentaire_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN commentaire_like.created_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE oeuvre_note (id SERIAL NOT NULL, user_id INT NOT NULL, oeuvre_id INT NOT NULL, note INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_381A2052A76ED395 ON oeuvre_note (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_381A205288194DE8 ON oeuvre_note (oeuvre_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX unique_user_oeuvre_note ON oeuvre_note (user_id, oeuvre_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN oeuvre_note.created_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN oeuvre_note.updated_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commentaire_like ADD CONSTRAINT FK_459B84E1A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commentaire_like ADD CONSTRAINT FK_459B84E1BA9CD190 FOREIGN KEY (commentaire_id) REFERENCES commentaire (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE oeuvre_note ADD CONSTRAINT FK_381A2052A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE oeuvre_note ADD CONSTRAINT FK_381A205288194DE8 FOREIGN KEY (oeuvre_id) REFERENCES oeuvre (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commentaire DROP note
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commentaire_like DROP CONSTRAINT FK_459B84E1A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commentaire_like DROP CONSTRAINT FK_459B84E1BA9CD190
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE oeuvre_note DROP CONSTRAINT FK_381A2052A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE oeuvre_note DROP CONSTRAINT FK_381A205288194DE8
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE commentaire_like
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE oeuvre_note
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commentaire ADD note INT NOT NULL
        SQL);
    }
}

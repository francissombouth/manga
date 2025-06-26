<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250617081209 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE auteur (id SERIAL NOT NULL, nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) DEFAULT NULL, nom_plume VARCHAR(255) DEFAULT NULL, biographie TEXT DEFAULT NULL, date_naissance DATE DEFAULT NULL, nationalite VARCHAR(100) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN auteur.created_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN auteur.updated_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE chapitre (id SERIAL NOT NULL, oeuvre_id INT NOT NULL, titre VARCHAR(255) NOT NULL, ordre INT NOT NULL, resume TEXT DEFAULT NULL, pages JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_8C62B02588194DE8 ON chapitre (oeuvre_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN chapitre.created_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN chapitre.updated_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE collection_user (id SERIAL NOT NULL, user_id INT NOT NULL, oeuvre_id INT NOT NULL, date_ajout TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, note_personnelle TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_C7E4FAA7A76ED395 ON collection_user (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_C7E4FAA788194DE8 ON collection_user (oeuvre_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN collection_user.date_ajout IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN collection_user.created_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE oeuvre (id SERIAL NOT NULL, auteur_id INT NOT NULL, titre VARCHAR(255) NOT NULL, type VARCHAR(50) NOT NULL, couverture VARCHAR(255) DEFAULT NULL, resume TEXT DEFAULT NULL, date_publication DATE DEFAULT NULL, isbn VARCHAR(20) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_35FE2EFE60BB6FE6 ON oeuvre (auteur_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN oeuvre.created_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN oeuvre.updated_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE oeuvre_tag (oeuvre_id INT NOT NULL, tag_id INT NOT NULL, PRIMARY KEY(oeuvre_id, tag_id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_C604AC1788194DE8 ON oeuvre_tag (oeuvre_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_C604AC17BAD26311 ON oeuvre_tag (tag_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE statut (id SERIAL NOT NULL, user_id INT NOT NULL, oeuvre_id INT NOT NULL, nom VARCHAR(50) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_E564F0BFA76ED395 ON statut (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_E564F0BF88194DE8 ON statut (oeuvre_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN statut.created_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN statut.updated_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE tag (id SERIAL NOT NULL, nom VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_389B7836C6E55B5 ON tag (nom)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN tag.created_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE "user" (id SERIAL NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, nom VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON "user" (email)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN "user".created_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN "user".updated_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE messenger_messages (id BIGSERIAL NOT NULL, body TEXT NOT NULL, headers TEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, available_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN messenger_messages.created_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN messenger_messages.available_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN messenger_messages.delivered_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE OR REPLACE FUNCTION notify_messenger_messages() RETURNS TRIGGER AS $$
                BEGIN
                    PERFORM pg_notify('messenger_messages', NEW.queue_name::text);
                    RETURN NEW;
                END;
            $$ LANGUAGE plpgsql;
        SQL);
        $this->addSql(<<<'SQL'
            DROP TRIGGER IF EXISTS notify_trigger ON messenger_messages;
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TRIGGER notify_trigger AFTER INSERT OR UPDATE ON messenger_messages FOR EACH ROW EXECUTE PROCEDURE notify_messenger_messages();
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE chapitre ADD CONSTRAINT FK_8C62B02588194DE8 FOREIGN KEY (oeuvre_id) REFERENCES oeuvre (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE collection_user ADD CONSTRAINT FK_C7E4FAA7A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE collection_user ADD CONSTRAINT FK_C7E4FAA788194DE8 FOREIGN KEY (oeuvre_id) REFERENCES oeuvre (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE oeuvre ADD CONSTRAINT FK_35FE2EFE60BB6FE6 FOREIGN KEY (auteur_id) REFERENCES auteur (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE oeuvre_tag ADD CONSTRAINT FK_C604AC1788194DE8 FOREIGN KEY (oeuvre_id) REFERENCES oeuvre (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE oeuvre_tag ADD CONSTRAINT FK_C604AC17BAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE statut ADD CONSTRAINT FK_E564F0BFA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE statut ADD CONSTRAINT FK_E564F0BF88194DE8 FOREIGN KEY (oeuvre_id) REFERENCES oeuvre (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE chapitre DROP CONSTRAINT FK_8C62B02588194DE8
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE collection_user DROP CONSTRAINT FK_C7E4FAA7A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE collection_user DROP CONSTRAINT FK_C7E4FAA788194DE8
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE oeuvre DROP CONSTRAINT FK_35FE2EFE60BB6FE6
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE oeuvre_tag DROP CONSTRAINT FK_C604AC1788194DE8
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE oeuvre_tag DROP CONSTRAINT FK_C604AC17BAD26311
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE statut DROP CONSTRAINT FK_E564F0BFA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE statut DROP CONSTRAINT FK_E564F0BF88194DE8
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE auteur
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE chapitre
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE collection_user
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE oeuvre
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE oeuvre_tag
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE statut
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE tag
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE "user"
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE messenger_messages
        SQL);
    }
}

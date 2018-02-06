<?php declare(strict_types = 1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180206232943 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE slack_link_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE slack_user_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE slack_link (id INT NOT NULL, user_id INT DEFAULT NULL, channel VARCHAR(100) DEFAULT NULL, url VARCHAR(255) NOT NULL, real_url VARCHAR(255) DEFAULT NULL, title VARCHAR(255) DEFAULT NULL, image VARCHAR(255) DEFAULT NULL, tags VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, posted_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, exported_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_2D24336EA76ED395 ON slack_link (user_id)');
        $this->addSql('CREATE TABLE slack_user (id INT NOT NULL, slack_id VARCHAR(100) NOT NULL, username VARCHAR(100) NOT NULL, real_name VARCHAR(100) NOT NULL, avatar VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_961B7CD663F6D2C9 ON slack_user (slack_id)');
        $this->addSql('ALTER TABLE slack_link ADD CONSTRAINT FK_2D24336EA76ED395 FOREIGN KEY (user_id) REFERENCES slack_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE slack_link DROP CONSTRAINT FK_2D24336EA76ED395');
        $this->addSql('DROP SEQUENCE slack_link_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE slack_user_id_seq CASCADE');
        $this->addSql('DROP TABLE slack_link');
        $this->addSql('DROP TABLE slack_user');
    }
}

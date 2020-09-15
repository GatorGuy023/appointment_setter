<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200915025035 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE company (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, code CHAR(36) NOT NULL --(DC2Type:guid)
        , name VARCHAR(255) NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4FBF094F77153098 ON company (code)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4FBF094F5E237E06 ON company (name)');
        $this->addSql('CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, company_id INTEGER NOT NULL, code CHAR(36) NOT NULL --(DC2Type:guid)
        , username VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, roles CLOB NOT NULL --(DC2Type:array)
        , fname VARCHAR(255) NOT NULL, lname VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D64977153098 ON user (code)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649F85E0677 ON user (username)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON user (email)');
        $this->addSql('CREATE INDEX IDX_8D93D649979B1AD6 ON user (company_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE company');
        $this->addSql('DROP TABLE user');
    }
}

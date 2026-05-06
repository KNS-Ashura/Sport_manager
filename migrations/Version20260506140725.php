<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260506140725 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__sport_match AS SELECT id, match_date, score_player1, score_player2, status, tournament_id, player1_id, player2_id FROM sport_match');
        $this->addSql('DROP TABLE sport_match');
        $this->addSql('CREATE TABLE sport_match (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, match_date DATETIME NOT NULL, score_player1 INTEGER DEFAULT NULL, score_player2 INTEGER DEFAULT NULL, status VARCHAR(255) NOT NULL, tournament_id INTEGER NOT NULL, player1_id INTEGER NOT NULL, player2_id INTEGER NOT NULL, CONSTRAINT FK_CE27A41C33D1A3E7 FOREIGN KEY (tournament_id) REFERENCES tournament (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_CE27A41CC0990423 FOREIGN KEY (player1_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_CE27A41CD22CABCD FOREIGN KEY (player2_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO sport_match (id, match_date, score_player1, score_player2, status, tournament_id, player1_id, player2_id) SELECT id, match_date, score_player1, score_player2, status, tournament_id, player1_id, player2_id FROM __temp__sport_match');
        $this->addSql('DROP TABLE __temp__sport_match');
        $this->addSql('CREATE INDEX IDX_CE27A41CD22CABCD ON sport_match (player2_id)');
        $this->addSql('CREATE INDEX IDX_CE27A41CC0990423 ON sport_match (player1_id)');
        $this->addSql('CREATE INDEX IDX_CE27A41C33D1A3E7 ON sport_match (tournament_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__sport_match AS SELECT id, match_date, score_player1, score_player2, status, tournament_id, player1_id, player2_id FROM sport_match');
        $this->addSql('DROP TABLE sport_match');
        $this->addSql('CREATE TABLE sport_match (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, match_date DATETIME NOT NULL, score_player1 INTEGER NOT NULL, score_player2 INTEGER NOT NULL, status VARCHAR(255) NOT NULL, tournament_id INTEGER NOT NULL, player1_id INTEGER NOT NULL, player2_id INTEGER NOT NULL, CONSTRAINT FK_CE27A41C33D1A3E7 FOREIGN KEY (tournament_id) REFERENCES tournament (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_CE27A41CC0990423 FOREIGN KEY (player1_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_CE27A41CD22CABCD FOREIGN KEY (player2_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO sport_match (id, match_date, score_player1, score_player2, status, tournament_id, player1_id, player2_id) SELECT id, match_date, score_player1, score_player2, status, tournament_id, player1_id, player2_id FROM __temp__sport_match');
        $this->addSql('DROP TABLE __temp__sport_match');
        $this->addSql('CREATE INDEX IDX_CE27A41C33D1A3E7 ON sport_match (tournament_id)');
        $this->addSql('CREATE INDEX IDX_CE27A41CC0990423 ON sport_match (player1_id)');
        $this->addSql('CREATE INDEX IDX_CE27A41CD22CABCD ON sport_match (player2_id)');
    }
}

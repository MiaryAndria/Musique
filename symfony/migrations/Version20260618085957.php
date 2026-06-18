<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260618085957 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE t_song_album (song_id INT NOT NULL, album_id INT NOT NULL, PRIMARY KEY (song_id, album_id))');
        $this->addSql('CREATE INDEX IDX_A069C676A0BDB2F3 ON t_song_album (song_id)');
        $this->addSql('CREATE INDEX IDX_A069C6761137ABCF ON t_song_album (album_id)');
        $this->addSql('ALTER TABLE t_song_album ADD CONSTRAINT FK_A069C676A0BDB2F3 FOREIGN KEY (song_id) REFERENCES song (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE t_song_album ADD CONSTRAINT FK_A069C6761137ABCF FOREIGN KEY (album_id) REFERENCES album (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE song DROP CONSTRAINT fk_33edeea11137abcf');
        $this->addSql('DROP INDEX idx_33edeea11137abcf');
        $this->addSql('ALTER TABLE song DROP album_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE t_song_album DROP CONSTRAINT FK_A069C676A0BDB2F3');
        $this->addSql('ALTER TABLE t_song_album DROP CONSTRAINT FK_A069C6761137ABCF');
        $this->addSql('DROP TABLE t_song_album');
        $this->addSql('ALTER TABLE song ADD album_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE song ADD CONSTRAINT fk_33edeea11137abcf FOREIGN KEY (album_id) REFERENCES album (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_33edeea11137abcf ON song (album_id)');
    }
}

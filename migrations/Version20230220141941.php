<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230220141941 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE `order` (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(255) NOT NULL, creation_date DATETIME NOT NULL, address LONGTEXT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reservation (id INT AUTO_INCREMENT NOT NULL, client_order_id INT DEFAULT NULL, product_id INT DEFAULT NULL, quantity INT NOT NULL, creation_date DATETIME NOT NULL, INDEX IDX_42C84955A3795DFD (client_order_id), INDEX IDX_42C849554584665A (product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C84955A3795DFD FOREIGN KEY (client_order_id) REFERENCES `order` (id)');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C849554584665A FOREIGN KEY (product_id) REFERENCES product (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C84955A3795DFD');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C849554584665A');
        $this->addSql('DROP TABLE `order`');
        $this->addSql('DROP TABLE reservation');
    }
}

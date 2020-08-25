<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200721230454 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE `character` (id INT UNSIGNED AUTO_INCREMENT NOT NULL, user_id INT UNSIGNED NOT NULL, faction_id INT UNSIGNED NOT NULL, playable_class_id INT UNSIGNED NOT NULL, name VARCHAR(255) CHARACTER SET latin1 NOT NULL COLLATE `latin1_swedish_ci`, INDEX fk_character_factions1_idx (faction_id), INDEX fk_character_playable_classes1_idx (playable_class_id), INDEX fk_character_user1_idx (user_id), UNIQUE INDEX id_UNIQUE (id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE character_profession (character_id INT UNSIGNED NOT NULL, profession_id INT UNSIGNED NOT NULL, current_skill_level INT UNSIGNED DEFAULT 1 NOT NULL, INDEX fk_character_has_profession_character1_idx (character_id), INDEX fk_character_has_profession_profession1_idx (profession_id), PRIMARY KEY(character_id, profession_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE character_recipe (character_id INT UNSIGNED NOT NULL, recipe_id INT UNSIGNED NOT NULL, INDEX fk_character_has_recipe_character1_idx (character_id), INDEX fk_character_has_recipe_recipe1_idx (recipe_id), PRIMARY KEY(character_id, recipe_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE craftable_item (id INT UNSIGNED AUTO_INCREMENT NOT NULL, name VARCHAR(255) CHARACTER SET latin1 NOT NULL COLLATE `latin1_swedish_ci`, item_link_url VARCHAR(255) CHARACTER SET latin1 NOT NULL COLLATE `latin1_swedish_ci`, icon_link_url VARCHAR(255) CHARACTER SET latin1 NOT NULL COLLATE `latin1_swedish_ci`, item_slot VARCHAR(255) CHARACTER SET latin1 DEFAULT \'Not equipable\' NOT NULL COLLATE `latin1_swedish_ci`, sell_price INT UNSIGNED DEFAULT NULL, UNIQUE INDEX id_UNIQUE (id), UNIQUE INDEX item_link_url_UNIQUE (item_link_url), UNIQUE INDEX name_UNIQUE (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE faction (id INT UNSIGNED AUTO_INCREMENT NOT NULL, name VARCHAR(255) CHARACTER SET latin1 NOT NULL COLLATE `latin1_swedish_ci`, UNIQUE INDEX id_UNIQUE (id), UNIQUE INDEX name_UNIQUE (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE location (id INT UNSIGNED AUTO_INCREMENT NOT NULL, name VARCHAR(255) CHARACTER SET latin1 NOT NULL COLLATE `latin1_swedish_ci`, location_link_url VARCHAR(255) CHARACTER SET latin1 NOT NULL COLLATE `latin1_swedish_ci`, faction_status VARCHAR(255) CHARACTER SET latin1 NOT NULL COLLATE `latin1_swedish_ci`, UNIQUE INDEX id_UNIQUE (id), UNIQUE INDEX location_link_url_UNIQUE (location_link_url), UNIQUE INDEX name_UNIQUE (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE playable_class (id INT UNSIGNED AUTO_INCREMENT NOT NULL, name VARCHAR(255) CHARACTER SET latin1 NOT NULL COLLATE `latin1_swedish_ci`, UNIQUE INDEX id_UNIQUE (id), UNIQUE INDEX name_UNIQUE (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE profession (id INT UNSIGNED AUTO_INCREMENT NOT NULL, name VARCHAR(255) CHARACTER SET latin1 NOT NULL COLLATE `latin1_swedish_ci`, profession_link_url VARCHAR(255) CHARACTER SET latin1 NOT NULL COLLATE `latin1_swedish_ci`, icon_link_url VARCHAR(255) CHARACTER SET latin1 NOT NULL COLLATE `latin1_swedish_ci`, is_main_profession TINYINT(1) DEFAULT \'0\' NOT NULL, UNIQUE INDEX id_UNIQUE (id), UNIQUE INDEX name_UNIQUE (name), UNIQUE INDEX profession_link_url_UNIQUE (profession_link_url), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE reagent (id INT UNSIGNED AUTO_INCREMENT NOT NULL, name VARCHAR(255) CHARACTER SET latin1 NOT NULL COLLATE `latin1_swedish_ci`, item_link_url VARCHAR(255) CHARACTER SET latin1 NOT NULL COLLATE `latin1_swedish_ci`, icon_link_url VARCHAR(255) CHARACTER SET latin1 NOT NULL COLLATE `latin1_swedish_ci`, UNIQUE INDEX id_UNIQUE (id), UNIQUE INDEX item_link_url_UNIQUE (item_link_url), UNIQUE INDEX name_UNIQUE (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE reagent_source (reagent_id INT UNSIGNED NOT NULL, source_id INT UNSIGNED NOT NULL, INDEX fk_reagent_has_source_reagent1_idx (reagent_id), INDEX fk_reagent_has_source_source1_idx (source_id), PRIMARY KEY(reagent_id, source_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE reagent_vendor (reagent_id INT UNSIGNED NOT NULL, vendor_id INT UNSIGNED NOT NULL, buy_price INT UNSIGNED DEFAULT 1 NOT NULL, INDEX fk_reagents_has_vendors_reagents1_idx (reagent_id), INDEX fk_reagents_has_vendors_vendors1_idx (vendor_id), PRIMARY KEY(reagent_id, vendor_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE recipe (id INT UNSIGNED AUTO_INCREMENT NOT NULL, recipe_item_id INT UNSIGNED DEFAULT NULL, craftable_item_id INT UNSIGNED DEFAULT NULL, profession_id INT UNSIGNED NOT NULL, name VARCHAR(255) CHARACTER SET latin1 NOT NULL COLLATE `latin1_swedish_ci`, difficulty_requirement INT UNSIGNED DEFAULT 1 NOT NULL, difficulty_category_1 INT UNSIGNED DEFAULT 1 NOT NULL, difficulty_category_2 INT UNSIGNED DEFAULT 1 NOT NULL, difficulty_category_3 INT UNSIGNED DEFAULT 1 NOT NULL, difficulty_category_4 INT UNSIGNED DEFAULT 1 NOT NULL, recipe_link_url VARCHAR(255) CHARACTER SET latin1 NOT NULL COLLATE `latin1_swedish_ci`, icon_link_url VARCHAR(255) CHARACTER SET latin1 NOT NULL COLLATE `latin1_swedish_ci`, minimum_amount_created INT UNSIGNED DEFAULT 1 NOT NULL, maximum_amount_created INT UNSIGNED DEFAULT 1 NOT NULL, training_cost INT UNSIGNED DEFAULT NULL, INDEX fk_recipe_craftable_item1_idx (craftable_item_id), INDEX fk_recipe_profession1_idx (profession_id), INDEX fk_recipe_recipe_item1_idx (recipe_item_id), UNIQUE INDEX id_UNIQUE (id), UNIQUE INDEX recipe_link_url_UNIQUE (recipe_link_url), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE recipe_item (id INT UNSIGNED AUTO_INCREMENT NOT NULL, profession_id INT UNSIGNED NOT NULL, name VARCHAR(255) CHARACTER SET latin1 NOT NULL COLLATE `latin1_swedish_ci`, item_link_url VARCHAR(255) CHARACTER SET latin1 NOT NULL COLLATE `latin1_swedish_ci`, icon_link_url VARCHAR(255) CHARACTER SET latin1 NOT NULL COLLATE `latin1_swedish_ci`, required_skill_level INT UNSIGNED DEFAULT 1 NOT NULL, INDEX fk_recipe_item_profession1_idx (profession_id), UNIQUE INDEX id_UNIQUE (id), UNIQUE INDEX item_link_url_UNIQUE (item_link_url), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE recipe_reagent (recipe_id INT UNSIGNED NOT NULL, reagent_id INT UNSIGNED NOT NULL, amount INT UNSIGNED DEFAULT 1 NOT NULL, INDEX fk_recipe_has_ingredient_ingredient1_idx (reagent_id), INDEX fk_recipe_has_ingredient_recipe1_idx (recipe_id), PRIMARY KEY(recipe_id, reagent_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE recipe_trainer (recipe_id INT UNSIGNED NOT NULL, trainer_id INT UNSIGNED NOT NULL, INDEX fk_recipe_has_trainer_recipe1_idx (recipe_id), INDEX fk_recipe_has_trainer_trainer1_idx (trainer_id), PRIMARY KEY(recipe_id, trainer_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE source (id INT UNSIGNED AUTO_INCREMENT NOT NULL, name VARCHAR(255) CHARACTER SET latin1 NOT NULL COLLATE `latin1_swedish_ci`, UNIQUE INDEX id_UNIQUE (id), UNIQUE INDEX name_UNIQUE (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE trainer (id INT UNSIGNED AUTO_INCREMENT NOT NULL, location_id INT UNSIGNED DEFAULT NULL, name VARCHAR(255) CHARACTER SET latin1 NOT NULL COLLATE `latin1_swedish_ci`, trainer_link_url VARCHAR(255) CHARACTER SET latin1 NOT NULL COLLATE `latin1_swedish_ci`, reaction_to_alliance VARCHAR(255) CHARACTER SET latin1 DEFAULT \'Hostile\' NOT NULL COLLATE `latin1_swedish_ci`, reaction_to_horde VARCHAR(255) CHARACTER SET latin1 DEFAULT \'Hostile\' NOT NULL COLLATE `latin1_swedish_ci`, INDEX fk_trainer_locations1_idx (location_id), UNIQUE INDEX id_UNIQUE (id), UNIQUE INDEX name_UNIQUE (name), UNIQUE INDEX trainer_link_url_UNIQUE (trainer_link_url), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE trainer_profession (trainer_id INT UNSIGNED NOT NULL, profession_id INT UNSIGNED NOT NULL, INDEX fk_trainer_has_profession_profession1_idx (profession_id), INDEX fk_trainer_has_profession_trainer1_idx (trainer_id), PRIMARY KEY(trainer_id, profession_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE user (id INT UNSIGNED AUTO_INCREMENT NOT NULL, username VARCHAR(255) CHARACTER SET latin1 NOT NULL COLLATE `latin1_swedish_ci`, email VARCHAR(255) CHARACTER SET latin1 NOT NULL COLLATE `latin1_swedish_ci`, password VARCHAR(255) CHARACTER SET latin1 NOT NULL COLLATE `latin1_swedish_ci`, roles JSON NOT NULL, UNIQUE INDEX email_UNIQUE (email), UNIQUE INDEX id_UNIQUE (id), UNIQUE INDEX username_UNIQUE (username), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE vendor (id INT UNSIGNED AUTO_INCREMENT NOT NULL, name VARCHAR(255) CHARACTER SET latin1 NOT NULL COLLATE `latin1_swedish_ci`, vendor_link_url VARCHAR(255) CHARACTER SET latin1 NOT NULL COLLATE `latin1_swedish_ci`, reaction_to_alliance VARCHAR(255) CHARACTER SET latin1 DEFAULT \'Hostile\' NOT NULL COLLATE `latin1_swedish_ci`, reaction_to_horde VARCHAR(255) CHARACTER SET latin1 DEFAULT \'Hostile\' NOT NULL COLLATE `latin1_swedish_ci`, UNIQUE INDEX id_UNIQUE (id), UNIQUE INDEX name_UNIQUE (name), UNIQUE INDEX vendor_link_url_UNIQUE (vendor_link_url), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE vendor_location (vendor_id INT UNSIGNED NOT NULL, location_id INT UNSIGNED NOT NULL, INDEX fk_vendor_has_location_location1_idx (location_id), INDEX fk_vendor_has_location_vendor1_idx (vendor_id), PRIMARY KEY(vendor_id, location_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE `character`');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE character_profession');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE character_recipe');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE craftable_item');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE faction');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE location');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE playable_class');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE profession');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE reagent');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE reagent_source');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE reagent_vendor');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE recipe');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE recipe_item');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE recipe_reagent');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE recipe_trainer');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE source');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE trainer');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE trainer_profession');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE user');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE vendor');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE vendor_location');
    }
}

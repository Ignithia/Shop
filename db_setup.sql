CREATE DATABASE IF NOT EXISTS `webshop`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;


CREATE TABLE IF NOT EXISTS `webshop`.`users` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(32) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `email` VARCHAR(320) NOT NULL,
    `avatar` VARCHAR(255) NOT NULL,
    `balance` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `joindate` DATETIME NOT NULL,
    `admin` BOOLEAN NOT NULL DEFAULT FALSE,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `webshop`.`category` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(30) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `webshop`.`percentage` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `percentage` INT NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `webshop`.`game` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(340) NOT NULL,
    `description` TEXT NOT NULL,
    `price` DECIMAL(10,2) NOT NULL,
    `release_date` DATE NOT NULL,
    `sale` BOOLEAN NOT NULL,
    `fk_percentage` INT NULL,
    `fk_category` INT NOT NULL,
    PRIMARY KEY (`id`),
    INDEX `game_index_0` (`fk_percentage`, `fk_category`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `webshop`.`review` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `text` TEXT NOT NULL,
    `recommended` BOOLEAN NOT NULL,
    `created_at` DATETIME NOT NULL,
    `fk_user` INT NOT NULL,
    `fk_game` INT NOT NULL,
    PRIMARY KEY (`id`),
    INDEX `review_index_0` (`fk_user`, `fk_game`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `webshop`.`screenshot` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `link` VARCHAR(255) NOT NULL,
    `fk_game` INT NOT NULL,
    PRIMARY KEY (`id`),
    INDEX `screenshot_index_0` (`fk_game`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `webshop`.`wishlist` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `rank` INT NOT NULL,
    `added_at` DATE NOT NULL,
    `fk_user` INT NOT NULL,
    `fk_game` INT NOT NULL,
    PRIMARY KEY (`id`),
    INDEX `wishlist_index_0` (`fk_user`, `fk_game`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `webshop`.`library` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `purchased_at` DATETIME NOT NULL,
    `fk_user` INT NOT NULL,
    `fk_game` INT NOT NULL,
    PRIMARY KEY (`id`),
    INDEX `library_index_0` (`fk_user`, `fk_game`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `webshop`.`shopping_cart` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `fk_user` INT NOT NULL,
    `fk_game` INT NOT NULL,
    PRIMARY KEY (`id`),
    INDEX `shopping_cart_index_0` (`fk_user`, `fk_game`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `webshop`.`friendlist` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `fk_user_out` INT NOT NULL,
    `fk_user_in` INT NOT NULL,
    `accepted` BOOLEAN NOT NULL,
    PRIMARY KEY (`id`),
    INDEX `friendlist_index_0` (`fk_user_out`, `fk_user_in`)
) ENGINE=InnoDB;

ALTER TABLE `webshop`.`game`
  ADD CONSTRAINT `fk_game_percentage`
    FOREIGN KEY (`fk_percentage`) REFERENCES `webshop`.`percentage`(`id`)
    ON UPDATE NO ACTION ON DELETE SET NULL;

ALTER TABLE `webshop`.`game`
  ADD CONSTRAINT `fk_game_category`
    FOREIGN KEY (`fk_category`) REFERENCES `webshop`.`category`(`id`)
    ON UPDATE NO ACTION ON DELETE RESTRICT;

ALTER TABLE `webshop`.`review`
  ADD CONSTRAINT `fk_review_user`
    FOREIGN KEY (`fk_user`) REFERENCES `webshop`.`users`(`id`)
    ON UPDATE NO ACTION ON DELETE CASCADE;

ALTER TABLE `webshop`.`review`
  ADD CONSTRAINT `fk_review_game`
    FOREIGN KEY (`fk_game`) REFERENCES `webshop`.`game`(`id`)
    ON UPDATE NO ACTION ON DELETE CASCADE;

ALTER TABLE `webshop`.`screenshot`
  ADD CONSTRAINT `fk_screenshot_game`
    FOREIGN KEY (`fk_game`) REFERENCES `webshop`.`game`(`id`)
    ON UPDATE NO ACTION ON DELETE CASCADE;

ALTER TABLE `webshop`.`wishlist`
  ADD CONSTRAINT `fk_wishlist_user`
    FOREIGN KEY (`fk_user`) REFERENCES `webshop`.`users`(`id`)
    ON UPDATE NO ACTION ON DELETE CASCADE;

ALTER TABLE `webshop`.`wishlist`
  ADD CONSTRAINT `fk_wishlist_game`
    FOREIGN KEY (`fk_game`) REFERENCES `webshop`.`game`(`id`)
    ON UPDATE NO ACTION ON DELETE CASCADE;

ALTER TABLE `webshop`.`library`
  ADD CONSTRAINT `fk_library_user`
    FOREIGN KEY (`fk_user`) REFERENCES `webshop`.`users`(`id`)
    ON UPDATE NO ACTION ON DELETE CASCADE;

ALTER TABLE `webshop`.`library`
  ADD CONSTRAINT `fk_library_game`
    FOREIGN KEY (`fk_game`) REFERENCES `webshop`.`game`(`id`)
    ON UPDATE NO ACTION ON DELETE CASCADE;

ALTER TABLE `webshop`.`shopping_cart`
  ADD CONSTRAINT `fk_cart_user`
    FOREIGN KEY (`fk_user`) REFERENCES `webshop`.`users`(`id`)
    ON UPDATE NO ACTION ON DELETE CASCADE;

ALTER TABLE `webshop`.`shopping_cart`
  ADD CONSTRAINT `fk_cart_game`
    FOREIGN KEY (`fk_game`) REFERENCES `webshop`.`game`(`id`)
    ON UPDATE NO ACTION ON DELETE CASCADE;

ALTER TABLE `webshop`.`friendlist`
  ADD CONSTRAINT `fk_friend_out`
    FOREIGN KEY (`fk_user_out`) REFERENCES `webshop`.`users`(`id`)
    ON UPDATE NO ACTION ON DELETE CASCADE;

ALTER TABLE `webshop`.`friendlist`
  ADD CONSTRAINT `fk_friend_in`
    FOREIGN KEY (`fk_user_in`) REFERENCES `webshop`.`users`(`id`)
    ON UPDATE NO ACTION ON DELETE CASCADE;

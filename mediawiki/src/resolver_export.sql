CREATE TABLE `resolver_export` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `page_id` VARCHAR(45) CHARACTER SET 'utf8' COLLATE 'utf8_unicode_ci' NULL,
  `page_name` VARCHAR(255) CHARACTER SET 'utf8' COLLATE 'utf8_unicode_ci' NULL,
  `page_url` VARCHAR(255) CHARACTER SET 'utf8' COLLATE 'utf8_unicode_ci' NULL,
  PRIMARY KEY (`id`),
  INDEX `page_name_KEY` (`page_name`),
  INDEX `page_id_KEY` (`page_id`),
  INDEX `page_url_KEY` (`page_url`));
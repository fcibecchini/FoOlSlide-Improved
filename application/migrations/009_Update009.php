<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Update009 extends CI_Migration {

	function up() {
		if (!$this->db->table_exists($this->db->dbprefix('types')))
		{
			$this->db->query(
					"CREATE TABLE IF NOT EXISTS `" . $this->db->dbprefix('typehs') . "` (
                                          `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
                                          `name` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
                                          `description` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
                                          PRIMARY KEY (`id`),
                                          UNIQUE KEY `name` (`name`)
                                        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;"
			);
		}
		
		$this->db->query("
				ALTER TABLE `" . $this->db->dbprefix('comics') . "`
					ADD `typeh_id` INT( 11 ) NOT NULL AFTER `description`
		");
	}

}
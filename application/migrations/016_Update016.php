<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Update016 extends CI_Migration {

	function up() {
		$this->db->query("
				ALTER TABLE `" . $this->db->dbprefix('preferences') . "`
					MODIFY `value` text COLLATE utf8_unicode_ci NOT NULL
		");
	}

}

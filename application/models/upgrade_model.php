<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Upgrade_model extends CI_Model {

	var $github_repo;
	var $release_api;

	function __construct() {
		parent::__construct();
		$this->github_repo = 'fcibecchini/FoOlSlide-Improved';
		$this->release_api = 'https://api.github.com/repos/' . $this->github_repo . '/releases';
	}

	/**
	 * Connects to GitHub Releases to retrieve the latest published version.
	 *
	 * @param type $force forces returning the download even if FoOlSlide is up to date
	 * @return type FALSE or the download URL
	 */
	function check_latest($force = FALSE) {
		$releases = $this->fetch_github_releases();
		if ($releases === FALSE || empty($releases)) {
			return FALSE;
		}

		$available_versions = array();
		foreach ($releases as $release) {
			$mapped_release = $this->map_github_release($release);
			if ($mapped_release === FALSE) {
				continue;
			}

			$available_versions[] = $mapped_release;
		}

		if (empty($available_versions)) {
			return FALSE;
		}

		$new_versions = array();
		foreach ($available_versions as $release) {
			if (!$this->is_bigger_version(FOOLSLIDE_VERSION, $release)) {
				continue;
			}
			$new_versions[] = $release;
		}

		if (!empty($new_versions)) {
			return $new_versions;
		}

		if ($force) {
			return array($available_versions[0]);
		}

		return FALSE;
	}

	function fetch_github_releases() {
		$result = $this->request_url($this->release_api . '?per_page=10', TRUE);
		if (!$result) {
			return FALSE;
		}

		$data = json_decode($result);
		if (!is_array($data)) {
			log_message('error', 'upgrade_model fetch_github_releases(): invalid GitHub releases payload');
			return FALSE;
		}

		return $data;
	}

	function map_github_release($release) {
		if (!is_object($release)) {
			return FALSE;
		}

		if (!empty($release->draft) || !empty($release->prerelease)) {
			return FALSE;
		}

		$parsed_version = $this->parse_version_string(isset($release->tag_name) ? $release->tag_name : '');
		if ($parsed_version === FALSE) {
			return FALSE;
		}

		$parsed_version->download = isset($release->zipball_url) ? $release->zipball_url : FALSE;
		$parsed_version->direct_download = $parsed_version->download;
		$parsed_version->changelog = isset($release->body) ? $release->body : '';
		$parsed_version->release_url = isset($release->html_url) ? $release->html_url : '';
		$parsed_version->release_name = isset($release->name) ? $release->name : '';

		return $parsed_version;
	}

	function parse_version_string($string) {
		if (!is_string($string) || $string === '') {
			return FALSE;
		}

		if (!preg_match('/(\d+)\.(\d+)\.(\d+)/', $string, $matches)) {
			return FALSE;
		}

		$current = new stdClass();
		$current->version = $matches[1];
		$current->subversion = $matches[2];
		$current->subsubversion = $matches[3];
		return $current;
	}

	function request_url($url, $is_api = FALSE) {
		if (function_exists('curl_init')) {
			$this->load->library('curl');
			$this->curl->create($url);
			$this->curl->http_header('User-Agent', 'FoOlSlide-Improved Upgrader');
			if ($is_api) {
				$this->curl->http_header('Accept', 'application/vnd.github+json');
			}
			return $this->curl->execute();
		}

		$headers = array(
			'User-Agent: FoOlSlide-Improved Upgrader'
		);
		if ($is_api) {
			$headers[] = 'Accept: application/vnd.github+json';
		}

		$context = stream_context_create(array(
			'http' => array(
				'method' => 'GET',
				'header' => implode("\r\n", $headers) . "\r\n",
				'timeout' => 30,
				'ignore_errors' => TRUE
			)
		));

		$result = @file_get_contents($url, FALSE, $context);
		if ($result === FALSE) {
			return FALSE;
		}

		return $result;
	}

	/**
	 * Compares two versions and returns TRUE if second parameter is bigger than first, else FALSE
	 *
	 * @param type $maybemin
	 * @param type $maybemax
	 * @return bool
	 */
	function is_bigger_version($maybemin, $maybemax) {
		if (is_string($maybemin))
			$maybemin = $this->version_to_object($maybemin);
		if (is_string($maybemax))
			$maybemax = $this->version_to_object($maybemax);

		if ($maybemax->version > $maybemin->version ||
				($maybemax->version == $maybemin->version && $maybemax->subversion > $maybemin->subversion) ||
				($maybemax->version == $maybemin->version && $maybemax->subversion == $maybemin->subversion && $maybemax->subsubversion > $maybemin->subsubversion)) {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Converts the version from string separated by dots to object
	 *
	 * @author Woxxy
	 * @param type $string
	 * @return object
	 */
	function version_to_object($string) {
		return $this->parse_version_string($string);
	}

	/**
	 *
	 * @author Woxxy
	 * @param string $url
	 * @return bool
	 */
	function get_file($url, $direct_url = FALSE) {
		$this->clean();
		$download_url = $direct_url ? $direct_url : $url;
		$zip = $this->request_url($download_url, FALSE);
		if (!$zip) {
			log_message('error', 'upgrade_model get_file(): impossible to download the update from GitHub Releases');
			flash_notice('error', _('Can\'t get the update file from GitHub Releases. It might be a momentary problem, or a problem with your server security configuration.'));
			return FALSE;
		}

		if (!is_dir('content/cache/upgrade'))
			mkdir('content/cache/upgrade', 0777, TRUE);
		write_file('content/cache/upgrade/upgrade.zip', $zip);
		$this->load->library('unzip');
		$this->unzip->extract('content/cache/upgrade/upgrade.zip');
		$this->flatten_downloaded_release();
		return TRUE;
	}

	function flatten_downloaded_release() {
		$base_path = 'content/cache/upgrade';
		if (!is_dir($base_path)) {
			return FALSE;
		}

		$entries = scandir($base_path);
		$candidates = array();
		foreach ($entries as $entry) {
			if ($entry === '.' || $entry === '..' || $entry === 'upgrade.zip') {
				continue;
			}
			$candidates[] = $entry;
		}

		if (count($candidates) !== 1) {
			return TRUE;
		}

		$source_dir = $base_path . '/' . $candidates[0];
		if (!is_dir($source_dir)) {
			return TRUE;
		}

		$source_entries = scandir($source_dir);
		foreach ($source_entries as $entry) {
			if ($entry === '.' || $entry === '..') {
				continue;
			}
			rename($source_dir . '/' . $entry, $base_path . '/' . $entry);
		}

		rmdir($source_dir);
		return TRUE;
	}

	/**
	 * Checks files permissions before upgrading
	 *
	 * @author Woxxy
	 * @return bool
	 */
	function check_files() {
		if (!is_writable('.')) {
			return FALSE;
		}

		if (!is_writable('index.php')) {
			return FALSE;
		}

		if (!is_writable('application/models/upgrade2_model.php')) {
			return FALSE;
		}

		return TRUE;
	}

	function permissions_suggest() {
		$prob = FALSE;
		if (!is_writable('.')) {
			$whoami = FALSE;
			if ($this->_exec_enabled())
				$whoami = exec('whoami');
			if (!$whoami && is_writable('content') && function_exists('posix_getpwid')) {
				write_file('content/testing_123.txt', 'testing_123');
				$whoami = posix_getpwuid(fileowner('content/testing_123.txt'));
				$whoami = $whoami['name'];
				unlink('content/testing_123.txt');
			}
			if ($whoami != "")
				set_notice('warn', sprintf(_('The %s directory would be better if writable, in order to deliver automatic updates. Use this command in your shell if possible: %s'), FCPATH, '<br/><b><code>chown -R ' . $whoami . ' ' . FCPATH . '</code></b>'));
			else
				set_notice('warn', sprintf(_('The %s directory would be better if writable, in order to deliver automatic updates.<br/>It was impossible to determine the user running PHP. Use this command in your shell if possible: %s where www-data is an example (usually it\'s www-data or Apache)'), FCPATH, '<br/><b><code>chown -R www-data ' . FCPATH . '</code></b><br/>'));
			set_notice('warn', sprintf(_('If you can\'t do the above, you can follow the manual installation instructions from %sGitHub Releases%s.'), '<a href="https://github.com/' . $this->github_repo . '/releases">', '</a>'));
			$prob = TRUE;
		}

		if ($prob) {
			set_notice('notice', 'If you made any changes, just refresh this page to recheck the directory permissions.');
		}
	}

	function _exec_enabled() {
		$disabled = explode(',', ini_get('disable_functions'));
		return!in_array('exec', $disabled);
	}

	/**
	 * Hi, I herd you liek upgrading, so I put an update for your upgrade, so you
	 * can update the upgrade before upgrading.
	 *
	 * @author Woxxy
	 * @return bool
	 */
	function update_upgrade() {
		if (!file_exists('content/cache/upgrade/application/models/upgrade2_model.php')) {
			return FALSE;
		}

		unlink('application/models/upgrade2_model.php');
		copy('content/cache/upgrade/application/models/upgrade2_model.php', 'application/models/upgrade2_model.php');

		return TRUE;
	}

	/**
	 * Does further checking, updates the upgrade2 "stage 2" file to accomodate
	 * changes to the upgrade script, updates the version number with the one
	 * from GitHub Releases, and cleans up.
	 *
	 * @author Woxxy
	 * @return bool
	 */
	function do_upgrade() {
		if (!$this->check_files()) {
			log_message('error', 'upgrade.php:_do_upgrade() check_files() failed');
			return false;
		}

		$new_versions = $this->upgrade_model->check_latest(TRUE);
		if ($new_versions === FALSE)
			return FALSE;

		$latest = $new_versions[0];

		if (!$this->upgrade_model->get_file($latest->download, $latest->direct_download)) {
			return FALSE;
		}

		if (!$this->upgrade_model->update_upgrade()) {
			return FALSE;
		}

		$this->load->model('upgrade2_model');
		if (!$this->upgrade2_model->do_upgrade()) {
			return FALSE;
		}

		$this->db->update('preferences', array('value' => $latest->version . '.' . $latest->subversion . '.' . $latest->subsubversion), array('name' => 'fs_priv_version'));
		$this->upgrade_model->clean();

		return TRUE;
	}

	/**
	 * Cleans up the upgrade folder
	 *
	 * @author Woxxy
	 */
	function clean() {
		delete_files('content/cache/upgrade/', TRUE);
	}

}

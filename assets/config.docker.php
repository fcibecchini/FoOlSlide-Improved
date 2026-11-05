<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
 * Development-only configuration used by Docker Compose.
 * Production and staging instances keep their own untracked config.php.
 */

$db['default']['dbdriver'] = 'mysqli';
$db['default']['hostname'] = 'db';
$db['default']['username'] = 'foolslide_user';
$db['default']['password'] = 'foobar';
$db['default']['database'] = 'foolslide_db';
$db['default']['dbprefix'] = 'fs_';

$config['encryption_key'] = '67117340818512659034';

$config['db_table_prefix'] = $db['default']['dbprefix'];

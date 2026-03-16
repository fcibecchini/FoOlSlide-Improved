<?php

use PHPUnit\Framework\TestCase;

class ReaderControllerTest extends TestCase
{
	private function newController()
	{
		return new ReaderTestController();
	}

	protected function setUp(): void
	{
		$GLOBALS['__test_settings'] = array();
		$GLOBALS['__test_notices'] = array();
		$GLOBALS['__test_flash_notices'] = array();
	}

	public function testIndexDelegatesToLatest()
	{
		$controller = new ReaderLatestSpyController();

		$controller->index();

		$this->assertSame(1, $controller->latestCalls);
	}

	public function testCheckAdultBuildsAdultViewWhenAdultFlagNotAccepted()
	{
		$controller = $this->newController();
		$template = new StubTemplate();
		$input = new StubInput(array('adult' => null));
		$session = new StubSession();
		$agent = new StubAgent(false);

		$controller->template = $template;
		$controller->input = $input;
		$controller->session = $session;
		$controller->agent = $agent;

		$comic = (object) array('adult' => true);

		$this->assertFalse($controller->_check_adult($comic));
		$this->assertSame('adult', $template->builtView);
		$this->assertSame($comic, $template->values['comic']);
	}

	public function testCheckAdultStoresSessionFlagWhenPostContainsTrue()
	{
		$controller = $this->newController();
		$template = new StubTemplate();
		$input = new StubInput(array('adult' => 'true'));
		$session = new StubSession();
		$agent = new StubAgent(false);

		$controller->template = $template;
		$controller->input = $input;
		$controller->session = $session;
		$controller->agent = $agent;

		$comic = (object) array('adult' => true);

		$this->assertTrue($controller->_check_adult($comic));
		$this->assertTrue($session->data['adult']);
	}

	public function testCheckAdultAllowsRobotsWithoutBuildingAdultView()
	{
		$controller = $this->newController();
		$template = new StubTemplate();
		$input = new StubInput(array('adult' => null));
		$session = new StubSession();
		$agent = new StubAgent(true);

		$controller->template = $template;
		$controller->input = $input;
		$controller->session = $session;
		$controller->agent = $agent;

		$comic = (object) array('adult' => true);

		$this->assertTrue($controller->_check_adult($comic));
		$this->assertNull($template->builtView);
	}

	public function testSearchShowsPreFormWhenQueryTooShort()
	{
		$controller = $this->newController();
		$template = new StubTemplate();
		$input = new StubInput(array('search' => 'ab'));

		$controller->template = $template;
		$controller->input = $input;

		$this->assertTrue($controller->search());
		$this->assertSame('search_pre', $template->builtView);
	}

	public function testSearchBuildsResultsViewForValidQuery()
	{
		$controller = $this->newController();
		$template = new StubTemplate();
		$input = new StubInput(array('search' => 'naruto'));

		$controller->template = $template;
		$controller->input = $input;

		$this->assertNull($controller->search());
		$this->assertSame('search', $template->builtView);
		$this->assertSame('naruto', $template->values['search']);
		$this->assertTrue($template->values['show_sidebar']);
	}

	public function testSearchFallsBackToPreFormWhenQueryThrows()
	{
		$controller = $this->newController();
		$template = new StubTemplate();
		$input = new StubInput(array('search' => 'naruto'));

		$controller->template = $template;
		$controller->input = $input;
		Comic::$throwOnIlike = true;

		$controller->search();

		$this->assertSame('search_pre', $template->builtView);
		Comic::$throwOnIlike = false;
	}

	public function testAboutBuildsViewWithConfiguredContactEmail()
	{
		$GLOBALS['__test_settings'] = array(
			'fs_about_admin_email' => 'about@example.com',
			'fs_gen_site_title' => 'Demo Site',
		);

		$controller = $this->newController();
		$template = new StubTemplate();
		$controller->template = $template;
		$controller->input = new StubInput(array());
		$controller->session = new StubSession();

		$controller->about();

		$this->assertSame('about', $template->builtView);
		$this->assertTrue($template->values['show_sidebar']);
		$this->assertSame('about@example.com', $template->values['about_contact_email']);
		$this->assertSame('', $template->values['about_contact_form']['name']);
	}

	public function testAboutHidesContactFormWhenConfiguredContactEmailMissing()
	{
		$GLOBALS['__test_settings'] = array(
			'fs_gen_site_title' => 'Demo Site',
		);

		$controller = $this->newController();
		$template = new StubTemplate();
		$controller->template = $template;
		$controller->input = new StubInput(array());
		$controller->session = new StubSession();

		$controller->about();

		$this->assertFalse($template->values['about_contact_email']);
	}

	public function testAboutContactSubmissionSendsEmailAndRedirects()
	{
		$GLOBALS['__test_settings'] = array(
			'fs_about_admin_email' => 'about@example.com',
			'fs_gen_site_title' => 'Demo Site',
		);

		$controller = $this->newController();
		$controller->template = new StubTemplate();
		$controller->input = new StubInput(array(
			'contact_name' => 'Alice',
			'contact_email' => 'alice@example.com',
			'contact_subject' => 'Help',
			'contact_message' => 'Need assistance',
			'contact_website' => '',
		));
		$controller->session = new StubSession();
		$controller->form_validation = new StubFormValidation(array(
			'contact_name' => 'Alice',
			'contact_email' => 'alice@example.com',
			'contact_subject' => 'Help',
			'contact_message' => 'Need assistance',
		), true);
		$controller->email = new StubEmail(true);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('redirect:about');

		try
		{
			$controller->about();
		}
		finally
		{
			$this->assertSame('about@example.com', $controller->email->fromAddress);
			$this->assertSame('alice@example.com', $controller->email->replyToAddress);
			$this->assertSame('about@example.com', $controller->email->toAddress);
			$this->assertStringContainsString('Help', $controller->email->subjectLine);
			$this->assertSame(1, count($GLOBALS['__test_flash_notices']));
			$this->assertArrayHasKey('about_contact_last_sent', $controller->session->data);
		}
	}

	public function testAboutContactSubmissionHonorsRateLimit()
	{
		$GLOBALS['__test_settings'] = array(
			'fs_about_admin_email' => 'about@example.com',
			'fs_gen_site_title' => 'Demo Site',
		);

		$controller = $this->newController();
		$template = new StubTemplate();
		$controller->template = $template;
		$controller->input = new StubInput(array(
			'contact_name' => 'Alice',
			'contact_email' => 'alice@example.com',
			'contact_subject' => 'Help',
			'contact_message' => 'Need assistance',
			'contact_website' => '',
		));
		$controller->session = new StubSession();
		$controller->session->set_userdata('about_contact_last_sent', time());
		$controller->form_validation = new StubFormValidation(array(), true);
		$controller->email = new StubEmail(true);

		$controller->about();

		$this->assertSame('about', $template->builtView);
		$this->assertSame(0, $controller->email->sendCalls);
		$this->assertNotEmpty($GLOBALS['__test_notices']);
		$this->assertSame('Please wait a minute before sending another message.', $GLOBALS['__test_notices'][0]['message']);
	}

	public function testSearchTagsRedirectsToTagsWhenNoPostPayload()
	{
		$controller = $this->newController();
		$input = new StubInput(array());
		$controller->input = $input;

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('redirect:tags');

		$controller->search_tags();
	}

	public function testSearchTagsRedirectsToTagsWhenTagFieldIsMissing()
	{
		$controller = $this->newController();
		$input = new StubInput(array('search' => 'test'));
		$controller->input = $input;

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('redirect:tags');

		$controller->search_tags();
	}

	public function testSearchTagsRedirectsToSingleTagPage()
	{
		$controller = $this->newController();
		$input = new StubInput(array('tag' => array(12)));
		$controller->input = $input;

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('redirect:tag/action_hero');

		$controller->search_tags();
	}

	public function testSearchTagsBuildsListForMultipleTags()
	{
		$controller = $this->newController();
		$template = new StubTemplate();
		$input = new StubInput(array('tag' => array(8, 11)));
		$controller->template = $template;
		$controller->input = $input;

		$controller->search_tags();

		$this->assertSame('list', $template->builtView);
		$this->assertSame('tags', $template->values['link']);
		$this->assertIsArray($template->values['comics']);
	}

	public function testTeamsBuildsMenuViewUsingPublishedTeamsOnly()
	{
		$controller = $this->newController();
		$template = new StubTemplate();
		$controller->template = $template;
		Team::reset();

		$controller->teams();

		$this->assertSame('menu', $template->builtView);
		$this->assertSame('Teams List', $template->values['title']);
		$this->assertSame('teams', $template->values['link']);
		$this->assertSame('search_team/', $template->values['search_action']);
		$this->assertSame('teamworks', $template->values['item_link_prefix']);
		$this->assertSame('name', $template->values['item_name_field']);
		$this->assertSame('stub', $template->values['item_stub_field']);
		$this->assertSame('teams', $template->values['items_name']);
		$this->assertSame(array('id IN (SELECT DISTINCT ch.team_id FROM fs_chapters ch JOIN fs_comics c ON c.id = ch.comic_id WHERE ch.hidden = 0 AND ch.team_id != 0 AND c.hidden = 0)', null, false), Team::$whereArgs[0]);
	}

	public function testSearchTeamBuildsMenuViewUsingPublishedTeamsOnly()
	{
		$controller = $this->newController();
		$template = new StubTemplate();
		$controller->template = $template;
		$controller->input = new StubInput(array('search' => 'scan'));
		Team::reset();

		$controller->search_team();

		$this->assertSame('menu', $template->builtView);
		$this->assertSame('scan', $template->values['search']);
		$this->assertSame('Teams List', $template->values['title']);
		$this->assertSame('teams', $template->values['link']);
		$this->assertSame('search_team/', $template->values['search_action']);
		$this->assertSame('teamworks', $template->values['item_link_prefix']);
		$this->assertSame(array('name', 'scan'), Team::$ilikeArgs);
		$this->assertSame(array('id IN (SELECT DISTINCT ch.team_id FROM fs_chapters ch JOIN fs_comics c ON c.id = ch.comic_id WHERE ch.hidden = 0 AND ch.team_id != 0 AND c.hidden = 0)', null, false), Team::$whereArgs[0]);
	}

	public function testTeamsHonorsExplicitEmptyDbPrefix()
	{
		$controller = $this->newController();
		$controller->template = new StubTemplate();
		$controller->config = new StubConfig(array('db_table_prefix' => ''));
		Team::reset();

		$controller->teams();

		$this->assertSame(array('id IN (SELECT DISTINCT ch.team_id FROM chapters ch JOIN comics c ON c.id = ch.comic_id WHERE ch.hidden = 0 AND ch.team_id != 0 AND c.hidden = 0)', null, false), Team::$whereArgs[0]);
	}

	public function testTeamsExcludeNationLicensedComicsForPublicReaders()
	{
		$controller = $this->newController();
		$controller->template = new StubTemplate();
		$controller->session = new StubSession();
		$controller->session->set_userdata('nation', 'IT');
		$controller->tank_auth = new StubTankAuth(false, false);
		$controller->db = new StubDb();
		Team::reset();

		$controller->teams();

		$this->assertSame(array("id IN (SELECT DISTINCT ch.team_id FROM fs_chapters ch JOIN fs_comics c ON c.id = ch.comic_id WHERE ch.hidden = 0 AND ch.team_id != 0 AND c.hidden = 0 AND NOT EXISTS (SELECT 1 FROM fs_licenses l WHERE l.comic_id = c.id AND l.nation = 'IT'))", null, false), Team::$whereArgs[0]);
	}

	public function testRemapCallsReaderMethodWhenAvailable()
	{
		$controller = new ReaderPingController();

		$this->assertSame('reader:ok', $controller->_remap('ping', array('ok')));
	}

	public function testRemapCallsRcMethodWhenAvailable()
	{
		$controller = $this->newController();
		$controller->RC = new StubReaderRc();

		$this->assertSame('rc:ok', $controller->_remap('ping', array('ok')));
	}

	public function testRemapThrows404WhenMethodMissing()
	{
		$controller = $this->newController();
		$controller->RC = new stdClass();

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('404');

		$controller->_remap('missing_method');
	}

	public function testReadRedirectsToSeriesWhenChapterIsMissing()
	{
		$controller = $this->newController();

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('redirect:series/demo');

		$controller->read('demo', 'en', 0, '');
	}

	public function testSeriesThrows404WhenStubIsMissing()
	{
		$controller = $this->newController();

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('404');

		$controller->series(null);
	}

	public function testSeriesHandlesMissingTagsCollection()
	{
		$controller = $this->newController();
		$template = new StubTemplate();
		$controller->input = new StubInput(array());
		$controller->session = new StubSession();
		$controller->agent = new StubAgent(false);
		$controller->template = $template;

		$controller->series('demo');

		$this->assertSame('comic', $template->builtView);
	}
}

class StubTemplate
{
	public $values = array();
	public $titleValue;
	public $builtView;

	public function set($key, $value)
	{
		$this->values[$key] = $value;
		return $this;
	}

	public function title($title)
	{
		$this->titleValue = $title;
		return $this;
	}

	public function build($view)
	{
		$this->builtView = $view;
		return $this;
	}
}

class StubInput
{
	private $postValues;

	public function __construct(array $postValues)
	{
		$this->postValues = $postValues;
	}

	public function post($key = null)
	{
		if ($key === null)
		{
			return $this->postValues;
		}

		if (array_key_exists($key, $this->postValues))
		{
			return $this->postValues[$key];
		}

		return null;
	}
}

class StubSession
{
	public $data = array();
	public $flash = array();

	public function set_userdata($key, $value)
	{
		$this->data[$key] = $value;
	}

	public function userdata($key)
	{
		if (array_key_exists($key, $this->data))
		{
			return $this->data[$key];
		}

		return null;
	}

	public function flashdata($key)
	{
		if (array_key_exists($key, $this->flash))
		{
			return $this->flash[$key];
		}

		return null;
	}
}

class StubFormValidation
{
	private $values;
	private $runResult;

	public function __construct(array $values, $runResult)
	{
		$this->values = $values;
		$this->runResult = $runResult;
	}

	public function set_rules($field, $label, $rules)
	{
		return $this;
	}

	public function run()
	{
		return $this->runResult;
	}

	public function set_value($field)
	{
		if (array_key_exists($field, $this->values))
		{
			return $this->values[$field];
		}

		return null;
	}
}

class StubEmail
{
	public $fromAddress;
	public $replyToAddress;
	public $toAddress;
	public $subjectLine;
	public $messageBody;
	public $altMessageBody;
	public $sendCalls = 0;

	private $sendResult;

	public function __construct($sendResult)
	{
		$this->sendResult = $sendResult;
	}

	public function clear($clear_attachments = FALSE)
	{
		return $this;
	}

	public function from($address, $name = '')
	{
		$this->fromAddress = $address;
		return $this;
	}

	public function reply_to($address, $name = '')
	{
		$this->replyToAddress = $address;
		return $this;
	}

	public function to($address)
	{
		$this->toAddress = $address;
		return $this;
	}

	public function subject($subject)
	{
		$this->subjectLine = $subject;
		return $this;
	}

	public function message($message)
	{
		$this->messageBody = $message;
		return $this;
	}

	public function set_alt_message($message)
	{
		$this->altMessageBody = $message;
		return $this;
	}

	public function send()
	{
		$this->sendCalls++;
		return $this->sendResult;
	}
}

class StubConfig
{
	private $items;

	public function __construct(array $items)
	{
		$this->items = $items;
	}

	public function item($key, $group = '')
	{
		if (array_key_exists($key, $this->items))
		{
			return $this->items[$key];
		}

		return null;
	}
}

class StubDb
{
	public function escape($value)
	{
		return "'" . str_replace("'", "\\'", $value) . "'";
	}
}

class StubAgent
{
	private $robot;

	public function __construct($robot)
	{
		$this->robot = $robot;
	}

	public function is_robot()
	{
		return $this->robot;
	}
}

class StubTankAuth
{
	private $allowed;
	private $team;

	public function __construct($allowed, $team)
	{
		$this->allowed = $allowed;
		$this->team = $team;
	}

	public function is_allowed()
	{
		return $this->allowed;
	}

	public function is_team()
	{
		return $this->team;
	}
}

class StubReaderRc
{
	public function ping($value)
	{
		return 'rc:' . $value;
	}
}

if (!function_exists('URIpurifier'))
{
	function URIpurifier($value)
	{
		return $value;
	}
}

if (!function_exists('HTMLpurify'))
{
	function HTMLpurify($value, $context = null)
	{
		return $value;
	}
}

if (!function_exists('log_message'))
{
	function log_message($level, $message)
	{
		return true;
	}
}

if (!class_exists('Tag'))
{
	class Tag
	{
		public $name = 'Action Hero';

		public function where($key, $value)
		{
			return $this;
		}

		public function get()
		{
			return $this;
		}

		public function get_comics($comics, $tag_id)
		{
			return array((object) array('id' => 7));
		}
	}
}

if (!class_exists('Team'))
{
	class Team
	{
		public static $ilikeArgs = null;
		public static $whereArgs = array();
		public $all = array();

		public static function reset()
		{
			self::$ilikeArgs = null;
			self::$whereArgs = array();
		}

		public function order_by($field, $direction)
		{
			return $this;
		}

		public function ilike($field, $value)
		{
			self::$ilikeArgs = array($field, $value);
			return $this;
		}

		public function where($field, $value = null, $escape = true)
		{
			self::$whereArgs[] = array($field, $value, $escape);
			return $this;
		}

		public function get_paged($page = 1, $page_size = 50)
		{
			$this->all = array((object) array('name' => 'Demo Team', 'stub' => 'demo-team'));
			return $this;
		}

		public function get()
		{
			$this->all = array((object) array('name' => 'Demo Team', 'stub' => 'demo-team'));
			return $this;
		}
	}
}

if (!class_exists('Comic'))
{
	class Comic
	{
		public static $throwOnIlike = false;
		public $table = 'comics';
		public $id = 7;
		public $typeh_id = 1;
		public $creator = 1;
		public $name = 'Demo';
		public $tags = false;
		public $adult = false;
		public $all = array();

		public function where($key, $value)
		{
			return $this;
		}

		public function clear()
		{
			return $this;
		}

		public function or_where($field, $value)
		{
			return $this;
		}

		public function ilike($field, $value)
		{
			if (self::$throwOnIlike)
			{
				throw new RuntimeException('search failed');
			}

			return $this;
		}

		public function limit($limit)
		{
			return $this;
		}

		public function get()
		{
			$this->all = array((object) array('id' => 7));
			return $this;
		}

		public function count()
		{
			return 1;
		}

		public function get_comics($tag_id)
		{
			$this->all = array((object) array('id' => 7));
			return $this;
		}

		public function get_hidden()
		{
			return $this;
		}

		public function result_count()
		{
			return 1;
		}

		public function get_tags()
		{
			$this->tags = false;
			return $this;
		}
	}
}

if (!class_exists('License'))
{
	class License
	{
		public $table = 'licenses';
	}
}

if (!class_exists('Jointag'))
{
	class Jointag implements IteratorAggregate
	{
		private $items = array();

		public function clear()
		{
			$this->items = array();
			return $this;
		}

		public function where($field, $value)
		{
			if ($field === 'tag_id')
			{
				$this->items = array((object) array('jointag_id' => 1));
			}

			return $this;
		}

		public function get()
		{
			return $this;
		}

		public function result_count()
		{
			return count($this->items);
		}

		public function getIterator(): Traversable
		{
			return new ArrayIterator($this->items);
		}
	}
}

if (!class_exists('Chapter'))
{
	class Chapter
	{
		public $table = 'chapters';
		public $all = array();

		public function where($key, $value)
		{
			return $this;
		}

		public function order_by($field, $dir)
		{
			return $this;
		}

		public function limit($value)
		{
			return $this;
		}

		public function get()
		{
			$this->all = array((object) array('team_id' => 5));
			return $this;
		}

		public function result_count()
		{
			return 0;
		}

		public function get_teams()
		{
			return $this;
		}

		public function get_bulk()
		{
			return $this;
		}
	}
}

if (!class_exists('Typeh'))
{
	class Typeh
	{
		public $name = 'Manga';
		public $stub = '';

		public function where($key, $value)
		{
			return $this;
		}

		public function get()
		{
			return $this;
		}
	}
}

if (!class_exists('Users'))
{
	class Users
	{
		public function get_user_by_id($id, $public = true)
		{
			return (object) array('username' => 'admin');
		}
	}
}

class ReaderTestController extends Reader
{
	public $template;
	public $input;
	public $session;
	public $agent;
	public $RC;
	public $form_validation;
	public $email;
	public $config;
	public $tank_auth;
	public $db;

	public function __construct()
	{
	}
}

if (!function_exists('flash_notice'))
{
	function flash_notice($type, $message)
	{
		$GLOBALS['__test_flash_notices'][] = array(
			'type' => $type,
			'message' => $message
		);
	}
}

class ReaderLatestSpyController extends Reader
{
	public $latestCalls = 0;

	public function __construct()
	{
	}

	public function latest($page = 1)
	{
		$this->latestCalls++;
	}
}

class ReaderPingController extends Reader
{
	public $RC;

	public function __construct()
	{
	}

	public function ping($value)
	{
		return 'reader:' . $value;
	}
}

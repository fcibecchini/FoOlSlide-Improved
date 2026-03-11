<?php

use PHPUnit\Framework\TestCase;

class ReaderControllerTest extends TestCase
{
	private function newController()
	{
		return new ReaderTestController();
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

if (!class_exists('Comic'))
{
	class Comic
	{
		public static $throwOnIlike = false;
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

	public function __construct()
	{
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

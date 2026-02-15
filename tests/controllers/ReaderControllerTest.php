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

	public function testSearchTagsRedirectsToTagsWhenNoPostPayload()
	{
		$controller = $this->newController();
		$input = new StubInput(array());
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
	}
}

if (!class_exists('Comic'))
{
	class Comic
	{
		public $id = 7;

		public function where($key, $value)
		{
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

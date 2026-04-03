<?php

use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class DataMapperDynamicPropertiesTest extends TestCase
{
	public function testHasOneRelationAccessDoesNotEmitDynamicPropertyDeprecation()
	{
		if (!class_exists('CI_Lang', false))
		{
			eval(<<<'PHP'
class CI_Lang
{
	public $language = array();
}
PHP
			);
		}

		if (!class_exists('CI_Loader', false))
		{
			eval(<<<'PHP'
class CI_Loader
{
	protected $_ci_classes = array();
}
PHP
			);
		}

		require_once dirname(__DIR__, 2) . '/application/libraries/datamapper.php';

		if (!class_exists('DataMapperDynamicPropertiesChapter', false))
		{
			eval(<<<'PHP'
class DataMapperDynamicPropertiesChapter extends DataMapper
{
	public $id = 42;
	public $has_one = array(
		'comic' => array(
			'class' => 'DataMapperDynamicPropertiesComic',
			'other_field' => 'chapter',
			'auto_populate' => false,
		),
	);
	public $has_many = array();

	public function __construct()
	{
	}

	public function exists()
	{
		return false;
	}
}

class DataMapperDynamicPropertiesComic extends DataMapper
{
	public function __construct()
	{
	}
}
PHP
			);
		}

		set_error_handler(function ($severity, $message, $file, $line) {
			if ($severity === E_DEPRECATED)
			{
				throw new ErrorException($message, 0, $severity, $file, $line);
			}

			return false;
		});

		try
		{
			$chapter = new DataMapperDynamicPropertiesChapter();

			$this->assertInstanceOf(DataMapperDynamicPropertiesComic::class, $chapter->comic);
			$this->assertSame(
				array('model' => 'chapter', 'id' => 42),
				$chapter->comic->parent
			);
		}
		finally
		{
			restore_error_handler();
		}
	}
}

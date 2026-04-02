<?php

use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class ArchiveModelTest extends TestCase
{
	private $createdDirectories = array();

	protected function setUp(): void
	{
		$GLOBALS['__test_settings'] = array(
			'fs_dl_archive_max' => 0.0015,
			'fs_dl_volume_enabled' => false,
		);

		ArchiveDataMapperStub::resetStorage();
		Chapter::resetRegistry();
		ArchivePageStub::reset();
	}

	protected function tearDown(): void
	{
		foreach (array_reverse($this->createdDirectories) as $directory)
		{
			$this->removeDirectory($directory);
		}
	}

	public function testCompressCreatesZipAndPersistsArchiveMetadata()
	{
		$comic = $this->createComic('archive-test-create', 'Demo Archive');
		$chapter = $this->createChapter($comic, array(
			'id' => 101,
			'uniqid' => 'chapter-create',
			'language' => 'it',
			'volume' => 0,
			'chapter' => 1,
			'subchapter' => 0,
			'directory' => 'chapter-create',
		));

		$this->createPageFiles($comic, $chapter, array(
			array('filename' => 'page-1.txt', 'bytes' => 700),
			array('filename' => 'page-2.txt', 'bytes' => 500),
		));

		$archive = new Archive();
		$result = $archive->compress($comic, 'chapter-create', 'it', 0, 1, 0);

		$this->assertFileExists($result['server_path']);
		$this->assertSame(1, ArchiveDataMapperStub::count());
		$this->assertSame(1, (new Chapter($chapter->id))->downloads);
		$this->assertSame(basename($result['server_path']), ArchiveDataMapperStub::all()[0]['filename']);
		$this->assertGreaterThan(0, filesize($result['server_path']));
	}

	public function testCompressEvictsOldestArchiveWhenNewArchivePushesCacheOverLimit()
	{
		$comic = $this->createComic('archive-test-eviction', 'Eviction Demo');
		$oldChapter = $this->createChapter($comic, array(
			'id' => 201,
			'uniqid' => 'chapter-old',
			'language' => 'it',
			'volume' => 0,
			'chapter' => 1,
			'subchapter' => 0,
			'directory' => 'chapter-old',
		));
		$newChapter = $this->createChapter($comic, array(
			'id' => 202,
			'uniqid' => 'chapter-new',
			'language' => 'it',
			'volume' => 0,
			'chapter' => 2,
			'subchapter' => 0,
			'directory' => 'chapter-new',
		));

		$this->createArchiveRecord($comic, $oldChapter, 'old-cache.zip', 900, '2026-04-02 09:00:00');
		$this->createPageFiles($comic, $newChapter, array(
			array('filename' => 'page-1.txt', 'bytes' => 900),
		));

		$archive = new Archive();
		$result = $archive->compress($comic, 'chapter-new', 'it', 0, 2, 0);

		$storedArchives = ArchiveDataMapperStub::all();
		$this->assertCount(1, $storedArchives, 'Expected the oldest cached archive to be evicted.');
		$this->assertSame(202, $storedArchives[0]['chapter_id']);
		$this->assertFileDoesNotExist($this->chapterArchivePath($comic, $oldChapter, 'old-cache.zip'));
		$this->assertFileExists($result['server_path']);
		$this->assertLessThanOrEqual(
			(int) round(get_setting('fs_dl_archive_max') * 1024 * 1024),
			array_sum(array_column($storedArchives, 'size'))
		);
	}

	private function createComic($directory, $name)
	{
		$path = FCPATH . 'content/comics/' . $directory;
		if (!is_dir($path))
		{
			mkdir($path, 0777, true);
		}
		$this->createdDirectories[] = $path;

		return new ArchiveComicStub(1 + count($this->createdDirectories), $directory, $name);
	}

	private function createChapter($comic, array $data)
	{
		$chapter = new Chapter();
		$chapter->id = $data['id'];
		$chapter->uniqid = $data['uniqid'];
		$chapter->language = $data['language'];
		$chapter->volume = $data['volume'];
		$chapter->chapter = $data['chapter'];
		$chapter->subchapter = $data['subchapter'];
		$chapter->comic_id = $comic->id;
		$chapter->downloads = 0;
		$chapter->comic = $comic;
		$chapter->teams = array(new ArchiveTeamStub('HF'));
		$chapter->setDirectory($data['directory']);
		Chapter::register($chapter);

		$path = FCPATH . 'content/comics/' . $comic->directory() . '/' . $chapter->directory();
		if (!is_dir($path))
		{
			mkdir($path, 0777, true);
		}
		$this->createdDirectories[] = $path;

		return $chapter;
	}

	private function createPageFiles($comic, $chapter, array $pages)
	{
		ArchivePageStub::registerPages($chapter->id, array());

		foreach ($pages as $page)
		{
			$path = FCPATH . 'content/comics/' . $comic->directory() . '/' . $chapter->directory() . '/' . $page['filename'];
			file_put_contents($path, str_repeat('A', $page['bytes']));
			ArchivePageStub::appendPage($chapter->id, new ArchivePageFileStub($page['filename']));
		}
	}

	private function createArchiveRecord($comic, $chapter, $filename, $bytes, $lastdownload)
	{
		$path = $this->chapterArchivePath($comic, $chapter, $filename);
		file_put_contents($path, str_repeat('Z', $bytes));

		$archive = new Archive();
		$archive->comic_id = $comic->id;
		$archive->volume_id = 0;
		$archive->chapter_id = $chapter->id;
		$archive->filename = $filename;
		$archive->size = filesize($path);
		$archive->lastdownload = $lastdownload;
		$archive->save();
	}

	private function chapterArchivePath($comic, $chapter, $filename)
	{
		return FCPATH . 'content/comics/' . $comic->directory() . '/' . $chapter->directory() . '/' . $filename;
	}

	private function removeDirectory($directory)
	{
		if (!is_dir($directory))
		{
			return;
		}

		$items = scandir($directory);
		if (!is_array($items))
		{
			return;
		}

		foreach ($items as $item)
		{
			if ($item === '.' || $item === '..')
			{
				continue;
			}

			$path = $directory . '/' . $item;
			if (is_dir($path))
			{
				$this->removeDirectory($path);
				continue;
			}

			@unlink($path);
		}

		@rmdir($directory);
	}
}

if (!class_exists('DataMapper'))
{
	#[AllowDynamicProperties]
	class DataMapper extends ArchiveDataMapperStub
	{
	}
}

#[AllowDynamicProperties]
class ArchiveDataMapperStub
{
	private static $records = array();
	private static $nextId = 1;

	protected $queryWhere = array();
	protected $queryOrder = array();
	protected $queryLimit = null;
	protected $queryOffset = 0;
	protected $querySelectSum = null;
	protected $resultCountValue = 0;
	public $all = array();

	public function __construct($id = null)
	{
		if ($id !== null)
		{
			foreach (self::$records as $record)
			{
				if ($record['id'] === $id)
				{
					$this->fill($record);
					$this->resultCountValue = 1;
					break;
				}
			}
		}
	}

	public static function resetStorage()
	{
		self::$records = array();
		self::$nextId = 1;
	}

	public static function all()
	{
		return array_values(self::$records);
	}

	public static function count()
	{
		return count(self::$records);
	}

	public function where($field, $value)
	{
		$this->queryWhere[] = array($field, $value);
		return $this;
	}

	public function order_by($field, $direction)
	{
		$this->queryOrder = array($field, strtoupper($direction));
		return $this;
	}

	public function limit($limit, $offset = 0)
	{
		$this->queryLimit = (int) $limit;
		$this->queryOffset = (int) $offset;
		return $this;
	}

	public function select_sum($field)
	{
		$this->querySelectSum = $field;
		return $this;
	}

	public function get()
	{
		if ($this->querySelectSum !== null)
		{
			$field = $this->querySelectSum;
			$this->$field = array_sum(array_column(self::$records, $field));
			$this->resetQuery();
			return $this;
		}

		$records = array_values(array_filter(self::$records, function ($record) {
			foreach ($this->queryWhere as $where)
			{
				if (!array_key_exists($where[0], $record) || $record[$where[0]] != $where[1])
				{
					return false;
				}
			}

			return true;
		}));

		if (!empty($this->queryOrder))
		{
			$field = $this->queryOrder[0];
			$direction = $this->queryOrder[1];
			usort($records, function ($left, $right) use ($field, $direction) {
				$result = strcmp((string) $left[$field], (string) $right[$field]);
				return $direction === 'DESC' ? -$result : $result;
			});
		}

		if ($this->queryLimit !== null)
		{
			$records = array_slice($records, $this->queryOffset, $this->queryLimit);
		}

		$this->all = array_map(function ($record) {
			$item = new static();
			$item->fill($record);
			return $item;
		}, $records);
		$this->resultCountValue = count($records);

		if ($this->resultCountValue === 1)
		{
			$this->fill($records[0]);
		}

		$this->resetQuery();
		return $this;
	}

	public function save()
	{
		if (!isset($this->id))
		{
			$this->id = self::$nextId++;
		}

		self::$records[$this->id] = $this->serializeRecord();
		return true;
	}

	public function delete()
	{
		unset(self::$records[$this->id]);
		return true;
	}

	public function result_count()
	{
		return $this->resultCountValue;
	}

	private function fill(array $record)
	{
		foreach ($record as $key => $value)
		{
			$this->$key = $value;
		}
	}

	private function serializeRecord()
	{
		$record = array();
		foreach (get_object_vars($this) as $key => $value)
		{
			if (strpos($key, 'query') === 0 || $key === 'resultCountValue' || $key === 'all')
			{
				continue;
			}

			$record[$key] = $value;
		}

		return $record;
	}

	private function resetQuery()
	{
		$this->queryWhere = array();
		$this->queryOrder = array();
		$this->queryLimit = null;
		$this->queryOffset = 0;
		$this->querySelectSum = null;
	}
}

class ArchiveComicStub
{
	public $id;
	public $name;
	private $directory;

	public function __construct($id, $directory, $name)
	{
		$this->id = $id;
		$this->directory = $directory;
		$this->name = $name;
	}

	public function directory()
	{
		return $this->directory;
	}
}

class ArchiveTeamStub
{
	public $name;

	public function __construct($name)
	{
		$this->name = $name;
	}
}

if (!class_exists('Chapter'))
{
	class Chapter implements IteratorAggregate
	{
		public static $records = array();
		public $table = 'chapters';
		public $all = array();
		private $filters = array();
		private $loaded = array();
		public $id = 1;
		public $comic_id = 7;
		public $uniqid = 'chapter';
		public $language = 'en';
		public $volume = 0;
		public $chapter = 1;
		public $subchapter = 0;
		public $downloads = 0;
		public $teams = array();
		public $comic;
		private $directoryName = 'chapter';

		public static function resetRegistry()
		{
			self::$records = array();
		}

		public static function register(self $chapter)
		{
			self::$records[$chapter->id] = $chapter;
		}

		public function __construct($id = null)
		{
			if ($id !== null && isset(self::$records[$id]))
			{
				$this->copyFrom(self::$records[$id]);
				$this->loaded = array(self::$records[$id]);
			}
		}

		public function where($key, $value)
		{
			$this->filters[$key] = $value;
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

		public function get_hidden()
		{
			$this->loaded = array_values(array_filter(self::$records, function ($chapter) {
				foreach ($this->filters as $field => $value)
				{
					if (!isset($chapter->$field) || $chapter->$field != $value)
					{
						return false;
					}
				}

				return true;
			}));

			if (count($this->loaded) === 1)
			{
				$this->copyFrom($this->loaded[0]);
			}

			return $this;
		}

		public function result_count()
		{
			return count($this->loaded);
		}

		public function get_teams()
		{
			if (empty($this->teams))
			{
				$this->teams = array((object) array('name' => 'Demo Team'));
			}

			return $this;
		}

		public function get_comic()
		{
			return $this;
		}

		public function save()
		{
			self::$records[$this->id] = clone $this;
			return true;
		}

		public function directory()
		{
			return $this->directoryName;
		}

		public function setDirectory($directory)
		{
			$this->directoryName = $directory;
		}

		public function get_bulk()
		{
			return $this;
		}

		public function getIterator(): Traversable
		{
			return new ArrayIterator($this->loaded);
		}

		private function copyFrom(self $chapter)
		{
			foreach (get_object_vars($chapter) as $key => $value)
			{
				if ($key === 'filters' || $key === 'loaded')
				{
					continue;
				}

				$this->$key = $value;
			}
		}
	}
}

class ArchivePageFileStub
{
	public $filename;

	public function __construct($filename)
	{
		$this->filename = $filename;
	}
}

class ArchivePageStub implements IteratorAggregate
{
	private static $pagesByChapter = array();
	private $chapterId;
	private $loaded = array();

	public static function reset()
	{
		self::$pagesByChapter = array();
	}

	public static function registerPages($chapterId, array $pages)
	{
		self::$pagesByChapter[$chapterId] = $pages;
	}

	public static function appendPage($chapterId, $page)
	{
		if (!isset(self::$pagesByChapter[$chapterId]))
		{
			self::$pagesByChapter[$chapterId] = array();
		}

		self::$pagesByChapter[$chapterId][] = $page;
	}

	public function where($field, $value)
	{
		if ($field === 'chapter_id')
		{
			$this->chapterId = $value;
		}

		return $this;
	}

	public function get()
	{
		$this->loaded = isset(self::$pagesByChapter[$this->chapterId]) ? self::$pagesByChapter[$this->chapterId] : array();
		return $this;
	}

	public function getIterator(): Traversable
	{
		return new ArrayIterator($this->loaded);
	}
}

class Page extends ArchivePageStub
{
}

require_once dirname(__DIR__, 2) . '/application/models/archive.php';

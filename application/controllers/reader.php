<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Reader extends Public_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->library('pagination');
		$this->load->library('template');
		$this->template->set_layout('reader');
	}


	public function index()
	{
		$this->latest();
	}

	public function about()
	{
		$this->template->title(_('About'), get_setting('fs_gen_site_title'));
		$this->template->set('show_sidebar', TRUE);
		$this->template->build('about');
	}

	function sitemap()
	{
		$sitemap = array(
			array(
				// homepage
				'loc' => site_url(),
				'lastmod' => '', // not needed,
				'changefreq' => 'hourly', // extremely fast
				'priority' => '0.8'
			),
			array(
				// release list page
				'loc' => site_url('directory'),
				'lastmod' => '',
				'changefreq' => 'weekly', // comics picked up don't change often
				'priority' => '0.5'
			),
			array(
				// tags page
				'loc' => site_url('tags'),
				'lastmod' => '',
				'changefreq' => 'monthly', 
				'priority' => '0.5'
			),
			array(
				// authors page
				'loc' => site_url('authors'),
				'lastmod' => '',
				'changefreq' => 'weekly',
				'priority' => '0.5'
			),
			array(
				// downloads page
				'loc' => site_url('most_downloaded'),
				'lastmod' => '',
				'changefreq' => 'hourly',
				'priority' => '0.5'
			),
			array(
				// parodies page
				'loc' => site_url('parodies'),
				'lastmod' => '',
				'changefreq' => 'weekly',
				'priority' => '0.5'
			)
		);

		$comics = new Comic();
		$comics->get_iterated();
		foreach ($comics as $comic)
		{
			$sitemap[] =
					array(
						// homepage
						'loc' => $comic->href(),
						'lastmod' => '',
						'changefreq' => 'daily',
						'priority' => '0.4'
			);
		}

		$chapters = new Chapter();
		$chapters->get_iterated();
		foreach ($chapters as $chapter)
		{
			$sitemap[] =
					array(
						// homepage
						'loc' => $chapter->href_iterated(),
						'lastmod' => $chapter->created,
						'changefreq' => 'daily',
						'priority' => '0.4'
			);
		}
		
		$comicss = new Comic();
		$comicss->get_iterated();
		
		foreach ($comicss as $c)
		{
			$sitemap[] =
			array(
					'loc' => $c->author_url(),
					'lastmod' => '',
					'changefreq' => 'weekly',
					'priority' => '0.4'
			);
		}
		
		$tags = new Tag();
		$tags->get_iterated();
		foreach ($tags as $tag)
		{
			$sitemap[] =
			array(
					'loc' => $tag->href(),
					'lastmod' => '',
					'changefreq' => 'daily',
					'priority' => '0.4'
			);
		}

		$data["sitemap"] = $sitemap;
		$this->load->view('sitemap', $data);
	}


	function feeds($format = NULL)
	{
		//if (is_null($format))
		//	redirect('feeds/rss');
		$this->load->helper('xml');
		$chapters = new Chapter();

		// filter with orderby
		$chapters->order_by('created', 'DESC');

		// get the generic chapters and the comic coming with them
		$chapters->limit(25)->get();
		$chapters->get_comic();

		if ($chapters->result_count() > 0)
		{
			// let's create a pretty array of chapters [comic][chapter][teams]
			$result['chapters'] = array();
			foreach ($chapters->all as $key => $chapter)
			{
				$result['chapters'][$key]['title'] = $chapter->comic->title() . ' ' . $chapter->title();
				$result['chapters'][$key]['thumb'] = $chapter->comic->get_thumb();
				$result['chapters'][$key]['href'] = $chapter->href();
				$result['chapters'][$key]['created'] = $chapter->created;
				$chapter->get_teams();
				foreach ($chapter->teams as $item)
				{
					$result['chapters'][$key]['teams'] = implode(' | ', $item->to_array());
				}
			}
		}
		else
			show_404();

		$data['encoding'] = 'utf-8';
		$data['feed_name'] = get_setting('fs_gen_site_title');
		$data['feed_url'] = site_url('feeds/rss');
		$data['page_description'] = get_setting('fs_gen_site_title') . ' RSS feed';
		$data['page_language'] = get_setting('fs_gen_lang') ? get_setting('fs_gen_lang') : 'en_EN';
		$data['posts'] = $result;
		if ($format == "atom")
		{
			header("Content-Type: application/atom+xml");
			$this->load->view('atom', $data);
			return TRUE;
		}
		header("Content-Type: application/rss+xml");
		$this->load->view('rss', $data);
	}

	public function team($stub = NULL)
	{
		if (is_null($stub))
			show_404();
		$team = new Team();
		$team->where('stub', $stub)->get();
		if ($team->result_count() < 1)
			show_404();

		$memberships = new Membership();
		$members = $memberships->get_members($team->id);

		$this->template->title($team->name, get_setting('fs_gen_site_title'));
		$this->template->set('team', $team);
		$this->template->set('members', $members);
		$this->template->build('team');
	}
	
	public function teamworks($stub, $page = 1) 
	{
		if (is_null($stub))
			show_404();
		$team = new Team();
		$team->where('stub', $stub)->get();
		
		$this->template->title(_('Series by Team'));
		
		$chapters = new Chapter();
		$chapters->where('team_id', $team->id)->order_by('created', 'DESC')->get_paged($page, 15);
		
		$this->template->title(_($team->name . ' Translations'), get_setting('fs_gen_site_title'));
		$this->template->set('show_sidebar', TRUE);
		$this->template->set('chapters', $chapters);
		$this->template->set('team', $team);
		$this->template->set('is_team', true);
		$this->template->set('link', 'teamworks/'.$team->stub);
		$this->template->build('latest');
	}


	public function directory($name, $page = 1)
	{
		$comics = new Comic();
		$type = new Typeh();
		$type->ilike('name', $name)->get();
		$comics->where('typeh_id', $type->id);
		$param = $type->name;

		/**
		* @todo this needs filtering, though it looks good enough in browser
		*/
		$comics->order_by('name', 'ASC')->get_paged($page, 15);
		
		foreach ($comics as $comic)
		{
			$comic->latest_chapter = new Chapter();
			$comic->latest_chapter->where('comic_id', $comic->id)->order_by('created', 'DESC')->limit(1)->get();
		}

		$this->template->title(_($param . ' List'), get_setting('fs_gen_site_title'));
		$this->template->set('show_sidebar', TRUE);
		$this->template->set('comics', $comics);
		$this->template->set('param', $param);
		$this->template->set('link', 'directory/'.$name);
		$this->template->build('list');
	}
	
	public function parody($parody, $page = 1) 
	{
		if (is_null($parody))
			show_404();
		$comics = new Comic();
		$comics->where('parody_stub', $parody)->order_by('name', 'ASC')->get_paged($page, 15);
		
		if ($comics->result_count() < 1)
			show_404();
		
		foreach ($comics->all as $comic1)
		{
			$comic1->latest_chapter = new Chapter();
			$comic1->latest_chapter->where('comic_id', $comic1->id)->order_by('created', 'DESC')->limit(1)->get();
		}
		
		foreach ($comics->all as $comic1)
		{
			$parody = $comic1->parody;
			$parody_stub = $comic1->parody_stub;
			break;
		}
		
		$this->template->title($parody, get_setting('fs_gen_site_title'));
		$this->template->set('show_sidebar', TRUE);
		$this->template->set('comics', $comics);
		$this->template->set('param', $parody);
		$this->template->set('param_stub', $parody_stub);
		$this->template->set('link', 'parody/'.$parody_stub);
		$this->template->build('list');
	}
	
	public function author($author, $page = 1) 
	{
		if (is_null($author))
			show_404();
		$comics = new Comic();
		$comics->where('author_stub', $author)->order_by('name', 'ASC')->get_paged($page, 15);
		
		if ($comics->result_count() < 1)
			show_404();
		
		foreach ($comics->all as $comic1)
		{
			$comic1->latest_chapter = new Chapter();
			$comic1->latest_chapter->where('comic_id', $comic1->id)->order_by('created', 'DESC')->limit(1)->get();
		}
		
		foreach ($comics->all as $comic1) 
		{
			$author = $comic1->author;
			$author_stub = $comic1->author_stub;
			break;
		}
		
		$this->template->title($author, get_setting('fs_gen_site_title'));
		$this->template->set('show_sidebar', TRUE);
		$this->template->set('comics', $comics);
		$this->template->set('param', $author);
		$this->template->set('param_stub', $author_stub);
		$this->template->set('link', 'author/'.$author_stub);
		$this->template->build('list');
	}
	
	public function authors($page = 1) 
	{		
		$comics = new Comic();
		
		$comics->order_by('author_stub', 'ASC')->distinct()->get_paged($page, 100);
		
		$this->template->title(_('Authors'), get_setting('fs_gen_site_title'));
		$this->template->set('title', _('Authors List'));
		$this->template->set('link', 'authors');
		$this->template->set('param', 'author');
		$this->template->set('param_stub', 'author_stub');
		$this->template->set('show_sidebar', TRUE);
		$this->template->set('comics', $comics);
		$this->template->build('menu');
	}
	
	public function parodies($page = 1) 
	{		
		$comics = new Comic();
		
		$comics->where('parody !=', "")->order_by('parody_stub, binary(parody)', 'ASC')->get_paged($page, 100);
		
		$this->template->title(_('Parodies'), get_setting('fs_gen_site_title'));
		$this->template->set('title', _('Parodies List'));
		$this->template->set('link', 'parodies');
		$this->template->set('param', 'parody');
		$this->template->set('param_stub', 'parody_stub');
		$this->template->set('show_sidebar', TRUE);
		$this->template->set('comics', $comics);
		$this->template->build('menu');
	}
	
	public function tags($page = 1)
	{		
		$tags = new Tag();
		$tags->order_by('name', 'ASC')->get_paged($page, 20);
		
		$this->template->set('tags', $tags);
		$this->template->set('show_sidebar', TRUE);
		$this->template->set('show_searchtags', TRUE);
		$this->template->title(_('Tags'), get_setting('fs_gen_site_title'));
		$this->template->build('tags');
	}
	
	public function tag($name, $page = 1)
	{
		$tag = new Tag();
		$tag->ilike('name', $name)->get();
		
		if($tag->result_count() < 1)
			show_404();
		
		$jointags = new Jointag();
		$jointags->where('tag_id', $tag->id)->get();
		$comics = new Comic();
		
		if ($jointags->result_count() > 0) 
		{
			foreach ($jointags as $j)
			{
				$comics->or_where('jointag_id', $j->jointag_id)->where('hidden', 0);
			}

			$comics->order_by('created', 'DESC')->get_paged($page, 10);
			foreach ($comics as $comic)
			{
				$comic->latest_chapter = new Chapter();
				$comic->latest_chapter->where('comic_id', $comic->id)->order_by('created', 'DESC')->limit(1)->get();
			}
					
			$this->template->set('comics', $comics);
		}
		$this->template->title($tag->name, get_setting('fs_gen_site_title'));
		$this->template->set('show_sidebar', TRUE);
		$this->template->set('show_searchtags', TRUE);
		$this->template->set('param', $tag->name);
		$this->template->set('link','tag/'.URIpurifier($tag->name));
		$this->template->build('list');
	}
	
	public function search_tags()
	{
		if ($post = $this->input->post()) {
			// let's sort the tags and delete empty entries
			foreach ($post['tag'] as $key => $value)
				if ($value == 0)
					unset($post['tag'][$key]);
			$tagss = array_unique($post['tag']);
	
			// check if we are searching for more than one tag
			if (count($tagss) > 1) {
				
				// Sort tags From Higher number of Comics to lower
				$sorted_tags = array();
				$comics = new Comic();
				$jointags = new Jointag();
				foreach ($tagss as $tag) {
					$comics->clear();
					$jointags->clear();
					$jointags->where('tag_id', $tag)->get();
					if ($jointags->result_count () > 0) {
						foreach ( $jointags as $j ) {
							$comics->or_where ( 'jointag_id', $j->jointag_id );
						}
					}
					$count = $comics->where('hidden',0)->count();
					$sorted_tags[$count] = $tag;
				}
				krsort($sorted_tags);
				
				// Take the last element (the tag with lowest number of comics)
				$alltags = new Tag();
				$last = array_pop($sorted_tags);
				$result = $comics->get_comics($last);
				foreach ($sorted_tags as $tg) {
					$t = array_pop($sorted_tags);
					if (count($result) > 0)
						$result = $alltags->get_comics($result, $t);
					else break;
				}
				foreach ($result as $c) {
					$c->latest_chapter = new Chapter();
					$c->latest_chapter->where('comic_id', $c->id)->order_by('created', 'DESC')->limit(1)->get();
				}
				if (count($result) <= 0) $this->template->set('error_message', _('No series found with specified tags'));
	
				// now put the tags searched in the page title
				$this->template->set('comics', $result);
				$this->template->title('Search Result', get_setting('fs_gen_site_title'));
				$this->template->set('show_sidebar', TRUE);
				$this->template->set('show_searchtags', TRUE);
				$this->template->set('param', _('Results'));
				$this->template->set('link', 'tags');
				$this->template->build ('list');
			}
	
			// just one tag? redirect the user to the tag page then
			elseif (count($tagss) == 1) {
				$search = new Tag();
				$search->where('id', array_pop($tagss))->get();
				$tt = strtolower(str_replace(' ', '_', $search->name));
				redirect ('tag/'.$tt);
			}
	
			// no tags? return to the same page
			else
				redirect('tags');
		}
	
		else 
			redirect('tags');
	}
	
	public function latest($page = 1)
	{
		// Create a "Chapter" object. It can contain more than one chapter!
		$chapters = new Chapter();

		// Select the latest 25 released chapters		
		$chapters->order_by('created', 'DESC')->get_paged($page, 15);
		foreach($chapters as $key => $chapter) {
			$chapter->comic->get_tags();
			foreach ($chapter->comic->tags as $tag) {
				$tag->stub = URIpurifier($tag->name);
			}
		}

		$this->template->set('chapters', $chapters);
		$this->template->set('show_sidebar', TRUE);
		$this->template->set('is_latest', true);
		$this->template->set('link', 'latest');
		$this->template->set('metahome', true);
		$this->template->set('metapage', $page);
		$this->template->title(_('Home'), get_setting('fs_gen_site_title'));
		$this->template->build('latest');
	}

	public function _check_adult($comic)
	{
		if($this->input->post('adult') == 'true')
		{
			$this->session->set_userdata('adult', TRUE);
		}

		if($comic->adult && !$this->agent->is_robot() && $this->session->userdata('adult') != TRUE)
		{
			$this->template->set('comic', $comic);
			$this->template->title(_('Adult content notice'));
			$this->template->build('adult');
			return FALSE;
		}

		return TRUE;
	}


	public function read($comic, $language = 'en', $volume = 0, $chapter = "", $subchapter = 0, $team = 0, $joint = 0, $pagetext = 'page', $page = 1)
	{
		$comice = new Comic();
		$comice->where('stub', $comic)->get_hidden();
		if ($comice->result_count() == 0)
		{
			set_notice('warn', 'This comic doesn\'t exist.');
		}

		if ($chapter == "")
		{
			redirect('series/' . $comic);
		}

		if(!$this->_check_adult($comice))
		{
			// or this function won't stop
			return FALSE;
		}

		$chaptere = new Chapter();
		$chaptere->where('comic_id', $comice->id)->where('language', $language)->where('volume', $volume)->where('chapter', $chapter)->order_by('subchapter', 'ASC');

		if (!is_int($subchapter) && $subchapter == 'page')
		{
			$current_page = $team;
		}
		else
		{
			$chaptere->where('subchapter', $subchapter);

			if ($team == 'page')
				$current_page = $joint;
			else
			{
				if ($team != 0)
				{
					$teame = new Team();
					$teame->where('stub', $team)->get();
					$chaptere->where('team_id', $teame->id);
				}

				if ($joint == 'page')
					$current_page = $pagetext;

				if ($joint != 0)
				{
					$chaptere->where('joint_id', $joint);
				}
			}
		}

		if (!isset($current_page))
		{
			if ($page != 1)
				$current_page = $page;
			else
				$current_page = 1;
		}

		$chaptere->get_hidden();
		if ($chaptere->result_count() == 0)
		{
			show_404();
		}
				
		$pages = $chaptere->get_pages();
		foreach ($pages as $page)
			unset($page["object"]);
		$next_chapter = $chaptere->next();

		if ($current_page > count($pages))
			redirect($next_chapter);
		if ($current_page < 1)
			$current_page = 1;

		$chapters = new Chapter();
		$chapters->where('comic_id', $comice->id)->order_by('volume', 'desc')->order_by('chapter', 'desc')->order_by('subchapter', 'desc')->get_bulk_hidden();

		/* $comics = new Comic();
		$comics->order_by('name', 'ASC')->limit(100)->get(); */
		
		$this->template->set('is_reader', TRUE);
		$this->template->set('comic', $comice);
		$this->template->set('chapter', $chaptere);
		$this->template->set('chapters', $chapters);
		//$this->template->set('comics', $comics);
		$this->template->set('current_page', $current_page);
		$this->template->set('pages', $pages);
		$this->template->set('next_chapter', $next_chapter);
		$this->template->title($comice->name, _('Chapter') . ' ' . $chaptere->chapter, get_setting('fs_gen_site_title'));

		switch ($comice->format) {
			case 1:
				$format = 'readtoon';
				break;

			default:
				$format = 'read';
		}
		$this->template->build($format);
	}

	public function download($comic, $uniqid, $language = 'en', $volume = null, $chapter = null, $subchapter = 0)
	{
		if (!get_setting('fs_dl_enabled'))
		{
			show_404();
		}

		$comice = new Comic();
		$comice->where('stub', $comic)->get_hidden();

		if ($comice->result_count() == 0)
		{
			set_notice('warn', 'This comic does not exist.');
		}

		$archive = new Archive();
		$result = $archive->compress($comice, $uniqid, $language, $volume, $chapter, $subchapter);

		if ($this->input->is_cli_request())
		{
			echo $result["server_path"].PHP_EOL;
		}
		else
		{
			redirect($result["url"]);
		}
	}
	
	public function most_downloaded($page = 1)
	{
		$chapters = new Chapter();
		
		$chapters->where('downloads >', 0)->order_by('downloads', 'DESC')->get_paged($page, 15);
		
		$this->template->set('chapters', $chapters);
		$this->template->set('link', 'most_downloaded');
		$this->template->set('is_download', true);
		$this->template->set('show_sidebar', TRUE);
		$this->template->title(_('Popular Releases'), get_setting('fs_gen_site_title'));
		$this->template->build('latest');
	}


	/**
	 * Replacing comic with serie, for deprecated "comic"...
	 *
	 * @deprecated 0.7 30/07/2011
	 * @author Woxxy
	 */
	public function comic($stub = NULL)
	{
		redirect('series/' . $stub);
	}


	/**
	 * Replacing serie with series, for deprecated "serie"...
	 *
	 * @deprecated 0.7 30/07/2011
	 * @author Woxxy
	 */
	public function serie($stub = NULL)
	{
		redirect('series/' . $stub);
	}


	public function series($stub = NULL)
	{
		if (is_null($stub))
			show_404();
		$comic = new Comic();
		$comic->where('stub', $stub)->get_hidden();
		if ($comic->result_count() < 1)
			show_404();

		if(!$this->_check_adult($comic))
		{
			// or this function won't stop
			return FALSE;
		}

		$chapters = new Chapter();
		$chapters->where('comic_id', $comic->id)->order_by('volume', 'desc')->order_by('chapter', 'desc')->order_by('subchapter', 'desc')->get_bulk();
		$type = new Typeh();
		$type->where('id', $comic->typeh_id)->get();
		$type->stub = URIpurifier($type->name);
		$comic->get_tags();
		foreach($comic->tags as $value)
			$value->stub = URIpurifier($value->name);
		$users = new Users();
		$user = $users->get_user_by_id($comic->creator, TRUE);
		
		//$this->template->set('show_sidebar', TRUE);
		$this->template->set('comic', $comic);
		$this->template->set('metacomic', $comic);
		$this->template->set('chapters', $chapters);
		$this->template->set('type', $type);
		$this->template->set('user', $user->username);
		$this->template->title($comic->name, get_setting('fs_gen_site_title'));
		$this->template->build('comic');
	}


	public function search()
	{
		if (!$this->input->post('search') || strlen($this->input->post('search')) < 3)
		{
			$this->template->title(_('Search'), get_setting('fs_gen_site_title'));
			$this->template->build('search_pre');
			return TRUE;
		}

		$search = HTMLpurify($this->input->post('search'), 'unallowed');
		$this->template->title(_('Search'));

		$comics = new Comic();
		$comics->ilike('name', $search)->get();
		foreach ($comics->all as $comic)
		{
			$comic->latest_chapter = new Chapter();
			$comic->latest_chapter->where('comic_id', $comic->id)->order_by('created', 'DESC')->limit(1)->get()->get_teams();
		}
		$this->template->set('show_sidebar', TRUE);
		$this->template->set('search', $search);
		$this->template->set('comics', $comics);
		$this->template->build('search');
	}
	
	public function search_author() 
	{
		$search = HTMLpurify($this->input->post('search'), 'unallowed');
		$this->template->title(_('Search Artist'));
		
		$comics = new Comic();
		$comics->ilike('author', $search)->order_by('author', 'ASC')->get();
		
		$this->template->set('title', _('Authors List'));
		$this->template->set('show_sidebar', TRUE);
		$this->template->set('search', $search);
		$this->template->set('comics', $comics);
		$this->template->set('link', 'authors');
		$this->template->set('param', 'author');
		$this->template->set('param_stub', 'author_stub');
		$this->template->build('menu');
	}
	
	public function search_parody()
	{
		$search = HTMLpurify($this->input->post('search'), 'unallowed');
		$this->template->title(_('Search Parody'));
	
		$comics = new Comic();
		$comics->ilike('parody', $search)->order_by('parody', 'ASC')->get();
		
		$this->template->set('title', _('Parodies List'));
		$this->template->set('show_sidebar', TRUE);
		$this->template->set('search', $search);
		$this->template->set('comics', $comics);
		$this->template->set('link', 'parodies');
		$this->template->set('param', 'parody');
		$this->template->set('param_stub', 'parody_stub');
		$this->template->build('menu');
	}


	public function _remap($method, $params = array())
	{
		if (isset($this->RC) && is_object($this->RC) && method_exists($this->RC, $method))
		{
			return call_user_func_array(array($this->RC, $method), $params);
		}

		if (method_exists($this, $method))
		{
			return call_user_func_array(array($this, $method), $params);
		}
		show_404();
	}

}

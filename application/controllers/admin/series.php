<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Series extends Admin_Controller
{
	function __construct()
	{
		parent::__construct();
		if (!($this->tank_auth->is_allowed()))
			redirect('account');

		// if this is a load balancer, let's not allow people in the series tab
		if (get_setting('fs_balancer_master_url'))
			redirect('/admin/members');

		$this->load->model('files_model');
		$this->load->library('pagination');
		$this->viewdata['controller_title'] = '<a href="'.site_url("admin/series").'">' . _("Series") . '</a>';;
	}


	function index()
	{
		redirect('/admin/series/manage');
	}


	function manage($page = 1)
	{
		$this->viewdata["function_title"] = _('Manage');
		$comics = new Comic();

		if ($this->input->post('search') && strlen($this->input->post('search')) > 2)
		{
			$search = $this->input->post('search');
			$comics->ilike('name', $search);
			$this->viewdata["extra_title"][] = _('Searching') . ': ' . htmlspecialchars(($search));
			$comics->order_by('name', 'ASC');
			$comics->get();
		}
		else 
		{
			$comics->order_by('name','ASC');
			$comics->get_paged_iterated($page,20);
		}
		$data["comics"] = $comics;

		$this->viewdata["main_content_view"] = $this->load->view("admin/series/manage.php", $data, TRUE);
		$this->load->view("admin/default.php", $this->viewdata);
	}


	function serie($stub = NULL, $chapter_id = "")
	{
		$comic = new Comic();
		$comic->where("stub", $stub)->get();
		if ($comic->result_count() == 0)
		{
			set_notice('warn', _('Sorry, the series you are looking for does not exist.'));
			$this->manage();
			return false;
		}
		
		// Obtain All Types
		$typehs = new Typeh();
		$typehs->order_by('name', 'ASC')->get();
		
		// Generate Dropdown Array
		$dropdown = array();
		foreach ($typehs->all as $typeh) {
			$dropdown[$typeh->id] = $typeh->name;
		}
		
		// Setup Types Dropdown
		$comic->validation['typeh_id']['label'] = _('Tipo');
		$comic->validation['typeh_id']['type'] = 'dropdowner';
		$comic->validation['typeh_id']['values'] = $dropdown;
		$comic->validation['typeh_id']['help'] = _('Seleziona il tipo della serie');

		$this->viewdata["function_title"] = '<a href="' . site_url('/admin/series/manage/') . '">' . _('Manage') . '</a>';
		if ($chapter_id == "") $this->viewdata["extra_title"][] = $comic->name;

		$data["comic"] = $comic;

		if ($chapter_id != "")
		{
			if ($this->input->post())
			{
				$chapter = new Chapter();
				$chapter->update_chapter_db($this->input->post());
				$subchapter = is_int($chapter->subchapter) ? $chapter->subchapter : 0;
				set_notice('notice', sprintf(_('Information for Chapter %s has been updated.'), $chapter->chapter.'.'.$subchapter));
			}

			$chapter = new Chapter($chapter_id);
			$data["chapter"] = $chapter;

			$team = new Team();
			$teams = $team->get_teams($chapter->team_id, $chapter->joint_id);

			$table = ormer($chapter);

			$table[] = array(
				_('Teams'),
				array(
					'name' => 'team',
					'type' => 'input',
					'value' => $teams,
					'help' => _('Insert the names of the teams who worked on this chapter.')
				)
			);
			
			$table = tabler($table);

			$data["table"] = $table;
			
			$this->viewdata["extra_title"][] = '<a href="' . site_url('admin/series/series/'.$comic->stub) . '">' . $comic->name . '</a>';
			$this->viewdata["extra_title"][] = (($chapter->name != "") ? $chapter->name : $chapter->chapter . "." . $chapter->subchapter);

			$data["pages"] = $chapter->get_pages();

			$this->viewdata["main_content_view"] = $this->load->view("admin/series/chapter.php", $data, TRUE);
			$this->load->view("admin/default.php", $this->viewdata);
			return true;
		}

		if ($this->input->post())
		{
			// Prepare for stub change in case we have to redirect instead of just printing the view
			$old_comic_stub = $comic->stub;
			$comic->update_comic_db($this->input->post());

			$config['upload_path'] = 'content/cache/';
			$config['allowed_types'] = 'jpg|png|gif';
			$this->load->library('upload', $config);
			$field_name = "thumbnail";
			if (count($_FILES) > 0 && $this->upload->do_upload($field_name))
			{
				$up_data = $this->upload->data();
				if (!$this->files_model->comic_thumb($comic, $up_data))
				{
					log_message("error", "Controller: series.php/serie: image failed being added to folder");
				}
				if (!unlink($up_data["full_path"]))
				{
					log_message('error', 'series.php/serie: couldn\'t remove cache file ' . $data["full_path"]);
					return false;
				}
			}

			flash_notice('notice', sprintf(_('Updated series information for %s.'), $comic->name));
			// Did we change the stub of the comic? We need to redirect to the new page then.
			if (isset($old_comic_stub) && $old_comic_stub != $comic->stub)
			{
				redirect('/admin/series/series/' . $comic->stub);
			}
		}

		$chapters = new Chapter();
		$chapters->where('comic_id', $comic->id)->order_by('volume', 'DESC')
				->order_by('chapter', 'DESC')->order_by('subchapter', 'DESC')->get();
		foreach ($chapters->all as $key => $item)
		{
			if ($item->joint_id > 0)
			{
				$teams = new Team();
				$jointers = $teams->get_teams(0, $item->joint_id);
				$item->jointers = $jointers;
				unset($jointers);
				unset($teams);
			}
			else
			{
				$team = new Team($item->team_id);
				$item->team_name = $team->result_count() ? $team->name : _('Unknown');
				$item->team_stub = $team->result_count() ? $team->stub : '';
			}
		}

		$data["chapters"] = $chapters;
		$data["chapters_by_volume"] = array();
		foreach ($chapters->all as $item)
		{
			$volume = (string) $item->volume;
			if (!isset($data["chapters_by_volume"][$volume]))
			{
				$data["chapters_by_volume"][$volume] = array();
			}
			$data["chapters_by_volume"][$volume][] = $item;
		}

		if ($comic->get_thumb())
			$comic->thumbnail = $comic->get_thumb();
		$table = ormer($comic);
		
		$tags = new Tag();
		$tags->order_by('name','ASC')->get();
		$tagnames = array("");
		foreach ($tags->all as $tag)
		{
			$tagnames[] = $tag->name;
		}
		//$comic->get_tags();
		$tagsearch = new Tag();
		$tagvalues = $tagsearch->get_tags($comic->jointag_id);
		if (!is_array($tagvalues) && !is_object($tagvalues))
		{
			$tagvalues = array();
		}
		
		$tagarray = array();
		
		foreach ($tagnames as $kk => $name)
		{
			foreach ($tagvalues as $k => $tt)
			{
				if ($tagnames[$kk] === $tt->name)
				{
					$tagarray[$tt->name] = $kk;
				}
			}
		}

// 		foreach ($tagvalues as $k => $tt)
// 		{
// 			$tagarray[] = $tt;
// 		}
					
		$table[] = array(
				_('Genere'),
				array(
						'name' => 'tags',
						'type' => 'dropdowner',
						'values' => $tagnames,
						'value' => $tagarray,
						'help' => _('Inserisci i Generi della Serie')
				)
		);
		
		$licenses = new License();

		$table[] = array(
			_('Licensed in'),
			array(
				'name' => 'licensed',
				'type' => 'nation',
				'value' => $licenses->get_by_comic($comic->id),
				'help' => _('Insert the nations where the series is licensed in order to limit the availability.')
			)
		);
		
		$custom_slug = array(array(
				_('Custom URL Slug'),
				array(
						'name' => 'has_custom_slug',
						'type' => 'checkbox',
						'text' => _('Has Custom URL Slug'),
						'help' => _('If you want to have a custom url slug or the comic\'s title is written with non-latin letters tick this.'),
						'class' => 'jqslugcb'
				)
		));
			
		array_splice($table, 2, 0, $custom_slug);

		$table = tabler($table);
		$data['table'] = $table;

		$this->viewdata["extra_script"] = '<script type="text/javascript" src="'.base_url().'assets/js/form-extra.js"></script>';
		$this->viewdata["main_content_view"] = $this->load->view("admin/series/series.php", $data, TRUE);
		$this->load->view("admin/default.php", $this->viewdata);
	}


	function add_new($stub = "")
	{
		$this->viewdata["function_title"] = '<a href="#">'._("Add New").'</a>';

		//$stub stands for $comic, but there's already a $comic here
		if ($stub != "")
		{
			if ($this->input->post())
			{
				$chapter = new Chapter();
				if ($chapter->add($this->input->post()))
				{
					$subchapter = is_int($chapter->subchapter) ? $chapter->subchapter : 0;
					flash_notice('notice', sprintf(_('Chapter %s has been added to %s.'), $chapter->chapter.'.'.$subchapter, $chapter->comic->name));
					redirect('/admin/series/series/' . $chapter->comic->stub . '/' . $chapter->id);
				}
			}
			$comic = new Comic();
			$comic->where('stub', $stub)->get();
			$this->viewdata["extra_title"][] = _("Chapter in") . ' ' . $comic->name;
			$chapter = new Chapter();
			$chapter->comic_id = $comic->id;

			$table = ormer($chapter);

			$table[] = array(
				_('Teams'),
				array(
					'name' => 'team',
					'type' => 'input',
					'value' => array('value' => get_setting('fs_gen_default_team')),
					'help' => _('Insert the names of the teams who worked on this chapter.')
				)
			);

			$table = tabler($table, FALSE, TRUE);
			$data["form_title"] = _('Add New Chapter');
			$data["table"] = $table;

			$this->viewdata["main_content_view"] = $this->load->view("admin/form.php", $data, TRUE);
			$this->load->view("admin/default.php", $this->viewdata);
			return true;
		}
		else
		{
			$comic = new Comic();
			
			// Obtain All Types
			$typehs = new Typeh();
			$typehs->order_by('name', 'ASC')->get();
				
			// Generate Dropdown Array
			$dropdown = array();
			foreach ($typehs->all as $typeh) {
				$dropdown[$typeh->id] = $typeh->name;
			}
				
			// Setup Types Dropdown
			$comic->validation['typeh_id']['label'] = _('Tipo');
			$comic->validation['typeh_id']['type'] = 'dropdowner';
			$comic->validation['typeh_id']['values'] = $dropdown;
			$comic->validation['typeh_id']['help'] = _('Seleziona il tipo della serie');
							
			if ($this->input->post())
			{
				if ($comic->add($this->input->post()))
				{
					$config['upload_path'] = 'content/cache/';
					$config['allowed_types'] = 'jpg|png|gif';
					$this->load->library('upload', $config);
					$field_name = "thumbnail";
					if (count($_FILES) > 0 && $this->upload->do_upload($field_name))
					{
						$up_data = $this->upload->data();
						if (!$this->files_model->comic_thumb($comic, $up_data))
						{
							log_message("error", "Controller: series.php/add_new: image failed being added to folder");
						}
						if (!unlink($up_data["full_path"]))
						{
							log_message('error', 'series.php/add_new: couldn\'t remove cache file ' . $up_data["full_path"]);
							return false;
						}
					}
					flash_notice('notice', sprintf(_('The series %s has been added.'), $comic->name));
					redirect('/admin/series/series/' . $comic->stub);
				}
			}

			$table = ormer($comic);
			
			$tags = new Tag();
			$tags->order_by('name','ASC')->get();
			$tagnames = array("");
			foreach ($tags->all as $tag)
			{
				$tagnames[] = $tag->name;
			}
			
			$table[] = array(
					_('Genere'),
					array(
						'name' => 'tags',
						'type' => 'dropdowner',
						'values' => $tagnames,
						'value' => array(),
						'help' => _('Inserisci i Generi della Serie')
					)
			);
			
			$table[] = array(
				_('Licensed in'),
				array(
					'name' => 'licensed',
					'type' => 'nation',
					'value' => array(),
					'help' => _('Insert the nations where the series is licensed in order to limit the availability.'),
					'class' => 'form-control'
				)
			);
			
			$custom_slug = array(array(
				_('Custom URL Slug'),
				array(
					'name' => 'has_custom_slug',
					'type' => 'checkbox',
					'text' => _('Has Custom URL Slug'),
					'help' => _('If you want to have a custom url slug or the comic\'s title is written with non-latin letters tick this.'),
					'class' => 'jqslugcb'
				)
			));
			
			array_splice($table, 2, 0, $custom_slug);

			$table = tabler($table, FALSE, TRUE);
			$data["form_title"] = _('Add New') . ' ' . _('Series');
			$data['table'] = $table;

			$this->viewdata["extra_script"] = '<script type="text/javascript" src="'.base_url().'assets/js/form-extra.js"></script>';
			$this->viewdata["extra_title"][] = _("Series");
			$this->viewdata["main_content_view"] = $this->load->view("admin/form.php", $data, TRUE);
			$this->load->view("admin/default.php", $this->viewdata);
		}
	}

	function add_new_chapter()
	{
		$this->viewdata["function_title"] = '<a href="#">'._("Add New").'</a>';

		if ($this->input->post())
		{
			$chapter = new Chapter();
			if ($chapter->add($this->input->post()))
			{
				$subchapter = is_int($chapter->subchapter) ? $chapter->subchapter : 0;
				flash_notice('notice', sprintf(_('Chapter %s has been added to %s.'), $chapter->chapter.'.'.$subchapter, $chapter->comic->name));
				redirect('/admin/series/series/' . $chapter->comic->stub . '/' . $chapter->id);
			}
		}
		$this->viewdata["extra_title"][] = _("Chapter");

		// Obtain All Comics
		$comics = new Comic();
		$comics->order_by('name', 'ASC')->get();

		// Generate Dropdown Array
		$dropdown = array();
		foreach ($comics->all as $comic) {
			$dropdown[$comic->id] = $comic->name;
		}

		// Setup Comics Dropdown
		$chapter = new Chapter();
		$chapter->validation['comic_id']['label'] = _('Series');
		$chapter->validation['comic_id']['type'] = 'dropdowner';
		$chapter->validation['comic_id']['values'] = $dropdown;
		$chapter->validation['comic_id']['help'] = _('Add chapter to selected series.');

		$table = ormer($chapter);
		$table[] = array(
			_('Teams'),
			array(
				'name' => 'team',
				'type' => 'input',
				'value' => array('value' => get_setting('fs_gen_default_team')),
				'help' => _('Insert the names of the teams who worked on this chapter.')
			)
		);

		$table = tabler($table, FALSE, TRUE);

		$data["form_title"] = _('Add New Chapter');
		$data["table"] = $table;

		$this->viewdata["main_content_view"] = $this->load->view("admin/form.php", $data, TRUE);
		$this->load->view("admin/default.php", $this->viewdata);
		return true;
	}
	
	function add_type() 
	{
		
		// only admins are allowed to create new comic types
		if (!$this->tank_auth->is_admin())
		{
			show_404();
		}
		
		// save the data if POST
		if ($this->input->post())
		{
			$type = new Typeh();
			if ($type->add($this->input->post()))
			{
				flash_notice('notice', 'Added the type ' . $type->name . '.');
				redirect('/admin/series/manage_types/' . str_replace(' ', '_', $type->name));
			}
		}
		
		$type = new Typeh();
		
		// set title and subtitle
		$this->viewdata["function_title"] = '<a href="' . site_url("/admin/series/manage_types") . '">' . _('Types') . '</a>';
		$this->viewdata["extra_title"][] = _('Add New');
		
		// transform the Datamapper array to a form
		$result = ormer($type);
		$result = tabler($result, FALSE, TRUE);
		$data['form_title'] = _('Add New Type');
		$data['table'] = $result;
		
		// print out
		$this->viewdata["main_content_view"] = $this->load->view('admin/form', $data, TRUE);
		$this->load->view("admin/default", $this->viewdata);
	}
	
	function manage_types($stub = "") 
	{
		// no type selected
		if ($stub == "")
		{
			// set subtitle
			$this->viewdata["function_title"] = _('Types');
		
			$types = new Typeh();
		
			// support filtering via search
			if ($this->input->post())
			{
				$types->ilike('name', $this->input->post('search'));
				$this->viewdata['extra_title'][] = _('Searching') . " : " . $this->input->post('search');
			}
		
			$types->order_by('name', 'ASC')->get_iterated();
			$rows = array();
			// produce links for each type
			foreach ($types as $type)
			{
				$rows[] = array('title' => '<a href="' . site_url('admin/series/manage_types/' . strtolower(str_replace(' ', '_', $type->name))) . '">' . $type->name . '</a>');
			}
			// put in a list the types
			$data['form_title'] = _('Types');
			$data['table'] = tabler($rows, TRUE, FALSE);
		
			// print out
			// we are using the default users view to show the types: this is ok since we only want
			// to show a list of links, but it would be better to implement an independent view in the future.
			$this->viewdata["main_content_view"] = $this->load->view('admin/members/users', $data, TRUE);
			$this->load->view("admin/default", $this->viewdata);
		}
		else
		{
			// type was selected, let's grab it and create a form for it
			$type = new Typeh();
			$name = str_replace('_', ' ', $stub);
			$type->where('name', $name)->get();
		
			// if the type was not found return 404
			if ($type->result_count() != 1)
				show_404();
		
			// if admin allow full editing rights
			if ($this->tank_auth->is_admin())
				$can_edit = true;
			else
				$can_edit = false;
		
			// if allowed in any way to edit,
			if ($this->input->post() && $can_edit)
			{
				$post["id"] = $type->id;
		
				// save the stub in case it's changed
				$old_stub = $type->name;
		
				// send the data to database
				if($type->update_type($this->input->post()))
				{
					// green box to tell data is saved
					set_notice('notice', _('Saved.'));
				}
		
				if ($type->name != $old_stub)
				{
					flash_notice('notice', _('Saved.'));
					redirect('admin/series/manage_types/' . str_replace(' ', '_', $type->name));
				}
			}
		
			// subtitle
			$this->viewdata["function_title"] = '<a href="' . site_url("admin/series/manage_types/") . '">' . _('Types') . '</a>';
			// subsubtitle!
			$this->viewdata["extra_title"][] = $type->name;
		
			// convert the tag information to an array
			$result = ormer($type);
		
			// convert the array to a form
			$result = tabler($result, TRUE, $can_edit);
			$data['table'] = $result;
			$data['type'] = $type;
		
			// print out
			$this->viewdata["main_content_view"] = $this->load->view('admin/series/manage_types.php', $data, TRUE);
			$this->load->view("admin/default", $this->viewdata);
		}
	}
	
	function add_tag() 
	{
		
		// only admins are allowed to create tags
		if (!$this->tank_auth->is_admin())
		{
			show_404();
		}
		
		// save the data if POST
		if ($this->input->post())
		{
			$tag = new Tag();
			if ($tag->add($this->input->post()))
			{
				$config['upload_path'] = 'content/cache/';
				$config['allowed_types'] = 'jpg|png|gif';
				$this->load->library('upload', $config);
				$field_name = "thumbnail";
				if (count($_FILES) > 0 && $this->upload->do_upload($field_name))
				{
					$up_data = $this->upload->data();
					if (!$this->files_model->tag_thumb($tag, $up_data))
					{
						log_message("error", "Controller: series.php/add_tag: image failed being added to folder");
					}
					if (!unlink($up_data["full_path"]))
					{
						log_message('error', 'series.php/add_tag: couldn\'t remove cache file ' . $up_data["full_path"]);
						return false;
					}
				}
				flash_notice('notice', 'Added the tag ' . $tag->name . '.');
				redirect('/admin/series/manage_tags/' . str_replace(' ', '_', $tag->name));
			}
		}
		
		$tag = new Tag();
		
		// set title and subtitle
		$this->viewdata["function_title"] = '<a href="' . site_url("/admin/series/manage_tags") . '">' . _('Tags') . '</a>';
		$this->viewdata["extra_title"][] = _('Add New');
		
		// transform the Datamapper array to a form
		$result = ormer($tag);
		$result = tabler($result, FALSE, TRUE);
		$data['form_title'] = _('Add New Tag');
		$data['table'] = $result;
		
		// print out
		$this->viewdata["main_content_view"] = $this->load->view('admin/form', $data, TRUE);
		$this->load->view("admin/default", $this->viewdata);
	}
	
	function manage_tags($stub = "") {
		// no tag selected
		if ($stub == "")
		{
			// set subtitle
			$this->viewdata["function_title"] = _('Tags');
		
			// we can use get_iterated on teams
			$tags = new Tag();
		
			// support filtering via search
			if ($this->input->post())
			{
				$tags->ilike('name', $this->input->post('search'));
				$this->viewdata['extra_title'][] = _('Searching') . " : " . $this->input->post('search');
			}
		
			$tags->order_by('name', 'ASC')->get_iterated();
			$rows = array();
			// produce links for each team
			foreach ($tags as $tag)
			{
				$rows[] = array('title' => '<a href="' . site_url('admin/series/manage_tags/' . strtolower(str_replace(' ', '_', $tag->name))) . '">' . $tag->name . '</a>');
			}
			// put in a list the teams
			$data['form_title'] = _('Tags');
			$data['table'] = tabler($rows, TRUE, FALSE);
		
			// print out
			$this->viewdata["main_content_view"] = $this->load->view('admin/members/users', $data, TRUE);
			$this->load->view("admin/default", $this->viewdata);
		}
		else
		{
			// tag was selected, let's grab it and create a form for it
			$tag = new Tag();
			$name = str_replace('_', ' ', $stub);
			$tag->where('name', $name)->get();
		
			// if the team was not found return 404
			if ($tag->result_count() != 1)
				show_404();
		
			// if admin allow full editing rights
			if ($this->tank_auth->is_admin())
				$can_edit = true;
			else
				$can_edit = false;
		
			// if allowed in any way to edit,
			if ($this->input->post() && $can_edit)
			{
				$post["id"] = $tag->id;
		
				// save the stub in case it's changed
		
				$old_stub = $tag->name;
		
				// send the data to database
				if($tag->update_tag($this->input->post())) 
				{
					$config ['upload_path'] = 'content/cache/';
					$config ['allowed_types'] = 'jpg|png|gif';
					$this->load->library ( 'upload', $config );
					$field_name = "thumbnail";
					if (count ( $_FILES ) > 0 && $this->upload->do_upload ( $field_name )) {
						$up_data = $this->upload->data ();
						if (! $this->files_model->tag_thumb ( $tag, $up_data )) {
							log_message ( "error", "Controller: series.php/add_tag: image failed being added to folder" );
						}
						if (! unlink ( $up_data ["full_path"] )) {
							log_message ( 'error', 'series.php/add_tag: couldn\'t remove cache file ' . $up_data["full_path"] );
							return false;
						}
						// green box to tell data is saved
						set_notice('notice', _('Saved.'));
					}

				}
		
				if ($tag->name != $old_stub)
				{
					flash_notice('notice', _('Saved.'));
					redirect('admin/series/manage_tags/' . str_replace(' ', '_', $tag->name));
				}
			}
		
		
			// subtitle
			$this->viewdata["function_title"] = '<a href="' . site_url("admin/series/manage_tags") . '">' . _('Tags') . '</a>';
			// subsubtitle!
			$this->viewdata["extra_title"][] = $tag->name;

			if ($tag->get_thumb())
				$tag->thumbnail = $tag->get_thumb();
			// convert the tag information to an array
			$result = ormer($tag);
		
			// convert the array to a form
			$result = tabler($result, TRUE, $can_edit);
			$data['table'] = $result;
			$data['tag'] = $tag;
		
			// print out
			$this->viewdata["main_content_view"] = $this->load->view('admin/series/manage_tags.php', $data, TRUE);
			$this->load->view("admin/default", $this->viewdata);
		}
	}

	function upload($upload_type = "")
	{
		$info = array();

		if (!isset($_FILES['Filedata']) || !isset($_FILES['Filedata']['tmp_name']))
		{
			$this->output->set_output(json_encode($info));
			return true;
		}

		// compatibility for flash uploader and browser not supporting multiple upload
		if (is_array($_FILES['Filedata']) && !is_array($_FILES['Filedata']['tmp_name']))
		{
			$_FILES['Filedata']['tmp_name'] = array($_FILES['Filedata']['tmp_name']);
			$_FILES['Filedata']['name'] = array($_FILES['Filedata']['name']);
		}

		for ($file = 0; $file < count($_FILES['Filedata']['tmp_name']); $file++)
		{
			$valid = explode('|', 'png|zip|rar|gif|jpg|jpeg');
			if (!in_array(strtolower(substr($_FILES['Filedata']['name'][$file], -3)), $valid))
				continue;

			if (!in_array(strtolower(substr($_FILES['Filedata']['name'][$file], -3)), array('zip', 'rar')))
				$pages = $this->files_model->page($_FILES['Filedata']['tmp_name'][$file], $_FILES['Filedata']['name'][$file], $this->input->post('chapter_id'));
			else
				$pages = $this->files_model->compressed_chapter($_FILES['Filedata']['tmp_name'][$file], $_FILES['Filedata']['name'][$file], $this->input->post('chapter_id'));

			if (!$pages || !is_array($pages))
			{
				continue;
			}

			foreach ($pages as $page)
			{
				$info[] = array(
					'name' => $page->filename,
					'size' => $page->size,
					'url' => $page->page_url(),
					'thumbnail_url' => $page->page_url(TRUE),
					'delete_url' => site_url("admin/series/delete/page"),
					'delete_data' => $page->id,
					'delete_type' => 'POST'
				);
			}
		}

		// return a json array
		$this->output->set_output(json_encode($info));
		return true;
	}


	function get_file_objects()
	{
		// Generate JSON File Output (Required by jQuery File Upload)
		header('Content-type: application/json');
		header('Pragma: no-cache');
		header('Cache-Control: private, no-cache');
		header('Content-Disposition: inline; filename="files.json"');

		$id = $this->input->post('id');
		$chapter = new Chapter($id);
		$pages = $chapter->get_pages();
		$info = array();
		foreach ($pages as $page)
		{
			$info[] = array(
				'name' => $page['filename'],
				'size' => intval($page['size']),
				'url' => $page['url'],
				'thumbnail_url' => $page['thumb_url'],
				'delete_url' => site_url("admin/series/delete/page"),
				'delete_data' => $page['id'],
				'delete_type' => 'POST'
			);
		}

		$this->output->set_output(json_encode($info));
		return true;
	}


	function delete($type, $id = 0)
	{	
		if (!isAjax())
		{
			$this->output->set_output(_('You can\'t delete chapters from outside the admin panel through this link.'));
			log_message("error", "Controller: series.php/remove: failed serie removal");
			return false;
		}
		$id = intval($id);

		switch ($type)
		{
			case("serie"):
				$comic = new Comic();
				$comic->where('id', $id)->get();
				$title = $comic->name;
				if (!$comic->remove())
				{
					flash_notice('error', sprintf(_('Failed to delete the series %s.'), $title));
					log_message("error", "Controller: series.php/remove: failed serie removal");
					$this->output->set_output(json_encode(array('href' => site_url("admin/series/manage"))));
					return false;
				}
				flash_notice('notice', 'The serie ' . $comic->name . ' has been removed');
				$this->output->set_output(json_encode(array('href' => site_url("admin/series/manage"))));
				break;
			case("chapter"):
				$chapter = new Chapter($id);
				$title = $chapter->chapter;
				if (!$comic = $chapter->remove())
				{
					flash_notice('error', sprintf(_('Failed to delete chapter %s.'), $chapter->comic->chapter));
					log_message("error", "Controller: series.php/remove: failed chapter removal");
					$this->output->set_output(json_encode(array('href' => site_url("admin/series/series/" . $comic->stub))));
					return false;
				}
				set_notice('notice', 'Chapter deleted.');
				$this->output->set_output(json_encode(array('href' => site_url("admin/series/serie/" . $comic->stub))));
				break;
			case("page"):
				$page = new Page($this->input->post('id'));
				$page->get_chapter();
				$page->chapter->get_comic();
				if (!$data = $page->remove_page())
				{
					log_message("error", "Controller: series.php/remove: failed page removal");
					return false;
				}
				$this->output->set_output(json_encode(array('href' => site_url("admin/series/serie/" . $page->chapter->comic->stub . "/" . $page->chapter->id))));
				break;
			case("allpages"):
				$chapter = new Chapter($id);
				$chapter->get_comic();
				if (!$chapter->remove_all_pages())
				{
					log_message("error", "Controller: series.php/remove: failed all pages removal");
					return false;
				}
				$this->output->set_output(json_encode(array('href' => site_url("admin/series/serie/" . $chapter->comic->stub . "/" . $chapter->id))));
				break;
			case("tag"):
				$tag = new Tag();
				$tag->where('id', $id)->get();
				$title = $tag->name;
				if (!$tag->remove_tag())
				{
					flash_notice('error', sprintf(_('Failed to delete the tag %s.'), $title));
					log_message("error", "Controller: series.php/remove: failed tag removal");
					$this->output->set_output(json_encode(array('href' => site_url("admin/series/manage_tags"))));
					return false;
				}
				flash_notice('notice', 'The tag ' . $tag->name . ' has been removed');
				$this->output->set_output(json_encode(array('href' => site_url("admin/series/manage_tags"))));
				break;
		}
	}


	function import($stub)
	{
		if (!$this->tank_auth->is_admin())
			show_404();

		if (!$stub)
			show_404();

		$comic = new Comic();
		$comic->where('stub', $stub)->get();
		$data['comic'] = $comic;
		$this->viewdata["extra_title"][] = $comic->name;

		$archive[] = array(
			_("Absolute directory path to ZIP archive for the series") . ' ' . $comic->name,
			array(
				'type' => 'input',
				'name' => 'directory',
				'help' => sprintf(_('Insert the absolute directory path. This means from the lowest accessible directory. Example: %s'), '/var/www/backup/' . $comic->stub)
			)
		);

		$data['archive'] = tabler($archive, FALSE, TRUE, TRUE);

		$this->viewdata["function_title"] = _("Import");
		if ($this->input->post('directory'))
		{
			$data['directory'] = $this->input->post('directory');
			if (!is_dir($data['directory']))
			{
				set_notice('error', _('The directory you set does not exist.'));
				$this->viewdata["main_content_view"] = $this->load->view("admin/series/import", $data, TRUE);
				$this->load->view("admin/default.php", $this->viewdata);
				return FALSE;
			}
			$data['archives'] = $this->files_model->import_list($data);
			$this->viewdata["main_content_view"] = $this->load->view("admin/series/import_compressed_list", $data, TRUE);
			$this->load->view("admin/default.php", $this->viewdata);
			return TRUE;
		}

		if ($this->input->post('action') == 'execute')
		{
			$result = $this->files_model->import_compressed();
			if (isset($result['error']) && !$result['error'])
			{
				$this->output->set_output(json_encode($result));
				return FALSE;
			}
			else
			{
				$this->output->set_output(json_encode($result));
				return true;
			}
		}

		$this->viewdata["main_content_view"] = $this->load->view("admin/series/import", $data, TRUE);
		$this->load->view("admin/default.php", $this->viewdata);
	}


}

<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Tag extends DataMapper
{

	var $has_one = array();
	var $has_many = array();
	var $validation = array(
			'name' => array(
					'rules' => array('required', 'max_length' => 256),
					'label' => 'Name',
					'type' => 'input'
			),
			'description' => array(
					'rules' => array(),
					'label' => 'Description',
					'type' => 'textarea',
			),
			'thumbnail' => array(
					'rules' => array('max_length' => 512),
					'label' => 'Thumbnail',
					'type' => 'upload',
					'display' => 'image',
			)
	);

	function __construct($id = NULL) {		
		parent::__construct($id);
	}


	function post_model_init($from_cache = FALSE)
	{

	}
	
	public function add($data = array())
	{
		if (!$this->update_tag($data))
		{
			log_message('error', 'add_tag: failed writing to database');
			return false;
		}
		
		if (!$this->add_tag_dir())
		{
			log_message('error', 'add_tag: failed creating dir');
			return false;
		}
		
		// Good job!
		return true;
	}


	public function update_tag($data = array())
	{
		// Check if we're updating or creating a new entry by looking at $data["id"].
		// False is pushed if the ID was not found.
		if (isset($data["id"]) && $data['id'] != '')
		{
			$this->where("id", $data["id"])->get();
			if ($this->result_count() == 0)
			{
				set_notice('error', _('Failed to find the selected tag\'s ID.'));
				log_message('error', 'update_tag_db: failed to find requested id');
				return false;
			}
			// Save the stub in a variable in case it gets changed, so we can change folder name
			$old_name = $this->name;
		}

		// Loop over the array and assign values to the variables.
		foreach ($data as $key => $value)
		{
			$this->$key = $value;
		}
		
		if (isset($old_name) && $old_name != $this->name && is_dir("content/tags/" . str_replace(' ', '_', $old_name) . "_" . $this->id))
		{
			$dir_old = "content/tags/" . str_replace(' ', '_', $old_name) . "_" . $this->id;
			$dir_new = "content/tags/" . str_replace(' ', '_', $this->name) . "_" . $this->id;
			rename($dir_old, $dir_new);
		}

		// Check that we have all the necessary automated variables
		// if (!isset($this->uniqid))
		//	$this->uniqid = uniqid(); 

		// let's save and give some error check. Push false if fail, true if good.
		if (!$this->save())
		{
			if (!$this->valid)
			{
				set_notice('error', _('Check that you have inputted all the required fields.'));
				log_message('error', 'update_tag: failed validation');
			}
			else
			{
				set_notice('error', _('Failed to update the tag in the database for unknown reasons.'));
				log_message('error', 'update_tag: failed to save');
			}
			return false;
		}
		else
		{
			return true;
		}
	}


	public function remove_tag()
	{
		if ($this->result_count() != 1)
		{
			set_notice('error', _('Failed to remove the tag. Please, check file permissions.'));
			log_message('error', 'remove_tag: id not found');
			return false;
		}

		$jointag = new Jointag();
		if (!$jointag->remove_tag_from_all($this->id))
		{
			log_message('error', 'remove_tag: failed removing traces of tag in joints');
			return false;
		}

		if (!$this->delete())
		{
			set_notice('error', _('Failed to delete the tag for unknown reasons.'));
			log_message('error', 'remove_tag: failed removing team');
			return false;
		}
		
		if(!$this->remove_tag_dir())
		{
			log_message('error', 'remove_tag: failed deleting dir');
			return false;
		}

		return true;
	}
	
	public function get_tags($jointag_id)
	{
		if (empty($jointag_id) || !is_numeric($jointag_id) || (int) $jointag_id < 1)
		{
			return array();
		}

		// if it's a jointag, let's deal it as a jointag
		if ($jointag_id > 0)
		{
			// get all the jointags entries so we have all the tags
			$jointags = new Jointag();
			$jointags->where('jointag_id', $jointag_id)->get();
	
			// not an existing jointag?
			if ($jointags->result_count() < 1)
			{
				return array();
			}
	
			// result array
			$tagarray = array();
			foreach ($jointags->all as $key => $join)
			{
				$tag = new Tag();
				$tag->where('id', $join->tag_id);
				$tag->get();
				$tagarray[] = $tag->get_clone();
			}
	
			if (empty($tagarray))
			{
				return array();
			}

			return $tagarray;
		}

		return array();
	}

	// this works by inputting an array of names (not stubs)
	public function get_tag_id($array)
	{
		if (count($array) < 1)
		{
			set_notice('error', _('There were no groups selected.'));
			log_message('error', 'get_groups: input array empty');
			return false;
		}

		if (count($array) == 1)
		{
			$tag = new Tag();
			$tag->where("name", $array[0])->get();
			if ($tag->result_count() < 1)
			{
				set_notice('error', _('There\'s no tag under this ID.'));
				log_message('error', 'get_tag: tag not found');
				return false;
			}
			$result = array("tag_id" => $tag->id);
			return $result;
		}

		set_notice('error', _('There\'s no tag found with this ID.'));
		log_message('error', 'get_tags: no case matched');
		return false;
	}
	
	public function get_comics($comics, $tag_id) {
		$result = array();
		$jointags = new Jointag();
		foreach ($comics as $comic) {
			$jointags->clear();
			$jointags->where('jointag_id', $comic->jointag_id)->where('tag_id', $tag_id)->get();
			if ($jointags->result_count() > 0)
				$result[] = $comic;
		}
		return $result;
	}
	
	public function add_tag_dir()
	{
		// Just create the folder
		if (!mkdir("content/tags/" . $this->directory()))
		{
			set_notice('error', _('The directory could not be created. Please, check file permissions.'));
			log_message('error', 'add_tag_dir: folder could not be created');
			return false;
		}
		return true;
	}
	
	public function remove_tag_dir()
	{
		$dir = "content/tags/" . $this->directory() . "/";
	
		// Delete all inner files
		if (!delete_files($dir, TRUE))
		{
			set_notice('error', _('The files inside the series directory could not be removed. Please, check the file permissions.'));
			log_message('error', 'remove_tag_dir: files inside folder could not be removed');
			return false;
		}
		else
		{
			// On success delete the directory itself
			if (!rmdir($dir))
			{
				set_notice('error', _('The directory could not be removed. Please, check file permissions.'));
				log_message('error', 'remove_tag_dir: folder could not be removed');
				return false;
			}
		}
	
		return true;
	}
	
	public function add_tag_thumb($filedata)
	{
		// If there's already one, remove it.
		if ($this->thumbnail != "")
			$this->remove_tag_thumb();
	
		// Get directory variable
		$dir = "content/tags/" . $this->directory() . "/";
	
		// Copy the full image over
		if (!copy($filedata["server_path"], $dir . $filedata["name"]))
		{
			set_notice('error', _('Failed to create the thumbnail image for the tag. Check file permissions.'));
			log_message('error', 'add_tag_thumb: failed to create/copy the image');
			return false;
		}
	
		// Load the image library
		$CI = & get_instance();
		$CI->load->library('image_lib');
	
		// Let's setup the thumbnail creation and pass it to the image library
		$image = "thumb_" . $filedata["name"];
		$img_config['image_library'] = 'GD2';
		$img_config['source_image'] = $filedata["server_path"];
		$img_config["new_image"] = $dir . $image;
		$img_config['maintain_ratio'] = TRUE;
		$img_config['width'] = 250;
		$img_config['height'] = 250;
		$img_config['maintain_ratio'] = TRUE;
		$img_config['master_dim'] = 'auto';
		$CI->image_lib->initialize($img_config);
	
		// Resize! And return false of failure
		if (!$CI->image_lib->resize())
		{
			set_notice('error', _('Failed to create the thumbnail image for the tag. Resize function didn\'t work'));
			log_message('error', 'add_tag_thumb: failed to create thumbnail');
			return false;
		}
	
		// Whatever we might want to do later, we better clear the library now!
		$CI->image_lib->clear();
	
		// The thumbnail is actually the filename of the original for series thumbnails
		// It's different from page thumbnails - those have "thumb_" in thiserie s variable!
		$this->thumbnail = $filedata["name"];
	
		// Save hoping we're lucky
		if (!$this->save())
		{
			set_notice('error', _('Failed to save the thumbnail image in the database.'));
			log_message('error', 'add_comic_thumb: failed to add to database');
			return false;
		}
	
		// Alright!
		return true;
	}
	
	public function remove_tag_thumb()
	{
	
		// Get directory
		$dir = "content/tags/" . $this->directory() . "/";
	
		// Remove the full image
		if (!unlink($dir . $this->thumbnail))
		{
			set_notice('error', _('Failed to remove the thumbnail\'s original image. Please, check file permissions.'));
			log_message('error', 'Model: tag.php/remove_tag_thumb: failed to delete image');
			return false;
		}
	
		// Remove the thumbnail
		if (!unlink($dir . "thumb_" . $this->thumbnail))
		{
			set_notice('error', _('Failed to remove the thumbnail image. Please, check file permissions.'));
			log_message('error', 'Model: tag.php/remove_tag_thumb: failed to delete thumbnail');
			return false;
		}
	
		// Set the thumbnail variable to empty and save to database
		$this->thumbnail = "";
		if (!$this->save())
		{
			set_notice('error', _('Failed to remove the thumbnail image from the database.'));
			log_message('error', 'Model: tag.php/remove_tag_thumb: failed to remove from database');
			return false;
		}
	
		// All's good.
		return true;
	}
	
	public function get_thumb($full = FALSE)
	{
		if ($this->thumbnail != "")
			return site_url() . "content/tags/" . $this->directory() . "/" . ($full ? "" : "thumb_") . $this->thumbnail;
		return false;
	}
	
	public function href()
	{
		return site_url('tag/' . str_replace(' ', '_', $this->name));
	}
	
	public function directory()
	{
		$name = str_replace(' ', '_', $this->name);
		return $name . '_' . $this->id;
	}

}

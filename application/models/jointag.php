<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Jointag extends DataMapper {

	var $has_one = array();
	var $has_many = array();
	var $validation = array(
			'jointag_id' => array(
					'rules' => array('required', 'max_length' => 256),
					'label' => 'Name'
			),
			'tag_id' => array(
					'rules' => array('required', 'max_length' => 256),
					'label' => 'Stub'
			),
		);

	function __construct($id = NULL) {
		parent::__construct($id);
	}

	function post_model_init($from_cache = FALSE) {

	}

	public function check_jointag($tags) {
		$tags = array_unique($tags);
		$size = count($tags);
		$jointags = new Jointag();
		$jointags->where('tag_id', $tags[0])->get_iterated();
		if ($jointags->result_count() < 1) {
			log_message('debug', 'check_jointag: jointag not found, result count zero');
			return false;
		}

		foreach ($jointags as $jointag) {
			$jointa = new Jointag();
			$jointa->where('jointag_id', $jointag->jointag_id)->get_iterated();
			if ($jointa->result_count() == $size) {
				$test = $tags;
				foreach ($jointa as $joi) {
					$key = array_search($joi->tag_id, $tags);
					if ($key === FALSE) {
						break;
					}
					unset($test[$key]);
				}
				if (empty($test)) {
					return $joi->jointag_id;
				}
			}
		}
		log_message('debug', 'check_jointag: jointag not found');
		return false;
	}

	public function add_jointag_via_name($tags) {
		$result = array();

		$alltags = new Tag();
		$alltags->order_by('name', 'ASC')->get();

		$ordered_tag_ids = array();
		foreach ($alltags->all as $tag)
		{
			$ordered_tag_ids[] = (int) $tag->id;
		}

		foreach ($tags as $value)
		{
			if ($value === '' || $value === NULL || $value === 0 || $value === '0')
			{
				continue;
			}

			$tag = new Tag();
			if (is_numeric($value))
			{
				$numeric_value = (int) $value;
				if (isset($ordered_tag_ids[$numeric_value - 1]))
				{
					$tag->where('id', $ordered_tag_ids[$numeric_value - 1])->get();
				}
				else
				{
					$tag->where('id', $numeric_value)->get();
				}
			}
			else
			{
				$tag->where('name', $value)->get();
			}

			if ($tag->result_count() == 0)
			{
				set_notice('error', _('One of the named tags doesn\'t exist.'));
				log_message('error', 'add_jointag_via_name: tag does not exist');
				return false;
			}

			$result[] = (int) $tag->id;
		}

		return $this->add_jointag($result);
	}

	// $tags is an array of IDs
	public function add_jointag($tags) {
		$tags = array_map('intval', (array) $tags);
		$tags = array_values(array_unique(array_filter($tags)));
		sort($tags);

		if (empty($tags))
		{
			return false;
		}

		if (!$result = $this->check_jointag($tags)) {
			$CI = & get_instance();
			$max_row = $CI->db->select_max('jointag_id')->get('jointags')->row();
			$max = ((int) $max_row->jointag_id) + 1;

			foreach ($tags as $tag) {
				if (!$CI->db->insert('jointags', array('jointag_id' => $max, 'tag_id' => $tag))) {
					set_notice('error', _('Couldn\'t save jointag to database due to an unknown error.'));
					log_message('error', 'add_jointag: saving failed');
					return false;
				}
			}

			return $max;
		}
		return $result;
	}

	public function remove_jointag() {
		if (!$this->delete_all()) {
			set_notice('error', _('The jointag couldn\'t be removed.'));
			log_message('error', 'remove_jointag: failed deleting');
			return false;
		}
		return true;
	}

	public function add_tag($tag_id) {
		$jointag = new Jointag();
		$jointag->tag_id = $tag_id;
		$jointag->jointag_id = $this->jointag_id;
		if (!$jointag->save()) {
			if ($jointag->valid) {
				set_notice('error', _('Check that you have inputted all the required fields.'));
				log_message('error', 'add_tag (jointag.php): validation failed');
			}
			else {
				set_notice('error', _('Couldn\'t add tag to jointag for unknown reasons.'));
				log_message('error', 'add_tag (jointag.php): saving failed');
			}
			return false;
		}
	}

	public function remove_tag($tag_id) {
		$this->where('tag_id', $tag_id)->get();
		if (!$this->delete()) {
			set_notice('error', _('Couldn\'t remove the tag from the jointag.'));
			log_message('error', 'remove_tag (jointag.php): removing failed');
			return false;
		}
	}

	public function remove_tag_from_all($tag_id) {
		$jointags = new Jointag();
		$jointags->where('tag_id', $tag_id)->get();
		if (!$jointags->delete_all()) {
			set_notice('error', _('Couldn\'t remove the tag from all the joints.'));
			log_message('error', 'remove_tags_from_all (jointag.php): removing failed');
			return false;
		}
		return true;
	}

}

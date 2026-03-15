<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Membership extends DataMapper {

	var $has_one = array('user');
	var $has_many = array();
	var $validation = array(
		'team_id' => array(
			'rules' => array('is_int'),
			'label' => 'Team ID',
		),
		'user_id' => array(
			'rules' => array('is_int'),
			'label' => 'User ID'
		),
		'is_leader' => array(
			'rules' => array(),
			'label' => 'Is leader',
		),
		'accepted' => array(
			'rules' => array(),
			'label' => 'Accepted'
		),
		'requested' => array(
			'rules' => array(),
			'label' => 'Requested'
		),
		'applied' => array(
			'rules' => array(),
			'label' => 'Applied'
		)
	);

	function __construct($id = NULL) {
		parent::__construct($id);
	}

	function post_model_init($from_cache = FALSE) {
		
	}

	function check($team_id, $user_id) {
		$member = new Membership();
		$member->where('team_id', $team_id)->where('user_id', $user_id)->get();
		return ($member->result_count() == 1);
	}

	function apply($team_id, $user_id = NULL) {
		if(is_null($user_id))
		{
			$CI = & get_instance();
			$user_id = $CI->tank_auth->get_user_id();
		}
			
		if ($this->check($team_id, $user_id))
			return FALSE;
		$this->team_id = $team_id;
		$this->user_id = $user_id;
		$this->is_leader = 0;
		$this->accepted = 0;
		$this->requested = 0;
		$this->applied = 1;
		if (!$this->save())
			return FALSE;
		return TRUE;
	}

	function request($team_id, $user_id) {
		$CI = & get_instance();
		if (!$CI->tank_auth->is_team_leader($team_id) && !$CI->tank_auth->is_allowed())
			return FALSE;
		if ($this->check($team_id, $user_id))
			return FALSE;
		$this->team_id = $team_id;
		$this->user_id = $user_id;
		$this->is_leader = 0;
		$this->accepted = 0;
		$this->requested = 1;
		$this->applied = 0;
		if (!$this->save())
			return FALSE;
		return TRUE;
	}

	function accept($team_id, $user_id) {
		if (!$this->check($team_id, $user_id))
			return FALSE;
		$this->where('team_id', $team_id)->where('user_id', $user_id)->get();
		$this->user_id = $user_id;
		$this->accepted = 1;
		if (!$this->save())
			return FALSE;
		return TRUE;
	}

	/**
	 * 	Returns User that is applying for the team
	 * 
	 *  @author Woxxy
	 *  @param int $team_id if NULL it returns the applications from every team in which the user is leader
	 * 	@return object User
	 */
	function get_applicants($team_id = NULL) {
		$CI = & get_instance();

		if (is_null($team_id)) {
			$teams = $CI->tank_auth->is_team_leader();
			if (!$teams || $teams->result_count() == 0)
				return FALSE;
			foreach ($teams->all as $team) {
				$this->or_where('team_id', $team->id);
			}
		}
		else if (is_int($team_id)) {
			$this->where('team_id', $team_id);
		}
		else {
			log_message('error', 'membership.php get_applications(): not int or null');
			return FALSE;
		}

		$this->where('accepted', 0)->where('applied', 1)->get();


		foreach ($this->all as $applicant) {
			$applicant->user = new User($applicant->user_id);
			$applicant->team = new Team($applicant->team_id);
		}
		return TRUE;
	}

	/**
	 * 	Returns teams that have requested the user to join
	 * 
	 *  @author Woxxy
	 *  @param int $team_id if NULL it returns for the current user
	 * 	@return object User
	 */
	function get_requests($user_id = NULL) {
		$CI = & get_instance();
		if (is_null($user_id)) {
			$user_id = $CI->tank_auth->get_user_id();
		}
		$this->where('user_id', $user_id)->where('accepted', 0)->where('requested', 1)->get();
		$teams = new Team();
		foreach ($this->all as $request) {
			$request->team = new Team($request->team_id);
		}
		return TRUE;
	}
	
	/**
	 * 	Returns teams that have requested the user to join
	 * 
	 *  @author Woxxy
	 *  @param int $team_id if NULL it returns for the current user
	 * 	@return object User
	 */
	function get_applications($user_id = NULL) {
		$CI = & get_instance();
		if (is_null($user_id)) {
			$user_id = $CI->tank_auth->get_user_id();
		}
		$this->where('user_id', $user_id)->where('accepted', 0)->where('applied', 1)->get();
		if($this->result_count() != 1)
		{
			return FALSE;
		}
		$teams = new Team();
		foreach ($this->all as $request) {
			$request->team = new Team($request->team_id);
		}
		return TRUE;
	}

	/**
	 * Accepts applications, can be triggered by team leader only if the user applied.
	 * By the user only if the team leader requested.
	 * 
	 * @param int $team_id
	 * @param int $user_id 
	 */
	function accept_application($team_id, $user_id = NULL) {
		$CI = & get_instance();
		if (is_null($user_id)) {
			$user_id = $CI->tank_auth->get_user_id();
			$this->where('team_id', $team_id)->where('user_id', $user_id)->where('requested', 1)->get();
			if ($this->result_count() != 1) {
				return FALSE;
			}
			$this->accepted = 1;
			$this->save();
			return TRUE;
		}
		else if (is_numeric($user_id)) {
			if ($CI->tank_auth->is_team_leader($team_id) || $CI->tank_auth->is_allowed()) {
				$this->where('team_id', $team_id)->where('user_id', $user_id);
				if (!$CI->tank_auth->is_allowed())
					$this->where('applied', 1);
				$this->get();
				if ($this->result_count() != 1) {
					return FALSE;
				}
				$this->accepted = 1;
				$this->save();
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Rejects applications, can be triggered by team leader only if the user applied.
	 * By the user only if the team leader requested or if it's a team member.
	 * 
	 * @param int $team_id
	 * @param int $user_id 
	 */
	function reject_application($team_id, $user_id = NULL) {
		$CI = & get_instance();

		if (is_null($user_id)) {
			$user_id = $CI->tank_auth->get_user_id();
			$this->where('team_id', $team_id)->where('user_id', $user_id)->get();
			if ($this->result_count() != 1) {
				return FALSE;
			}
			$this->delete();
			return TRUE;
		}
		else if (is_numeric($user_id)) {
			if ($CI->tank_auth->is_team_leader($team_id) || $CI->tank_auth->is_allowed()) {
				$this->where('team_id', $team_id)->where('user_id', $user_id)->get();
				if ($this->result_count() != 1) {
					return FALSE;
				}
				$this->delete();
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Makes a member an admin. Admins and mods and team leaders can trigger this.
	 * If an admin/mod triggers this when the user is not a team member and the, 
	 * the team has no leaders, this person will automatically be made a team leader.
	 * 
	 * @author Woxxy
	 * @param int $team_id
	 * @param int $user_id 
	 */
	function make_team_leader($team_id, $user_id) {
		$CI = & get_instance();
		if (!$CI->tank_auth->is_team_leader($team_id) && !$CI->tank_auth->is_allowed()) {
			return FALSE;
		}

		$payload = array(
			'team_id' => (int) $team_id,
			'user_id' => (int) $user_id,
			'is_leader' => 1,
			'accepted' => 1,
			'requested' => 0,
			'applied' => 0,
			'updated' => date('Y-m-d H:i:s'),
		);

		$existing = $CI->db
			->select('id')
			->from($CI->db->dbprefix('memberships'))
			->where('team_id', (int) $team_id)
			->where('user_id', (int) $user_id)
			->limit(1)
			->get()
			->row();

		if ($existing)
		{
			return (bool) $CI->db
				->where('id', (int) $existing->id)
				->update($CI->db->dbprefix('memberships'), $payload);
		}

		$payload['created'] = date('Y-m-d H:i:s');
		return (bool) $CI->db->insert($CI->db->dbprefix('memberships'), $payload);
	}

	/**
	 * Removes the selected team leader. Unlike make_team_leader(), admins/mods can always remove them
	 * This means that if one leader disappears, it can be changed by the admin/mod
	 *
	 * @param int $team_id
	 * @param int $user_id
	 * @return bool 
	 */
	function remove_team_leader($team_id, $user_id = NULL) {
		$CI = & get_instance();
		if (!$CI->tank_auth->is_team_leader($team_id) && !$CI->tank_auth->is_allowed()) {
			return FALSE;
		}
		if(is_null($user_id))
			$user_id = $CI->tank_auth->get_user_id();
		$this->where('team_id', $team_id)->where('user_id', $user_id)->get();
		if ($this->result_count() != 1)
			return FALSE;
		$this->is_leader = 0;
		$this->save();
		return TRUE;
	}

	/**
	 * Returns an array of Users. Bonus point: it also returns $user->is_leader
	 * 
	 * @author Woxxy
	 * @param int $team_id 
	 * @return object Users with ->is_leader
	 */
	function get_members($team_id) {
		$CI = & get_instance();
		$query = $CI->db
			->select('u.id, u.username, u.email, u.last_login, p.display_name AS profile_display_name, p.twitter AS profile_twitter, p.bio AS profile_bio, m.is_leader')
			->from($CI->db->dbprefix('memberships') . ' m')
			->join($CI->db->dbprefix('users') . ' u', 'u.id = m.user_id')
			->join($CI->db->dbprefix('profiles') . ' p', 'p.user_id = u.id', 'left')
			->where('m.team_id', (int) $team_id)
			->where('m.accepted', 1)
			->order_by('m.is_leader', 'DESC')
			->order_by('u.username', 'ASC')
			->get();

		$members = array();
		foreach ($query->result() as $member) {
			$member->is_leader = ($member->is_leader == 1) ? '1' : '0';
			$members[] = $member;
		}

		return $members;
	}

	/**
	 * Checks if there's a leader between the members of a team
	 *
	 * @author Woxxy
	 * @param int $team_id
	 * @return bool
	 */
	function has_leader($team_id) {
		$members = new Membership();
		$members_result = $members->get_members($team_id);
		foreach ($members_result as $key => $member) {
			if ($member->is_leader)
				return TRUE;
		}
		return FALSE;
	}

}

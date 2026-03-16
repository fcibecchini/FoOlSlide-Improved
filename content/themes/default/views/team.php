<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
?>

<div class="list">
	<div class="title">
		<?php echo '<a href="' . site_url('team/' . $team->stub) . '">' . _('Team\'s page') . ': ' . $team->name . '</a>'; ?>
	</div>

	<?php
	echo '<div class="group">
					<div class="title">' . _('Informations') . '</span></div>
				';
	if (trim((string) $team->url) !== '')
		echo '<div class="element">
					<div class="title">' . _("URL") . ': <a href="' . $team->url . '">' . $team->url . '</a></div></div>';
	echo '<div class="element">
					<div class="title">' . _("Translations") . ': <a href="' . site_url() . 'teamworks/' . $team->stub . '">' . site_url() . 'teamworks/' . $team->stub . '</a></div></div>';
	if (trim((string) $team->irc) !== '')
		echo '<div class="element">
					<div class="title">' . _("IRC") . ': <a href="' . parse_irc($team->irc) . '">' . $team->irc . '</a></div></div>';
	if (trim((string) $team->twitter) !== '')
		echo '<div class="element">
					<div class="title">' . _("X") . ': <a href="https://x.com/' . $team->twitter . '">https://x.com/' . $team->twitter . '</a></div></div>';
	if (trim((string) $team->facebook) !== '')
		echo '<div class="element">
					<div class="title">' . _("Facebook") . ': <a href="' . $team->facebook . '">' . $team->facebook . '</a></div>
				</div>';
	echo '</div>';


	echo '<div class="group">
					<div class="title">' . _('Team leaders') . '</div>
				';
	if (count($members) == 0) {
		echo '<div class="element">
					<div class="title">' . _("No leaders in this team") . '.</div>
				</div>';
	}
	else
		foreach ($members as $key => $member) {
			if (!$member->is_leader)
				continue;
			$member_name = htmlspecialchars(($member->profile_display_name) ? $member->profile_display_name : $member->username, ENT_QUOTES, 'UTF-8');
			$member_bio = htmlspecialchars((string) $member->profile_bio, ENT_QUOTES, 'UTF-8');
			$member_twitter = htmlspecialchars((string) $member->profile_twitter, ENT_QUOTES, 'UTF-8');
			echo '<div class="element team-member">
					<div class="image">'.get_gravatar($member->email, 75, NULL, NULL, TRUE, array('width' => '75', 'height' => '75', 'style' => 'width:75px;height:75px;')).'</div>
					<div class="member-details">
						<div class="title">' . $member_name . '</div>';
					if($member->profile_bio) echo '<div class="member-meta">'._('Bio').': '.$member_bio.'</div>';
					if($member->profile_twitter) echo '<div class="member-meta">'._('X').': <a href="https://x.com/'.$member_twitter.'" target="_blank">'.$member_twitter.'</a></div>';
					echo '</div>';
				echo '</div>';
		}

	echo '</div><div class="group">
					<div class="title">' . _('Members') . '</div>
				';
	if (count($members) == 0) {
		echo '<div class="element">
					<div class="title">' . _("No members in this team") . '.</div>
				</div>';
	}
	else
		foreach ($members as $key => $member) {
			if ($member->is_leader)
				continue;
			$member_name = htmlspecialchars(($member->profile_display_name) ? $member->profile_display_name : $member->username, ENT_QUOTES, 'UTF-8');
			$member_bio = htmlspecialchars((string) $member->profile_bio, ENT_QUOTES, 'UTF-8');
			$member_twitter = htmlspecialchars((string) $member->profile_twitter, ENT_QUOTES, 'UTF-8');
			echo '<div class="element team-member">
					<div class="image">'.get_gravatar($member->email, 75, NULL, NULL, TRUE, array('width' => '75', 'height' => '75', 'style' => 'width:75px;height:75px;')).'</div>
					<div class="member-details">
						<div class="title">' . $member_name . '</div>';
					if($member->profile_bio) echo '<div class="member-meta">'._('Bio').': '.$member_bio.'</div>';
					if($member->profile_twitter) echo '<div class="member-meta">'._('X').': <a href="https://x.com/'.$member_twitter.'" target="_blank">'.$member_twitter.'</a></div>';
					echo '</div>';
				echo '</div>';
		}
	echo '</div>'
	?>
</div>

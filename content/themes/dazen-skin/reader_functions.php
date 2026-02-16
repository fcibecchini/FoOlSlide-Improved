<?php

/**
 * READER FUNCTIONS
 *
 * This file allows you to add functions and plain procedures that will be
 * loaded every time the public reader loads.
 *
 * If this file doesn't exist, the default theme's reader_functions.php will
 * be loaded.
 *
 * For more information, refer to the support sites linked in your admin panel.
 */





/**
 * Returns the sidebar in the theme
 *
 * @param string team name
 * @author Woxxy
 * @return string facebook widget for the team
 */
if(!function_exists('get_sidebar'))
{
	function get_sidebar()
	{
		$echo = '<div class="list" style="margin-bottom: 10px;">
				<div class="title"><a href="'.site_url().'">Social</a></div>
				<table class="social"><tr>';
		if(get_setting_twitter())$echo .= '<td colspan="2">'. get_twitter_widget() .'</td>';
		if(get_setting_facebook())$echo .= '<td colspan="2">'. get_facebook_widget() .'</td>';
		if(get_setting_irc())$echo .= '<td colspan="2">'. get_irc_widget() .'</td>';
		$echo .= '</tr></table></div></div>';
		return $echo;
	}
}

if(!function_exists('get_searchtags_bar'))
{
	function get_searchtags_bar()
	{
		$tags = new Tag();
		$tags->order_by('name','ASC')->get_iterated();
		$tagnames = array("");
		foreach ($tags as $tag)
			$tagnames[$tag->id] = $tag->name;

		$table[] = array(
			_('Tags'),
			array(
				'name' => 'tag',
				'type' => 'dropdowner',
				'values' => $tagnames,
				'value' => array(),
				//'help' => _('Select the tags you are interested in')

			)
		);
		$table = tabler($table, FALSE, TRUE, FALSE, TRUE);
		$echo = '<script type="text/javascript">
				function addField(e){
					if (jQuery(e).val().length > 0){
						jQuery(e).clone().val("").insertAfter(e);
						jQuery(e).attr("onKeyUp", "");
						jQuery(e).attr("onChange", "");
					}
				}
				</script>';

		$echo .= '<h3>'._('Multiple Tag Search').'</h3>';
		$echo .= form_open_multipart("search_tags/", array('class' => 'form-stacked'));
		$echo .= $table;
		$echo .= form_close();
		$echo .= '';
		return $echo;
	}
}

/**
 * Returns twitter for the team
 * If $team is not set, it returns the home team's twitter
 *
 * @param string team name
 * @author Woxxy
 * @return string twitter for the team
 */
if(!function_exists('get_setting_twitter'))
{
	function get_setting_twitter($team = NULL)
	{
		if(is_null($team)) return get_home_team()->twitter;
		$team = get_home_team();
		return $team->twitter;
	}
}

/**
 * Returns IRC widget for the team
 * If $team is not set, it returns the home team's twitter widget
 *
 * @param string team name
 * @author Woxxy
 * @return string twitter for the team
 */
if(!function_exists('get_twitter_widget'))
{
	function get_twitter_widget($team = NULL)
	{
		$twitter = get_setting_twitter($team);
		//$echo = sprintf(_('%sFollow us%s on Twitter'),'<a href="http://twitter.com/intent/user?screen_name='.urlencode($twitter).'">', '<img src="'.site_url().'assets/images/bird_16_blue.png" /></a>' );
		return '<a href="http://twitter.com/intent/user?screen_name='.$twitter.'"><div><div><i class="fa fa-3x fa-twitter" aria-hidden="true" style=""></i></div></div><br>Twitter</a>';
	}
}

/**
 * Returns IRC for the team
 * If $team is not set, it returns the home team's irc
 *
 * @param string team name
 * @author Woxxy
 * @return string irc for the team
 */
if(!function_exists('get_setting_irc'))
{
	function get_setting_irc($team = NULL)
	{
		if(is_null($team)) return get_home_team()->irc;
		$team = get_home_team();
		return $team->irc;
	}
}

/**
 * Returns IRC widget for the team
 * If $team is not set, it returns the home team's irc widget
 *
 * @param string team name
 * @author Woxxy
 * @return string irc widget for the team
 */
if(!function_exists('get_irc_widget'))
{
	function get_irc_widget($team = NULL)
	{
		$irc = get_setting_irc($team);

		//$echo = _('Come chat with us on') . ' <a href="'.parse_irc($irc).'">' . $irc . '</a>';
		//return '<div class="text">'.$echo.'</div>';
		return '<a href="'.$irc.'"><div><div><i class="fa fa-3x fa-comments" aria-hidden="true" style=""></i></div></div><br>Chat</a>';
	}
}

/**
 * Returns facebook url for the team
 * If $team is not set, it returns the home team's facebook
 *
 * @param string team name
 * @author Woxxy
 * @return string facebook for the team
 */
if(!function_exists('get_setting_facebook'))
{
	function get_setting_facebook($team = NULL)
	{
		$hometeam = get_setting('fs_gen_default_team');
		$team = get_home_team();
		return $team->facebook;
	}
}

/**
 * Returns facebook widget for the team
 * If $team is not set, it returns the home team's facebook widget
 *
 * @param string team name
 * @author Woxxy
 * @return string facebook widget for the team
 */
if(!function_exists('get_facebook_widget'))
{
	function get_facebook_widget($team = NULL)
	{
		$facebook = get_setting_facebook($team);

		//return 	"<iframe src='http://www.facebook.com/plugins/likebox.php?href=".urlencode($facebook)."&amp;width=290&amp;colorscheme=light&amp;show_faces=false&amp;stream=false&amp;header=false' scrolling='no' frameborder='0' style='border:none; overflow:hidden; width:290px; height:63px; background:rgba(255,255,255,.5)' allowTransparency='true'></iframe>";
		return '<a href="'.$facebook.'"><div><div><i class="fa fa-3x fa-facebook" aria-hidden="true" style=""></i></div></div><br>Facebook</a>';
	}
}

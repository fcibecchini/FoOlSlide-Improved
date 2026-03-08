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

if(!function_exists('get_searchtags_style'))
{
	function get_searchtags_style()
	{
		return '<style>
.tag-search {
  background: #292E38;
  border: 1px solid #3a4250;
  border-radius: 12px;
  padding: 14px;
  margin-bottom: 12px;
  box-shadow: 0 1px 4px rgba(0,0,0,.25);
}

.tag-search h3 {
  margin: 0 0 12px;
  display: inline-flex;
  align-items: center;
  gap: 10px;
  padding: 8px 14px;
  border-radius: 999px;
  background: #912F3F;
  color: #fff;
  font-size: 17px;
  font-weight: 800;
}

.tag-search h3::before {
  content: "\f002";
  font-family: FontAwesome;
  font-size: 16px;
}

.tag-search fieldset {
  border: none;
  padding: 0;
  margin: 0;
}

.tag-search .input {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 10px;
  align-items: end;
}

.tag-search select {
  width: 100%;
  background: #1f2733;
  border: 1px solid #3a4250;
  border-radius: 8px;
  color: #f0f0f0;
  padding: 9px 10px;
  font-size: 14px;
}

.tag-search select:focus {
  outline: none;
  border-color: #912F3F;
}

.tag-search .help-block {
  grid-column: 1 / -1;
  font-size: 12px;
  color: #aab0c0;
}

.tag-search input[type="submit"] {
  background: #912F3F;
  border: none;
  border-radius: 999px;
  padding: 10px 18px;
  font-size: 15px;
  font-weight: 700;
  color: #fff;
  cursor: pointer;
  box-shadow: 0 2px 6px rgba(0,0,0,.25);
  height: 42px;
}

.tag-search input[type="submit"]:hover {
  background: #B24759;
}

@media (max-width: 640px) {
  .tag-search .input {
    grid-template-columns: 1fr;
  }

  .tag-search h3 {
    font-size: 15px;
    padding: 7px 12px;
  }
}

.tag-search .input > br {
  display: none;
}

.tag-search .form-group:last-of-type {
  margin-top: 10px;
}

.tag-search label[for="tag"] {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-size: 15px;
  font-weight: 700;
  color: #fff;
  padding: 6px 12px;
  margin-bottom: 8px;
  background: #912F3F;
  border-radius: 999px;
}
</style>';
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

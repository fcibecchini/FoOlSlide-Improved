<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html>
<head>
<title><?php echo $template['title']; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1">
		<?php
		if (isset($metacomic))
		{
			if ($comic->description) echo '<meta name="description" content="'.str_replace('"','',$comic->description).'" />';
			echo '<meta name="keywords" content="';
			if (get_setting('fs_theme_comic_keywords')) echo get_setting('fs_theme_comic_keywords').',';
			if ($comic->parody) echo $comic->parody.',';
			echo $comic->name;
			if ($comic->author) echo ','.$comic->author;
			if ($comic->tags)
				foreach($comic->tags as $key => $value)
					echo ','.$value->name;
			echo '" />';
		}
		elseif (isset($metahome) && $metapage == 1) echo get_setting('fs_theme_header_code_homepage');

		if (file_exists('content/themes/' . get_setting('fs_theme_dir') . '/style.css'))
			echo link_tag('content/themes/' . get_setting('fs_theme_dir') . '/style.css?v='.FOOLSLIDE_VERSION);
		echo link_tag('content/themes/' . get_setting('fs_theme_dir') . '/menu.css?v='.FOOLSLIDE_VERSION);
		echo link_tag('content/themes/' . get_setting('fs_theme_dir') . '/paginazione.css?v='.FOOLSLIDE_VERSION);
		echo link_tag('content/themes/' . get_setting('fs_theme_dir') . '/override.css?v='.FOOLSLIDE_VERSION);
		echo link_tag('content/themes/' . get_setting('fs_theme_dir') . '/read.css?v='.FOOLSLIDE_VERSION);
			echo link_tag('content/themes/' . get_setting('fs_theme_dir') . '/author.css?v='.FOOLSLIDE_VERSION); //il php è adult.css
		echo link_tag('assets/css/font-awesome.min.css?v='.FOOLSLIDE_VERSION);
		?>
		<link rel="sitemap" type="application/xml" title="Sitemap" href="<?php echo site_url() ?>sitemap.xml" />
		<link rel="alternate" type="application/rss+xml" title="RSS" href="<?php echo site_url() ?>rss.xml" />
		<link rel="alternate" type="application/atom+xml" title="Atom" href="<?php echo site_url() ?>atom.xml" />
		<link rel='index' title="<?php echo get_setting('fs_gen_site_title') ?>" href="<?php echo site_url() ?>" />
		<meta name="generator" content="FoOlSlide <?php echo FOOLSLIDE_VERSION ?>" />
		<script src="<?php echo site_url() . 'assets/js/jquery.js?v='.FOOLSLIDE_VERSION ?>"></script>
		<script src="<?php echo site_url() . 'assets/js/jquery.plugins.js?v='.FOOLSLIDE_VERSION ?>"></script>

		<?php if ($this->agent->is_browser('MSIE')) : ?>
		<script type="text/javascript">
			jQuery(document).ready(function(){
			// Let's make placeholders work on IE and old browsers too
			jQuery('[placeholder]').focus(function() {
				var input = jQuery(this);
				if (input.val() == input.attr('placeholder')) {
					input.val('');
					input.removeClass('placeholder');
				}
			}).blur(function() {
				var input = jQuery(this);
				if (input.val() == '' || input.val() == input.attr('placeholder')) {
					input.addClass('placeholder');
					input.val(input.attr('placeholder'));
				}
			}).blur().parents('form').submit(function() {
				jQuery(this).find('[placeholder]').each(function() {
					var input =jQuery(this);
					if (input.val() == input.attr('placeholder')) {
						input.val('');
					}
				})
			});
			});
		</script>

		<?php endif; ?>
		<?php echo get_setting('fs_theme_header_code'); ?>
	</head>
<body
	class="<?php if (isset($_COOKIE["night_mode"]) && $_COOKIE["night_mode"] == 1)
			echo 'night '; ?>">
	<div id="wrapper">
			<?php
			echo get_setting('fs_theme_preheader_text'); ?>
			<div id="header">
				<?php echo get_setting('fs_theme_header_text');?>
				<div id="title"><a href="<?php echo site_url('') ?>"><i class="fa fa-home"></i><span class="mmmh"> <?php echo get_setting('fs_gen_site_title') ?></span></a></div>
				<div role="navigation" id="navig">
					<ul>
						<?php if (get_setting ('fs_gen_back_url')) : ?>
							<?php echo '<li><a href="'.get_setting('fs_gen_back_url').'"><span class="mh"> '._('Forum').'</span></a></li>';?>
						<?php endif; ?>
						<!--<li><a href="<?php echo site_url('tags') ?>"><i class="fa fa-tags"></i><span class="mh"> <?php echo _('Tags'); ?></span></a></li>-->
						<li><a href="<?php echo site_url('tags') ?>"><span class="mh"><?php echo _('Tags'); ?></span></a></li>
						<li><a href="<?php echo site_url('authors') ?>"><span class="mh"> <?php echo _('Authors'); ?></span></a></li>
						<li><a href="<?php echo site_url('parodies') ?>"><span class="mh"> <?php echo _('Parodies'); ?></span></a></li>
						<li><a href="<?php echo site_url('most_downloaded') ?>"><span class="mh"> <?php echo _('Most Downloaded'); ?></span></a></li>
						<?php if (get_setting ('fs_theme_custom_link')) : ?><?php echo '<li>'.get_setting('fs_theme_custom_link').'</li>';?><?php endif; ?>


						<div class="clearer"></div>
					</ul>

					<?php
						echo form_open("search/");
						echo form_input(array('name' => 'search', 'placeholder' => _('CERCA'), 'id' => 'searchbox', 'class' => 'fright'));
						echo form_close();
					?>

				</div>
			<div class="clearer"></div>
			</div>

			<article id="content">



				<?php
				if (!isset($is_reader) || !$is_reader)
					echo '<div class="panel">';

				echo '<div class="row">';

				if (($template['title'] !== 'Lolicon :: Hentai Fantasy Reader') && get_setting('fs_ads_top_banner') && get_setting('fs_ads_top_banner_active') && !get_setting('fs_ads_top_banner_reload'))
					echo '<div class="ads banner" id="ads_top_banner">' . get_setting('fs_ads_top_banner') . '</div>';

				if (($template['title'] !== 'Lolicon :: Hentai Fantasy Reader') && get_setting('fs_ads_top_banner') && get_setting('fs_ads_top_banner_active') && get_setting('fs_ads_top_banner_reload'))
					echo '<div class="ads iframe banner" id="ads_top_banner"><iframe marginheight="0" marginwidth="0" frameborder="0" src="' . site_url() . 'content/ads/ads_top.html' . '"></iframe></div>';

				if (isset($show_sidebar))
					//echo get_sidebar();

					if (isset($show_searchtags) && $this->uri->segment(1) !== 'tags')
						echo get_searchtags_style() . '<div class="tag-search">' . get_searchtags_bar() . '</div>';

				if (isset($is_latest) && $is_latest)
				{
					$loaded_slideshow = FALSE;
					for ($i = 0; $i < 5; $i++)
					{
						$slideshow_img = get_setting('fs_slsh_src_' . $i);
						if ($slideshow_img != FALSE)
						{
							if (!$loaded_slideshow)
							{
								?>
								<link rel="stylesheet" href="<?php echo site_url() ?>assets/js/nivo-slider.css" type="text/css" media="screen" />
								<link rel="stylesheet" href="<?php echo site_url() ?>assets/js/nivoThemes/default/default.css" type="text/css" media="screen" />
								<script src="<?php echo site_url() ?>assets/js/jquery.nivo.slider.pack.js" type="text/javascript"></script>
								<script type="text/javascript">
									jQuery(window).load(function() {
										jQuery('#slider').nivoSlider({
											pauseTime: 6000
										});
									});
								</script>
								<style>
								.nivoSlider {
									position: relative;
									width: 680px !important; /* Change this to your images width */
									height: 280px !important; /* Change this to your images height */
									margin-bottom: 10px;
									overflow: hidden;
									margin-left: 1px;
								}

								.nivoSlider img {
									position: absolute;
									top: 0px;
									left: 0px;
									display: none;
									width: 690px !important;
								}

								.nivoSlider a {
									border: 0;
									display: block;
								}
								</style>
								<?php
								echo ' <div class="slider-wrapper theme-default">
									<div id="slider" class="nivoSlider">';
								$loaded_slideshow = TRUE;
							}

							if (get_setting('fs_slsh_url_' . $i))
								echo '<a href="' . get_setting('fs_slsh_url_' . $i) . '">';
							echo '<img src="' . get_setting('fs_slsh_src_' . $i) . '" alt="" ' . ((get_setting('fs_slsh_text_' . $i) != FALSE) ? 'title="#fs_slsh_text_' . $i . '"' : '') . ' />';
							if (get_setting('fs_slsh_url_' . $i))
								echo '</a>';
						}
					}

					if ($loaded_slideshow)
					{
						echo '</div>';
						for ($i = 0; $i < 5; $i++)
						{
							if (get_setting('fs_slsh_text_' . $i))
							{
								echo '<div id="fs_slsh_text_' . $i . '" class="nivo-html-caption">';
								echo get_setting('fs_slsh_text_' . $i);
								echo '</div>';
							}
						}
						echo '</div>';
					}
				}

				// here we output the body of the page
				echo $template['body'];

				if (($template['title'] !== 'Lolicon :: Hentai Fantasy Reader') && get_setting('fs_ads_bottom_banner') && get_setting('fs_ads_bottom_banner_active') && !get_setting('fs_ads_bottom_banner_reload'))
					echo '<div class="ads banner" id="ads_bottom_banner">' . get_setting('fs_ads_bottom_banner') . '</div>';

				if (($template['title'] !== 'Lolicon :: Hentai Fantasy Reader') && get_setting('fs_ads_bottom_banner') && get_setting('fs_ads_bottom_banner_active') && get_setting('fs_ads_bottom_banner_reload'))
					echo '<div class="ads iframe banner" id="ads_bottom_banner"><iframe marginheight="0" marginwidth="0" frameborder="0" src="' . site_url() . 'content/ads/ads_bottom.html' . '"></iframe></div>';

				echo '</div>';

				if (!isset($is_reader) || !$is_reader)
					echo '</div>';
				?>

			</article>

	</div>
	<div id="footer">
		<div class="text">
			<div>
					<?php echo get_setting('fs_gen_footer_text'); ?>
				</div>
		</div>
	</div>

	<div id="messages"></div>
</body>
	<?php echo get_setting('fs_theme_footer_code'); ?>
</html>

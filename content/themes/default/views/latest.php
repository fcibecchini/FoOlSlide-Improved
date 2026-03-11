<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); ?>
<?php
$is_latest = !empty($is_latest);
$is_download = !empty($is_download);
$is_team = !empty($is_team);
?>

<div class="list">
	<div class="title">
		<a href="<?php echo site_url($link) ?>">
		<?php 
		if ($is_latest) echo _('Latest released chapters').':'; 
		elseif ($is_download) echo _('Most Popular Releases').':';
		elseif ($is_team) echo $team->name.' - '._('Latest released chapters').':';
		?>
		</a>
	</div>
     <?php
		$current_comic = "";
		$current_comic_closer = "";

		$opendiv = FALSE;
		// Let's loop over every chapter. The array is just $chapters because we used get_iterated(), else it would be $chapters->all
		foreach($chapters as $key => $chapter)
		{
			if (!isset($chapter->comic) || !is_object($chapter->comic))
				continue;

			if ($current_comic != $chapter->comic_id)
			{
				if ($opendiv) echo '</div>';
				
				echo '<div class="group">';
				echo '<img class="preview" src="'.htmlspecialchars($chapter->comic->get_thumb(), ENT_QUOTES, 'UTF-8').'" />';
				echo '<div class="title">' . $chapter->comic->url() . ' <span class="meta">' . $chapter->comic->edit_url() . '</span></div>';
				echo '<div class="elemento"><div class="title"><div id="tablelatest">';
				$tags = isset($chapter->comic->tags) && is_array($chapter->comic->tags) ? $chapter->comic->tags : array();
				if ($tags) {
						echo '<div class="row"><span class="mh"><div class="cell"><b>'._('Tag').'</b>:</div></span><div class="cell"> ';
						for ($i=0; $i<3 && isset($tags[$i]); $i++)				
							echo '<a href="'.site_url('tag/'.$tags[$i]->stub).'" ><span class="label label-default">'.$tags[$i]->name.'</span></a>  ';
						if (sizeof($tags)>3) echo ' ... ';
						echo '</div></div>';
				}
				
				if ($chapter->comic->parody) {
						echo '<div class="row"><span class="mh"><div class="cell"><b>'._('Parody').'</b>:</div></span> 
							<div class="cell"><a href="'.site_url('parody/'. $chapter->comic->parody_stub).'"><span class="label label-success">'.$chapter->comic->parody.'</span></a></div></div>';
				}
				echo '</div></div></div>';
				$current_comic = $chapter->comic_id;
			}
			
			echo '<div class="element">'.$chapter->download_url(NULL, 'fleft small').'
					<div class="title">'.$chapter->url().'</div>
					<div class="meta_r">'; if($is_download) echo $chapter->downloads . ' ' . _('downloads'). ', '; echo _('by') . ' ' . $chapter->team_url() . ', ' . $chapter->date() . ' ' . $chapter->edit_url() . '</div>
				</div>';
			
			$opendiv = TRUE;
		}

		// Closing the last comic group
		echo '</div>';
        echo prevnext($link.'/', $chapters);
	?>
</div>

<?php if (!defined('BASEPATH'))
	exit('No direct script access allowed'); ?>		

        <div class="large comic">
            <h1 class="title">
                <?php echo /* $comic->comments('fleft') . */$comic->name; ?>                    
            </h1>
            <?php if ($comic->get_thumb()): ?>
			<div class="thumbnail">
				<img src="<?php echo $comic->get_thumb(); ?>" />
			</div>
			<?php endif; ?>
            <div class="info">
			<div id="tablelist">
                    <?php if ($comic->author) : ?><div class="row"><?php echo '<div class="cell"><b>'._('Author').'</b>:</div> <div class="cell"><a href="'.site_url('author/'. $comic->author_stub).'">'.$comic->author.'</a></div>'; ?></div><?php endif; ?>
                    <?php if ($comic->artist) : ?><div class="row"><?php echo '<div class="cell"><b>'._('Artist').'</b>:</div> <div class="cell">'.$comic->artist.'</div>';?></div><?php endif; ?>   
                    <?php if ($user) : ?><div class="row"><?php echo '<div class="cell"><b>'._('Uploader').'</b>:</div> <div class="cell"><a href="'.site_url('uploader/'.(int) $comic->creator).'">'.htmlspecialchars($user, ENT_QUOTES, 'UTF-8').'</a></div>';?></div><?php endif; ?>
                    <?php if ($comic->typeh_id != 0) : ?><div class="row"><?php echo '<div class="cell"><b>'._('Type').'</b>:</div>  <div class="cell"><a href="'.site_url('directory/'.$type->stub).'"><span class="label label-primary">'.$type->name.'</span></a></div>'; ?></div><?php endif; ?>
                    <?php if ($comic->parody) : ?><div class="row"><?php echo '<div class="cell"><b>'._('Parody').'</b>:</div>  <div class="cell"><a href="'.site_url('parody/'. $comic->parody_stub).'"><span class="label label-success">'.$comic->parody.'</span></a></div>'; ?></div><?php endif; ?>
                    <?php if ($comic->tags) : ?><div class="row"><?php echo '<div class="cell"><b>'._('Tag').'</b>:</div> <div class="cell">'; foreach($comic->tags as $value){ echo '<a href="'.site_url('tag/'.$value->stub).'"><span class="label label-default">'.$value->name.'</span></a> ';} echo '</div>';?></div><?php endif; ?>
				 	<?php if ($comic->description) : ?><div class="row"><?php echo '<div class="cell"><b>'._('Description').'</b>:</div> <div class="cell">'.$comic->description.'</div>';?></div><?php endif; ?> 
			</div>
            </div>
        </div>
        
	<div class="list">
		<?php
		echo '<div class="title">'._('Chapters available for').' '.$comic->name.'</div>';
		$current_volume = "";
		$opendiv = false;

		foreach ($chapters as $key => $chapter)
		{
			if ($current_volume != $chapter->volume)
			{
				if ($opendiv === true)
				{
					echo '</div>';
				}

                $current_volume = $chapter->volume;
			    $opendiv = true;

				echo '<div class="group">';
				if ($current_volume > 0) {
					echo '<div class="title">'.$chapter->download_volume_url(NULL, 'fleft small')._('Volume').' '.str_pad($current_volume, 2, '0', STR_PAD_LEFT).'</div>';
				} else {
					echo '<div class="title">'._('Chapters').'</div>';
				}
			}

			echo '<div class="element">'.$chapter->download_url(NULL, 'fleft small').'
					<div class="title">' . $chapter->url($chapter->title(false)) . '</div>
					<div class="meta_r">' . $chapter->downloads . ' ' . _('downloads'). ', ' . _('by') . ' ' . $chapter->team_url() . ', ' . $chapter->date() . ' ' . $chapter->edit_url() . '</div>
				</div>';
		}

		echo '</div>';
		?>
	</div>

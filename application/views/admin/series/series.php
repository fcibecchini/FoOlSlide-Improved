<?php
$this->buttoner[] = array(
	'text' => _('Delete Series'),
	'href' => site_url('/admin/series/delete/serie/'.$comic->id),
	'plug' => _('Do you really want to delete this series and its chapters?'),
	'class' => "btn-danger"
);
?>
<div class="table">
	<h3><?php echo _('Series Information'); ?> <?php echo buttoner(); ?></h3>
	<?php
		echo form_open_multipart("", array('class' => 'form-stacked'));
		echo $table;
		echo form_close();
	?>
</div>

<br/>

<?php 
// $current_volume = "";
// foreach ($chapters as $item)
// {
// 	if ($current_volume != $item->volume && $chapters->result_count() > 1)
// 	{
// 		echo '<div class="table"><h3>' . _('Download Volume ') . $item->volume . '</h3>';
// 		echo '<div class="input"><textarea cols="1" rows="1" class="form-control" onFocus="this.select()">'.$item->download_volume_href().'</textarea>';
// 		echo '</div></div><br/>';

// 		$current_volume = $item->volume;
// 	}
// }
?>

<?php
if (isset($chapters_by_volume) && count($chapters_by_volume) > 0)
{
	foreach ($chapters_by_volume as $volume => $volume_chapters)
	{
		$share_rows = 1;
		$share_code = $volume_chapters[0]->share_volume();
		$share_title = ((int) $volume > 0) ? sprintf(_('Share Volume %s'), $volume) : _('Share');
		?>
<div class="table">
	<h3><?php echo $share_title; ?></h3>
	<?php
		echo form_open('', array('class' => 'form-stacked'));
		echo '<div class="input"><textarea cols="120" rows="2" class="form-control" style="width: 100%; min-width: 48rem;" onFocus="this.select()">';
		echo $share_code;
		echo '</textarea></div>';
		echo form_close();
	?>
</div>

<br/>
		<?php
	}
}

?>

<?php
	$this->buttoner = array(
		array(
			'href' => site_url('/admin/series/add_new/'.$comic->stub),
			'text' => _('Add Chapter'),
		)
	);
	
	if($this->tank_auth->is_admin())
	{
		$this->buttoner[] = array(
			'href' => site_url('/admin/series/import/'.$comic->stub),
			'text' => _('Import From Folder')
		);
	}
?>
<div class="table" style="padding-bottom: 15px">
	<h3><?php echo _('Chapters'); ?> <?php echo buttoner(); ?></h3>
	<div class="list chapters">
	<?php
		foreach ($chapters as $item)
		{
			echo '<div class="item">
				<div class="title"><a href="'.site_url("admin/series/series/".$comic->stub."/".$item->id).'">'. $item->title().'</a></div>
				<div class="smalltext info">
					Chapter #'.$item->chapter.'
					Sub #'.$item->subchapter;
						if(isset($item->jointers))
						{
							echo ' By ';
							foreach($item->jointers as $key2 => $jointe)
							{
								if($key2>0) echo " | ";
								echo '<a href="'.site_url("/admin/users/teams/".$jointe->stub).'">'.$jointe->name.'</a>';
							}
						}
						else echo ' By <a href="'.site_url("/admin/users/teams/".$item->team_stub).'">'.$item->team_name.'</a>';
						echo '</div>
				<div class="smalltext">
					'._('Quick tools').': 
						<a href="'.site_url("admin/series/delete/chapter/".$item->id).'" onclick="confirmPlug(\''.site_url("admin/series/delete/chapter/".$item->id).'\', \''._('Do you really want to delete this chapter and its pages?'). '\'); return false;">' . _('Delete') . '</a> |
						<a href="';
							echo $item->href();
			echo '">' . _('Read') . '</a>
				</div>';
			echo '</div>';
		}
	?>
	</div>
</div>

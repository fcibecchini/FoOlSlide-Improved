<?php
if (! defined ( 'BASEPATH' ))
	exit ( 'No direct script access allowed' );
?>

<div class="list series">
	<div class="title">
		<a href="<?php echo site_url('tags') ?>"><?php echo _('Tags List'); ?></a>
	</div>
	<?php
	foreach ($tags as $key => $tag ) 
	{
		$name = URIpurifier($tag->name);
		echo '<div class="group">';
		if($tag->get_thumb()) echo '<a href="'.base_url ().'tag/'.$name.'"><img class="preview" src="'.$tag->get_thumb(TRUE).'" /></a>';
		echo '<div class="title"><a href="'.base_url ().'tag/'.$name.'">' .$tag->name. '</a></div>';
		echo '<div class="element"><div class="title">' . $tag->description . '</div></div></div>';
	}
	
	echo prevnext('tags/', $tags);
	?>
</div>

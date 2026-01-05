<?php
if (! defined ( 'BASEPATH' ))
	exit ( 'No direct script access allowed' );
?>

<div class="list">
	<div class="title">
		<a href="<?php echo site_url($link) ?>"><?php echo $title; ?></a>
	</div>
	<?php
	echo form_open("search_".$param."/");
	echo form_input(array('name' => 'search', 'placeholder' => _('To search, type and hit enter'), 'id' => 'menusearchbox', 'class' => 'menu'));
	echo form_close();

	$old = '';
	foreach ( $comics as $key => $comic ) {
		$current = $comic->$param_stub;
		if ($current !== $old)
			echo '<a class="attribute-row" href="' . base_url () . $param . '/'. $comic->$param_stub .'">' . $comic->$param . '</a>';
		$old = $current;
	}
	echo prevnext($link.'/', $comics);
	?>
</div>

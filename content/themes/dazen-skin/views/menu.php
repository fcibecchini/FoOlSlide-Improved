<?php
if (! defined ( 'BASEPATH' ))
	exit ( 'No direct script access allowed' );
?>

<div class="list">
	<div class="title">
		<a href="<?php echo site_url($link) ?>"><?php echo $title; ?></a>
	</div>
	<?php
	$items_name = isset($items_name) ? $items_name : 'comics';
	$items = isset($items) ? $items : $$items_name;
	$item_name_field = isset($item_name_field) ? $item_name_field : $param;
	$item_stub_field = isset($item_stub_field) ? $item_stub_field : $param_stub;
	$item_link_prefix = isset($item_link_prefix) ? $item_link_prefix : $param;
	$search_action = isset($search_action) ? $search_action : "search_".$param."/";

	echo form_open($search_action);
	echo form_input(array('name' => 'search', 'placeholder' => _('To search, type and hit enter'), 'id' => 'menusearchbox', 'class' => 'menu'));
	echo form_close();

	$old = '';
	foreach ( $items as $key => $item ) {
		$current = $item->$item_stub_field;
		if ($current !== $old)
			echo '<a class="attribute-row" href="' . base_url () . $item_link_prefix . '/'. $item->$item_stub_field .'">' . $item->$item_name_field . '</a>';
		$old = $current;
	}
	echo prevnext($link.'/', $items);
	?>
</div>

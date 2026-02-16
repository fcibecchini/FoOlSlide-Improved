<?php
if (! defined ( 'BASEPATH' ))
	exit ( 'No direct script access allowed' );
?>
<style>
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
  content: "\f002"; /* fa-search */
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

/* spazio prima del bottone submit */
.tag-search .form-group:last-of-type {
  margin-top: 10px;
}

/* Label "Generi" come titolo elegante */
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


</style>
<div class="list series">
	<div class="title">
		<a href="<?php echo site_url('tags') ?>"><?php echo _('Tags List'); ?></a>
	</div>


	<div class="tag-search">
		<?php echo get_searchtags_bar();?>
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

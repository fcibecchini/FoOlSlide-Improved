<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); ?>

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
		// Apertura griglia GLOBALE
		echo '<div class="elements">';

foreach ($chapters as $chapter) {

    // --- Thumb sicura: prende la prima disponibile ---
    $thumb_url = null;
    if (!empty($chapter->thumb)) {
        $thumb_url = $chapter->thumb;
    } elseif (method_exists($chapter, 'get_thumb')) {
        $t = $chapter->get_thumb();
        if (!empty($t)) $thumb_url = $t;
    } elseif (isset($chapter->comic)) {
        if (!empty($chapter->comic->thumb)) {
            $thumb_url = $chapter->comic->thumb;
        } elseif (method_exists($chapter->comic, 'get_thumb')) {
            $t = $chapter->comic->get_thumb();
            if (!empty($t)) $thumb_url = $t;
        }
    }

    echo '<article class="element">';

		$link_raw = $chapter->comic->url();
$comic_href = '#';
if (preg_match('/href=["\']([^"\']+)["\']/', $link_raw, $m)) {
$comic_href = $m[1];
}

echo '<a class="thumb" href="'.$comic_href.'">
		<img class="cover" src="'.$chapter->comic->get_thumb().'" alt="cover" loading="lazy">
	</a>';


				// NOME FUMETTO (link completo se disponibile)
        if (isset($chapter->comic)) {
            if (method_exists($chapter->comic, 'url')) {
                echo '<div class="title">'.$chapter->comic->url().'</div>';
            } elseif (isset($chapter->comic->name)) {
                echo '<div class="title">'.htmlspecialchars($chapter->comic->name, ENT_QUOTES, 'UTF-8').'</div>';
            }
        }


        // TITOLO CAPITOLO (usa il link completo che già genera url())
        if (method_exists($chapter, 'url')) {
            echo '<div class="comic">'.$chapter->url().'</div>';
        } else {
            // fallback senza link
            $tit = isset($chapter->title) ? $chapter->title : 'Chapter';
            echo '<div class="comic">'.htmlspecialchars($tit, ENT_QUOTES, 'UTF-8').'</div>';
        }



        // TAG (opzionale)
        if (isset($chapter->comic->tags) && is_array($chapter->comic->tags) && count($chapter->comic->tags) > 0) {
            echo '<div class="badges">';
            $tags = $chapter->comic->tags;
            $max = min(3, count($tags));
            for ($i = 0; $i < $max; $i++) {
                echo '<a href="'.site_url('tag/'.$tags[$i]->stub).'" class="label label-default">'.$tags[$i]->name.'</a> ';
            }
            if (count($tags) > 3) echo '<span class="label label-default">…</span>';
            echo '</div>';
        }

        // PARODY (opzionale)
        if (!empty($chapter->comic->parody)) {
            echo '<div class="badges"><a href="'.site_url('parody/'.$chapter->comic->parody_stub).'" class="label label-success">'.$chapter->comic->parody.'</a></div>';
        }

        // META (difensivo)
        echo '<div class="meta_r">';
            if (!empty($is_download) && isset($chapter->downloads)) {
                echo intval($chapter->downloads) . ' ' . _('downloads') . ', ';
            }
            $team_html = (method_exists($chapter, 'team_url')) ? $chapter->team_url() : '';
            $date_html = (method_exists($chapter, 'date')) ? $chapter->date() : '';
            $edit_html = (method_exists($chapter, 'edit_url')) ? $chapter->edit_url() : '';
            //echo _('by').' '.($team_html ?: _('unknown')).', '.($date_html ?: '').' '.($edit_html ?: '');
						echo "pubblicato il ".($date_html ?: '');
        echo '</div>';

    echo '</article>';
}

echo '</div>'; // .elements
echo prevnext($link.'/', $chapters);
?>

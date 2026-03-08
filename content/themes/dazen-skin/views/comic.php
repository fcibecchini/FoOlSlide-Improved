<?php if (!defined('BASEPATH')) exit('No direct script access allowed'); ?>

<?php
// Dati comodo
$cover = method_exists($comic, 'get_thumb') ? $comic->get_thumb() : '';
$author_html = $comic->author ? '<a href="'.site_url('author/'.$comic->author_stub).'">'.htmlspecialchars($comic->author, ENT_QUOTES, 'UTF-8').'</a>' : '';
$artist_html = $comic->artist ? htmlspecialchars($comic->artist, ENT_QUOTES, 'UTF-8') : '';
$uploader_html = !empty($user) ? htmlspecialchars($user, ENT_QUOTES, 'UTF-8') : '';
$type_html = ($comic->typeh_id != 0 && !empty($type)) ? '<a href="'.site_url('directory/'.$type->stub).'" class="label label-primary">'.$type->name.'</a>' : '';
$parody_html = $comic->parody ? '<a href="'.site_url('parody/'.$comic->parody_stub).'" class="label label-success">'.htmlspecialchars($comic->parody, ENT_QUOTES, 'UTF-8').'</a>' : '';
$tags_arr = is_array($comic->tags) ? $comic->tags : [];
?>

<!-- ============ TESTATA FUMETTO ============ -->
<section class="comic-hero">
  <?php if ($cover): ?>
    <div class="comic-hero__cover">
      <img src="<?php echo $cover; ?>" alt="<?php echo htmlspecialchars($comic->name, ENT_QUOTES, 'UTF-8'); ?>" loading="lazy">
    </div>
  <?php endif; ?>

  <div class="comic-hero__body">
    <h1 class="comic-hero__title"><?php echo htmlspecialchars($comic->name, ENT_QUOTES, 'UTF-8'); ?></h1>

    <div class="comic-hero__meta">
      <?php if ($author_html): ?>
        <div class="meta-row">
          <div class="meta-key"><?php echo _('Author'); ?></div>
          <div class="meta-val"><?php echo $author_html; ?></div>
        </div>
      <?php endif; ?>

      <?php if ($artist_html): ?>
        <div class="meta-row">
          <div class="meta-key"><?php echo _('Artist'); ?></div>
          <div class="meta-val"><?php echo $artist_html; ?></div>
        </div>
      <?php endif; ?>

      <?php if ($uploader_html): ?>
        <div class="meta-row">
          <div class="meta-key"><?php echo _('Uploader'); ?></div>
          <div class="meta-val"><?php echo $uploader_html; ?></div>
        </div>
      <?php endif; ?>

      <?php if ($type_html): ?>
        <div class="meta-row">
          <div class="meta-key"><?php echo _('Type'); ?></div>
          <div class="meta-val"><?php echo $type_html; ?></div>
        </div>
      <?php endif; ?>

      <?php if ($parody_html): ?>
        <div class="meta-row">
          <div class="meta-key"><?php echo _('Parody'); ?></div>
          <div class="meta-val"><?php echo $parody_html; ?></div>
        </div>
      <?php endif; ?>

      <?php if (!empty($tags_arr)): ?>
        <div class="meta-row">
          <div class="meta-key"><?php echo _('Tag'); ?></div>
          <div class="meta-val">
            <?php foreach ($tags_arr as $tg): ?>
              <a href="<?php echo site_url('tag/'.$tg->stub); ?>" class="label label-default"><?php echo htmlspecialchars($tg->name, ENT_QUOTES, 'UTF-8'); ?></a>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <?php if (!empty($comic->description)): ?>
      <div class="comic-hero__desc">
        <div class="desc-label"><?php echo _('Description'); ?></div>
        <div class="desc-text"><?php echo $comic->description; ?></div>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- ============ LISTA CAPITOLI ============ -->
<div class="list list--chapters">
  <div class="title"><?php echo _('Chapters available for').' '.htmlspecialchars($comic->name, ENT_QUOTES, 'UTF-8'); ?></div>

  <?php
    $current_volume = null;
    $open = false;

    foreach ($chapters as $chapter):
      // Cambio volume → chiudo precedente e apro gruppo nuovo
      if ($current_volume !== $chapter->volume) {
        if ($open) echo '</div>'; // chiude .group-grid
        $current_volume = $chapter->volume;
        $open = true;

        echo '<div class="group">';
        if ($current_volume > 0) {
          echo '<div class="title">'.
                $chapter->download_volume_url(NULL, 'fleft small').' '.
                _('Volume').' '.str_pad($current_volume, 2, '0', STR_PAD_LEFT).
               '</div>';
        } else {
          echo '<div class="title">'._('Chapters').'</div>';
        }
        echo '<div class="group-grid">'; // griglia capitoli
      }

      // Card capitolo
      echo '<article class="chapter-card">'.
              $chapter->download_url(NULL, 'fleft small'). // genera thumb/anteprima
              '<div class="chapter-card__title">'.$chapter->url($chapter->title(false)).'</div>'.
              '<div class="chapter-card__meta">'.
                intval($chapter->downloads).' '._('downloads').', '.$chapter->date().' '.$chapter->edit_url().
              '</div>'.
           '</article>';

    endforeach;

    if ($open) {
      echo '</div>';   // .group-grid
      echo '</div>';   // .group
    }
  ?>
</div>

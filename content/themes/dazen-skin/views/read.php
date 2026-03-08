<?php
if (!defined('BASEPATH'))
  exit('No direct script access allowed');
?>

<div class="panel">
  <div class="topbar">
    <div>
      <div class="topbar_left">
        <h1 class="tbtitle dnone"><?php echo $comic->url() ?> :: <?php echo $chapter->url() ?></h1>

        <!-- Nome fumetto (sempre visibile) -->
        <div class="tbtitle dropdown_parent">
          <div class="text_only">
            <?php echo '<a href="' . $comic->href() . '">' . ((strlen($comic->title()) > 40) ? (substr($comic->title(), 0, 40) . '...') : $comic->title()) . '</a>' ?>
          </div>
        </div>

        <!-- Selettore CAPITOLO: versione desktop/tablet (ha .mmh che il tema nasconde su mobile) -->
        <div class="tbtitle dropdown_parent mmh">
          <div class="text">
            <?php echo '<a href="' . $chapter->href() . '">' . ((strlen($chapter->title()) > 40) ? (substr($chapter->title(), 0, 40) . '...') : $chapter->title()) . '</a>' ?> ⤵
          </div>
          <?php
            echo '<ul class="dropdown">';
            foreach ($chapters->all as $ch)
            {
              echo '<li>' . $ch->url() . '</li>';
            }
            echo '</ul>';
          ?>
        </div>

        <!-- Selettore CAPITOLO: versione MOBILE (nuovo) -->
        <div class="tbtitle dropdown_parent chapter-picker-mobile">
          <div class="text">Capitoli ⤵</div>
          <?php
            echo '<ul class="dropdown">';
            foreach ($chapters->all as $ch)
            {
              echo '<li>' . $ch->url() . '</li>';
            }
            echo '</ul>';
          ?>
        </div>

        <?php echo $chapter->download_url(NULL, "fleft larg"); ?>
      </div>

      <div class="topbar_right">
        <!-- Selettore PAGINA (totale) -->
        <div class="tbtitle dropdown_parent dropdown_right mmh">
          <div class="text"><?php echo count($pages); ?> ⤵</div>
          <?php
            $url = $chapter->href();
            echo '<ul class="dropdown" style="width:90px;">';
            for ($i = 1; $i <= count($pages); $i++)
            {
              echo '<li><a href="' . $url . 'page/' . $i . '" onClick="changePage(' . ($i - 1) . '); return false;">' . _("Page") . ' ' . $i . '</a></li>';
            }
            echo '</ul>';
          ?>

        </div>

				<?php $total_pages = count($pages); ?>
					<div class="tbtitle dropdown_parent dropdown_right dh">
					  <div class="text" id="page-picker-mobile-label">
					    <?php echo _('Page').' '.$current_page.' / '.$total_pages; ?> ⤵
					  </div>
					  <ul class="dropdown" style="width: 160px;">
					    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
					      <li>
					        <a href="<?php echo $chapter->href().'page/'.$i; ?>"
					           onclick="changePage(<?php echo $i - 1; ?>); return false;">
					           <?php echo _('Page').' '.$i; ?>
					        </a>
					      </li>
					    <?php endfor; ?>
					  </ul>
					</div>



        <div class="divider mmh"></div>

        <!-- “Finestra” di numeri vicino alla pagina corrente -->
        <span class="numbers mmh">
          <?php
            $start = max(1, $current_page - 2);
            $end   = min(count($pages), $current_page + 2);

            for ($i = $start; $i <= $end; $i++) {
                $current = ((count($pages) / 100 > 1 && $i / 100 < 1) ? '0' : '')
                         . ((count($pages) / 10 > 1 && $i / 10 < 1) ? '0' : '')
                         . $i;

                echo '<div class="number number_' . $i . ' '
                   . (($i == $current_page) ? 'current_page' : '')
                   . '"><a href="' . $chapter->href . 'page/' . $i . '">'
                   . $current . '</a></div>';
            }
          ?>
        </span>
      </div>
    </div>
    <div class="clearer"></div>
  </div>
</div>


<div id="page">
  <div class="inner">
    <a href="<?php echo $chapter->next_page($current_page); ?>" onClick="return nextPage();" >
      <img class="open" src="<?php echo $pages[$current_page - 1]['url'] ?>" />
    </a>
  </div>
</div>

<div class="clearer"></div>

<div id="bottombar">
  <div class="pagenumber">
    <?php echo _('Page') . ' ' . $current_page ?>
  </div>
</div>

<!-- Overlay click zones per navigazione sinistra/destra -->
<style>
#page .inner { position: relative; }
#page .inner .nav-zone{
  position: absolute; top:0; bottom:0;
  width: 40%;
  background: transparent;
  border: 0;
  padding: 0; margin: 0;
  cursor: pointer;
  outline: none;
  z-index: 20;
  pointer-events: auto;
  -webkit-tap-highlight-color: transparent;
}
#page .inner img.open{ position: relative; z-index: 1; }
#page .inner .nav-left  { left: 0; }
#page .inner .nav-right { right: 0; }
#page .inner .nav-zone:focus{ outline: none; }
#page .inner .nav-zone:focus-visible{
  outline: 2px solid #B24759;
  outline-offset: -2px;
}
</style>

<script type="text/javascript">
  var title = document.title;
  var pages = <?php echo json_encode($pages); ?>;

  var next_chapter   = "<?php echo $next_chapter; ?>";
  var preload_next   = 5;
  var preload_back   = 2;
  var current_page   = <?php echo $current_page - 1; ?>;
  var initialized    = false;

  var base_url = '<?php echo $chapter->href() ?>';
  var site_url = '<?php echo site_url() ?>';

  var gt_page           = '<?php echo addslashes(_("Page")) ?>';
  var gt_key_suggestion = '<?php echo addslashes(_("Use W-A-S-D or the arrow keys to navigate")) ?>';
  var gt_key_tap        = '<?php echo addslashes(_("Double-tap to change page")) ?>';

  function resetReaderScroll()
  {
    var readerTop = Math.max(jQuery('#page').offset().top - 6, 0);
    jQuery(window).scrollTop(readerTop);
    jQuery('html, body').scrollTop(readerTop);
    jQuery('#page').scrollLeft(0);
  }

  function isMobileReader()
  {
    return window.matchMedia('(max-width: 768px)').matches;
  }

  function resetMobileViewportZoom()
  {
    if (!isMobileReader()) return;

    var viewport = document.querySelector('meta[name="viewport"]');
    if (!viewport) return;

    var baseContent = viewport.dataset.baseContent || viewport.getAttribute('content') || 'width=device-width, initial-scale=1, maximum-scale=10, user-scalable=yes';
    viewport.dataset.baseContent = baseContent;
    viewport.setAttribute('content', 'width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no');

    window.setTimeout(function(){
      viewport.setAttribute('content', baseContent);
      resetReaderScroll();
    }, 120);
  }

  function resetPageView()
  {
    jQuery('#page .inner img.open').css({
      'transform': '',
      '-webkit-transform': '',
      'transform-origin': '',
      '-webkit-transform-origin': ''
    });
    resetMobileViewportZoom();
  }

  function changePage(id, noscroll, nohash)
  {
    id = parseInt(id);
    if (initialized && id == current_page) return false;

    if(!initialized) {
      create_message('key_suggestion', 4000, gt_key_suggestion);
    }

    initialized = true;

    if (id > pages.length - 1) {
      location.href = next_chapter;
      return false;
    }
    if (id < 0) {
      current_page = 0;
      id = 0;
    }

    preload(id);
    current_page = id;

    jQuery("html, body").stop(true,true);
    if(!noscroll) resetPageView();

    if (pages[id].loaded !== true) {
      jQuery('#page .inner img.open').css({'opacity':'0'}).attr('src', pages[id].url);
    } else {
      jQuery('#page .inner img.open').css({'opacity':'1'}).attr('src', pages[id].url);
    }

    resizePage(id);

    if(!noscroll) resetReaderScroll();

    if(!nohash) History.pushState(null, null, base_url+'page/' + (current_page + 1));
    document.title = gt_page+' ' + (current_page+1) + ' :: ' + title;

    update_numberPanel();
    jQuery('#pagelist .current').removeClass('current');

    jQuery("#ads_top_banner.iframe iframe").attr("src", site_url + "content/ads/ads_top.html");
    jQuery("#ads_bottom_banner.iframe iframe").attr("src", site_url + "content/ads/ads_bottom.html");

    return false;
  }

  function resizePage(id) {
    var viewport_width = Math.min(jQuery(window).width(), jQuery(document).width());
    var page_width  = parseInt(pages[id].width);
    var page_height = parseInt(pages[id].height);

    // Mobile
    if (viewport_width <= 768) {
      var new_width  = viewport_width - 20; // padding
      var new_height = Math.floor((new_width * page_height) / page_width);

      jQuery('#page').css({'max-width': '100%', 'overflow': 'hidden'});
      jQuery('#page .inner').css({'width': '100%', 'text-align': 'center'});
      jQuery('#page .inner img.open').css({
        'max-width': '100%', 'width': new_width, 'height': new_height, 'object-fit': 'contain'
      });

      isSpread = false;
      delete_message('is_spread');
      return;
    }

    // Desktop (logica originale)
    var nice_width = 980;
    var perfect_width = 980;

    if (viewport_width > 1200) { nice_width = 1120; perfect_width = 1000; }
    if (viewport_width > 1600) { nice_width = 1400; perfect_width = 1300; }
    if (viewport_width > 1800) { nice_width = 1600; perfect_width = 1500; }

    if (page_width > nice_width && (page_width/page_height) > 1.2) {
      var width, height;
      if (page_height < 1610) {
        width  = page_width;
        height = page_height;
      } else {
        height = 1600;
        width  = (height*page_width)/(page_height);
      }
      jQuery("#page").css({'max-width': 'none', 'overflow':'auto'});
      jQuery("#page .inner img.open").css({'max-width':'99999px'}).attr({width:width, height:height});

      if (jQuery("#page").width() < jQuery("#page .inner img.open").width()) {
        isSpread = true;
        create_message('is_spread', 3000, 'Tap the arrows twice to change page');
      } else {
        jQuery("#page").css({'max-width': width+10, 'overflow':'hidden'});
        isSpread = false;
        delete_message('is_spread');
      }
    } else {
      var width, height;
      if ((page_width < nice_width) && (viewport_width > page_width + 10)) {
        width  = page_width;
        height = page_height;
      } else {
        width  = (viewport_width > perfect_width) ? perfect_width : viewport_width - 10;
        height = (page_height*width)/page_width;
      }
      jQuery('#page .inner img.open').attr({width:width, height:height});
      jQuery("#page").css({'max-width':(width + 10) + 'px','overflow':'hidden'});
      jQuery("#page .inner img.open").css({'max-width':'100%'});
      isSpread = false;
      delete_message('is_spread');
    }
  }

  function nextPage(){ changePage(current_page+1); return false; }
  function prevPage(){ changePage(current_page-1); return false; }

  function preload(id)
  {
    var array = [];
    var arraydata = [];
    for(var i = -preload_back; i < preload_next; i++) {
      if(id+i >= 0 && id+i < pages.length) {
        array.push(pages[(id+i)].url);
        arraydata.push(id+i);
      }
    }

    jQuery.preload(array, {
      threshold: 40,
      enforceCache: true,
      onComplete:function(data) {
        var idx = data.index;
        var page = arraydata[idx];
        pages[page].loaded = true;
        jQuery('.numbers .number_'+ (page+1)).addClass('loaded');
        if(current_page == page) {
          jQuery('#page .inner img.open').animate({'opacity':'1.0'}, 800).attr('src', pages[current_page].url);
        }
      }
    });
  }

  function create_numberPanel() {
    var result = "";
    for (var j = 1; j <= pages.length; j++) {
      var nextnumber =
        (((j / 1000 < 1) && (pages.length >= 1000)) ? '0' : '') +
        (((j / 100  < 1) && (pages.length >= 100 )) ? '0' : '') +
        (((j / 10   < 1) && (pages.length >= 10  )) ? '0' : '') + j;

      result += "<div class='number number_" + j + " dnone'>" +
                  "<a href='" + base_url + "page/" + j +
                  "' onClick='changePage(" + (j - 1) + "); return false;'>" +
                  nextnumber +
                "</a></div>";
    }
    jQuery(".topbar_right .numbers").html(result);
  }

  function update_numberPanel()
  {
    jQuery('.topbar_right .number.current_page').removeClass('current_page');
    jQuery('.topbar_right .number_'+(current_page+1)).addClass('current_page');
    jQuery('.topbar_right .number').addClass('dnone');
    for (var i = ((val = current_page - 1) <= 0)?(1):val; i <= pages.length && i < current_page + 4; i++) {
      jQuery('.number_'+i).removeClass('dnone');
    }
    jQuery('.pagenumber').html(gt_page + ' ' + (current_page+1));
		// 🔹 aggiorna il label del selettore pagine mobile
  var $lab = jQuery('#page-picker-mobile-label');
  if ($lab.length) {
    $lab.text(gt_page + ' ' + (current_page + 1) + ' / ' + pages.length + ' ⤵');
  }
  }

  function chapters_dropdown(){ location.href = jQuery('#chapters_dropdown').val(); }
  function togglePagelist(){ jQuery('#pagelist').slideToggle(); jQuery.scrollTo('#pagelist', 300); }

  var isSpread = false;
  var button_down = false;
  var button_down_code;
  var timeStamp37 = 0, timeStamp39 = 0;

  jQuery(document).ready(function() {

    // Tastiera
    jQuery(document).keydown(function(e){
      if(!button_down && !jQuery("input").is(":focus")) {
        button_down = true;
        var code = e.keyCode || e.which;

        if(code==37 || code==65){ // left / A
          if(!isSpread) prevPage();
          else if((e.timeStamp - timeStamp37 < 400) && (e.timeStamp - timeStamp37 > 150)) prevPage();
          timeStamp37 = e.timeStamp;
          e.preventDefault();
          button_down_code = setInterval(function(){ if (button_down) jQuery('#page').scrollTo("-=13",{axis:"x"}); }, 20);
        }
        if(code==39 || code==68){ // right / D
          if(!isSpread) nextPage();
          else if((e.timeStamp - timeStamp39 < 400) && (e.timeStamp - timeStamp39 > 150)) nextPage();
          timeStamp39 = e.timeStamp;
          e.preventDefault();
          button_down_code = setInterval(function(){ if (button_down) jQuery('#page').scrollTo("+=13",{axis:"x"}); }, 20);
        }
        if(code==40 || code==83){ // down / S
          e.preventDefault();
          button_down_code = setInterval(function(){ if (button_down) jQuery.scrollTo("+=13"); }, 20);
        }
        if(code==38 || code==87){ // up / W
          e.preventDefault();
          button_down_code = setInterval(function(){ if (button_down) jQuery.scrollTo("-=13"); }, 20);
        }
      }
    });

    jQuery(document).keyup(function(){ button_down_code = window.clearInterval(button_down_code); button_down = false; });

    // History
    jQuery(window).bind('statechange',function(){
      var State = History.getState();
      var url = parseInt(State.url.substr(State.url.lastIndexOf('/')+1));
      changePage(url-1, false, true);
      document.title = gt_page+' ' + (current_page+1) + ' :: ' + title;
    });

    // Stato iniziale
    var State = History.getState();
    var url = State.url.substr(State.url.lastIndexOf('/')+1);
    if(url < 1) url = 1;
    current_page = url-1;
    History.pushState(null, null, base_url+'page/' + (current_page+1));
    changePage(current_page, false, true);
    create_numberPanel();
    update_numberPanel();
    document.title = gt_page+' ' + (current_page+1) + ' :: ' + title;

    // Resize
    jQuery(window).resize(function() { resizePage(current_page); });

    // Rimuovo l'anchor attorno all’immagine e creo le "zone" cliccabili
    var $inner = jQuery('#page .inner');
    var $img = $inner.find('a img.open').clone();
    $inner.empty().append($img);

    $inner.append('<button type="button" class="nav-zone nav-left" aria-label="Previous page"></button>'+
                  '<button type="button" class="nav-zone nav-right" aria-label="Next page"></button>');

    $inner.find('.nav-left').bind('click', function(e){ e.preventDefault(); prevPage(); });
    $inner.find('.nav-right').bind('click', function(e){ e.preventDefault(); nextPage(); });
    // Desktop fallback: click left/right area even if overlay buttons are not hit.
    $inner.bind('click', function(e){
      if (jQuery(e.target).closest('.nav-zone').length) return;
      var x = e.pageX - $inner.offset().left;
      var w = $inner.width();
      if (x < w * 0.4) prevPage();
      else if (x > w * 0.6) nextPage();
    });

    // Touch: drag solo se zoom; altrimenti swipe/tap area
    var touchState = { startXRel:0, startY:0, lastX:0, lastY:0, isDragging:false };

    function getCurrentScale(element) {
      var transform = window.getComputedStyle(element).transform;
      if (transform === 'none') return 1;
      var matrix = new WebKitCSSMatrix(transform);
      return matrix.a;
    }

    $inner.bind('touchstart', function(event){
      if (event.originalEvent.touches.length === 1) {
        var imgEl = $inner.find('img.open')[0];
        var scale = getCurrentScale(imgEl);

        var rect = this.getBoundingClientRect();
        var t = event.originalEvent.touches[0];

        if (scale > 1) {
          touchState.isDragging = true;
          touchState.startXRel = t.clientX - rect.left;
          touchState.startY    = t.pageY;
          touchState.lastX     = t.pageX;
          touchState.lastY     = t.pageY;
          event.preventDefault();
        } else {
          touchState.isDragging = false;
          touchState.startXRel  = t.clientX - rect.left;
          touchState.startY     = t.pageY;
        }
      }
    });

    $inner.bind('touchmove', function(event){
      if (touchState.isDragging && event.originalEvent.touches.length === 1) {
        var touch = event.originalEvent.touches[0];
        var img = $inner.find('img.open');
        var matrix = new WebKitCSSMatrix(img.css('transform'));

        var deltaX = touch.pageX - touchState.lastX;
        var deltaY = touch.pageY - touchState.lastY;

        img.css('transform', 'matrix('+matrix.a+', 0, 0, '+matrix.a+', '+(matrix.m41 + deltaX)+', '+(matrix.m42 + deltaY)+')');

        touchState.lastX = touch.pageX;
        touchState.lastY = touch.pageY;
        event.preventDefault();
      }
    });

    $inner.bind('touchend', function(event){
      if (!touchState.isDragging) {
        var rect = this.getBoundingClientRect();
        var endXRel = event.changedTouches[0].clientX - rect.left;
        var moveX = Math.abs(endXRel - touchState.startXRel);
        var moveY = Math.abs(event.changedTouches[0].pageY - touchState.startY);

        if (moveX > moveY && moveX > 10) {
          if (endXRel < touchState.startXRel) nextPage();
          else prevPage();
        } else {
          var w = rect.width;
          if (touchState.startXRel < w * 0.4) prevPage();
          else if (touchState.startXRel > w * 0.6) nextPage();
        }
      }
      touchState.isDragging = false;
    });
  });
</script>

<script>
jQuery(function($){
  var $parents = $('.dropdown_parent');

  function isDesktopHoverable(){
    // true sui device con mouse (no touch)
    return window.matchMedia('(hover: hover) and (pointer: fine)').matches;
  }

  // Handler click SOLO per mobile/tablet (niente hover affidabile)
  function bindMobileClick(){
    $parents.undelegate('.text, .text_only', 'click.dd'); // pulizia eventuali handler precedenti
    $parents.delegate('.text, .text_only', 'click.dd', function(e){
      var $p = $(this).closest('.dropdown_parent');

      // Se non c'è il dropdown, lascia navigare tranquillamente (link puro)
      if (!$p.find('ul.dropdown').length) return;

      // Se ho cliccato un <a> interno, lascia navigare
      if ($(e.target).closest('a').length) return;

      e.preventDefault();
      $parents.not($p).removeClass('open');
      $p.toggleClass('open');
    });

    // Chiudi cliccando fuori o con ESC
    $(document).unbind('click.dd').bind('click.dd', function(e){
      if (!$(e.target).closest('.dropdown_parent').length) $parents.removeClass('open');
    });
    $(document).unbind('keydown.dd').bind('keydown.dd', function(e){
      if (e.key === 'Escape') $parents.removeClass('open');
    });
  }

  // Modalità desktop: niente click handler, usa solo :hover da CSS
  function unbindClicks(){
    $parents.undelegate('.text, .text_only', 'click.dd');
    $(document).unbind('click.dd').unbind('keydown.dd');
    $parents.removeClass('open');
  }

  // Set iniziale
  if (isDesktopHoverable()) unbindClicks();
  else bindMobileClick();

  // Reagisci ai cambi (ridimensionamento / cambio input)
  window.matchMedia('(hover: hover) and (pointer: fine)').addEventListener('change', function(e){
    if (e.matches) unbindClicks();   // passa a hover
    else bindMobileClick();          // torna al click
  });
});
</script>

<script type="text/javascript">
  (function() {
    var po = document.createElement('script'); po.type = 'text/javascript'; po.async = true;
    po.src = 'https://apis.google.com/js/plusone.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(po, s);
  })();
</script>

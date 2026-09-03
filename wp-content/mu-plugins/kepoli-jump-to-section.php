<?php
/**
 * Plugin Name: Kepoli Jump-to-Section (collapsed TOC on long posts)
 * Description: kepoli posts run several screens tall on mobile (measured ~8,200px). For a Facebook reader who
 *   landed looking for one specific answer, a compact, collapsed-by-default "Jump to section" list gives
 *   orientation + scroll-depth without cluttering the view for someone who just wants to read. Built client-
 *   side from the post's own <h2> headings (assigning stable ids where missing, so the FAQPage <h3>s and body
 *   sections both work with the sticky-header scroll offset), gated to posts with >=3 <h2>s so short posts
 *   never get one. Inserted after the opening paragraph (keeps the drop-cap intact). Uses textContent only —
 *   no HTML injection. Depends on the html{scroll-padding-top} rule in kepoli-frontend-polish.php. Reversible.
 *
 * @package Kepoli
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_head', static function (): void {
    if (is_admin() || !is_singular('post')) {
        return;
    }
    echo "\n<style id=\"kepoli-toc-css\">"
        . '.kepoli-toc{margin:1.4em 0 1.6em;border:1px solid var(--line,#e7e2d8);border-radius:var(--radius,10px);background:var(--surface,#fbf9f4);padding:0 16px}'
        . '.kepoli-toc__summary{cursor:pointer;list-style:none;padding:12px 0;min-height:44px;display:flex;align-items:center;font-family:var(--sans,system-ui,sans-serif);font-weight:700;font-size:.88rem;letter-spacing:.02em;color:var(--olive-dark,#4a5a3a)}'
        . '.kepoli-toc__summary::-webkit-details-marker{display:none}'
        . '.kepoli-toc__summary::after{content:"\\25be";margin-inline-start:auto;transition:transform .15s}'
        . '.kepoli-toc[open] .kepoli-toc__summary::after{transform:rotate(180deg)}'
        . '.kepoli-toc__list{list-style:none;margin:0;padding:0 0 10px}'
        . '.kepoli-toc__list li{border-top:1px solid var(--line,#e7e2d8)}'
        . '.kepoli-toc__list a{display:flex;align-items:center;min-height:44px;font-family:var(--sans,system-ui,sans-serif);font-size:.88rem;color:var(--ink,#2b2822);text-decoration:none}'
        . '.kepoli-toc__list a:hover{text-decoration:underline}'
        . "</style>\n";
}, 99);

add_action('wp_footer', static function (): void {
    if (!is_singular('post')) {
        return;
    }
    ?>
<script>
(function(){
  var content=document.querySelector('.entry-content');
  if(!content) return;
  var heads=content.querySelectorAll('h2');
  if(heads.length<3) return;
  var used={};
  var list=document.createElement('ul');
  list.className='kepoli-toc__list';
  heads.forEach(function(h){
    var id=h.id;
    if(!id){
      var base=(h.textContent||'').toLowerCase().trim().replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'')||'section';
      id=base; var n=2;
      while(used[id]||document.getElementById(id)){ id=base+'-'+(n++); }
      h.id=id;
    }
    used[id]=true;
    var li=document.createElement('li');
    var a=document.createElement('a');
    a.href='#'+id;
    a.textContent=h.textContent;
    li.appendChild(a);
    list.appendChild(li);
  });
  var details=document.createElement('details');
  details.className='kepoli-toc';
  var sum=document.createElement('summary');
  sum.className='kepoli-toc__summary';
  sum.textContent='Jump to section';
  details.appendChild(sum);
  details.appendChild(list);
  var firstP=content.querySelector('p');
  if(firstP){ firstP.insertAdjacentElement('afterend', details); }
  else { content.insertBefore(details, content.firstChild); }
})();
</script>
    <?php
});

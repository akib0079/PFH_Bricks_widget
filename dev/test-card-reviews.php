<?php
/**
 * Product card review row.
 *
 *   php dev/test-card-reviews.php
 *
 * The card can show typed text, the shop's live WebwinkelKeur rating, or each
 * product's own WooCommerce rating. The fallback path matters most: a product
 * with no reviews yet must not render nought stars.
 */

require __DIR__ . '/stubs.php';
$dir = dirname( __DIR__ ) . '/pfh-bricks-widgets/';
define('PFH_WIDGETS_VERSION','test'); define('PFH_WIDGETS_DIR',$dir); define('PFH_WIDGETS_URL','./');
require $dir.'includes/class-pfh-helpers.php'; require $dir.'includes/class-pfh-icons.php';
require $dir.'includes/class-pfh-reviews.php'; require $dir.'includes/class-pfh-assets.php';
require $dir.'includes/trait-pfh-design-revision.php';
require $dir.'includes/trait-pfh-product-card.php';
require $dir.'elements/class-pfh-element-products.php';

add_filter('pfh_webwinkelkeur_pre_summary', fn() => ['rating'=>9.7,'count'=>396,'scale'=>10]);

$pass=0;$fail=0;
function ok($l,$c,$d=''){global $pass,$fail; if($c){$pass++;echo "  \033[32m✓\033[0m $l\n";}else{$fail++;echo "  \033[31m✗\033[0m $l".($d?"\n      $d\n":"\n");}}

function row($mode, $card, $fallback = 'shop') {
  $e = new PFH_Element_Products(['id'=>'p']); $e->name='pfh-products'; $e->set_controls();
  $s = []; foreach ($e->controls as $k=>$c) { if (isset($c['default'])) $s[$k]=$c['default']; }
  $s['reviewEnable']=true; $s['reviewMode']=$mode; $s['reviewFallback']=$fallback;
  $e->settings=$s; $e->element['settings']=$s;
  $m = new ReflectionMethod($e,'review_row');
  return $m->invoke($e, $card);
}

echo "\n\033[1mProduct card review row\033[0m\n";

$reviewed  = ['rating'=>4.5,'reviews'=>38];
$unrated   = ['rating'=>0.0,'reviews'=>0];

$r = row('static', $reviewed);
ok('static mode is unchanged', 5.0 === (float)$r['stars'] && '124 reviews' === $r['text'], json_encode($r));

$r = row('shop', $unrated);
ok('shop mode uses the live WebwinkelKeur score', 5.0 === (float)$r['stars'], json_encode($r));
ok('shop mode renumbers the label to the live count', '396 reviews' === $r['text'], json_encode($r));

$r = row('woocommerce', $reviewed);
ok('per-product uses the product’s own rating', 4.5 === (float)$r['stars'] && '38 reviews' === $r['text'], json_encode($r));

$r = row('woocommerce', $unrated, 'shop');
ok('a product with no reviews falls back to the shop figure', '396 reviews' === $r['text'], json_encode($r));

$r = row('woocommerce', $unrated, 'hide');
ok('or the row is hidden entirely', null === $r, json_encode($r));

$r = row('woocommerce', $unrated, 'static');
ok('or the typed text is used', '124 reviews' === $r['text'], json_encode($r));

$r = row('woocommerce', ['rating'=>4.5,'reviews'=>1]);
ok('singular is handled', '1 review' === $r['text'], json_encode($r));

echo "\n$pass passed, $fail failed\n";
exit($fail ? 1 : 0);

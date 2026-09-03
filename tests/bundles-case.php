<?php
/**
 * اختبارات عدّاد عناصر الباقات.
 *
 * ليه: الدالة دي بتخدم خمس صفحات، وبتتكلم مع جدول بلجن خارجي ممكن يختفي.
 * الاختبار بيشغّل كل حالة في **عملية منفصلة** لأن حارس الجدول بيستخدم static
 * بيفضل محفوظ طول العملية.
 *
 * التشغيل: php tests/bundles-test.php     (أو من tools-lint.sh)
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }

define('ABSPATH', true); define('HOUR_IN_SECONDS', 3600);
class FakeWpdb {
    public $prefix='wp_'; public $rows=[]; public $tableExists=true; public $queries=0;
    public function prepare($sql, ...$a){ if(count($a)===1&&is_array($a[0]))$a=$a[0];
        foreach($a as $v) $sql=preg_replace('/%d|%s/', is_int($v)?(string)$v:"'$v'", $sql, 1); return $sql; }
    public function get_var($sql){ $this->queries++;
        if(str_starts_with($sql,'SHOW TABLES')) return $this->tableExists?'wp_asnp_wepb_simple_bundle_items':null; return null; }
    public function get_results($sql){ $this->queries++;
        preg_match('/IN \(([^)]*)\)/',$sql,$m); $ids=array_map('intval',explode(',',$m[1]??''));
        $out=[]; foreach($ids as $id) if(!empty($this->rows[$id])) $out[]=(object)['bundle_id'=>$id,'items'=>$this->rows[$id]];
        return $out; }
}
$GLOBALS['T']=[];
function get_transient($k){ return $GLOBALS['T'][$k] ?? false; }
function set_transient($k,$v,$e){ $GLOBALS['T'][$k]=$v; return true; }
function absint($v){ return abs((int)$v); }
require dirname(__DIR__).'/inc/bundles.php';
$scenario = $argv[1];
$w = new FakeWpdb(); $GLOBALS['wpdb']=$w;
$out = null;
switch($scenario){
  case 'normal':
    $w->rows=[10=>3, 11=>5, 12=>0];
    $out = learnsimply_bundle_item_counts([10,11,12]); break;
  case 'queries':                                  // كام استعلام لـ٥ باقات؟
    $w->rows=[1=>2,2=>2,3=>2,4=>2,5=>2];
    learnsimply_bundle_item_counts([1,2,3,4,5]);
    $out = ['queries'=>$w->queries]; break;
  case 'no_table':                                 // البلجن اتشال
    $w->tableExists=false; $w->rows=[10=>3];
    $out = learnsimply_bundle_item_counts([10]); break;
  case 'empty':  $out = learnsimply_bundle_item_counts([]); break;
  case 'dirty':                                    // مدخلات وسخة
    $w->rows=[7=>4];
    $out = learnsimply_bundle_item_counts([7,7,0,null,'7','abc',-3]); break;
  case 'single': $w->rows=[9=>6]; $out=['n'=>learnsimply_bundle_item_count(9)]; break;
  case 'single_missing': $w->rows=[]; $out=['n'=>learnsimply_bundle_item_count(99)]; break;
  case 'cached':                                   // الحارس بيتكاش؟
    $w->rows=[1=>1]; learnsimply_bundle_item_counts([1]); $q1=$w->queries;
    learnsimply_bundle_item_counts([2]); $out=['first'=>$q1,'total'=>$w->queries]; break;
}
echo json_encode($out, JSON_UNESCAPED_UNICODE), "\n";

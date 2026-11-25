<?php
declare(strict_types=1);
$base=dirname(__DIR__,2).'/reference/pre-rewrite/src/04_analyze/type_model/data/';
require $base.'lifecycle.php';require $base.'definitions.php';
function role(int $n): \type_model\lifecycle_operation_kind {
 return \type_model\lifecycle_operation_kind::from([1=>'default_construct',2=>'destroy',3=>'copy_construct',4=>'move_construct',5=>'copy_assign'][$n]);
}
function operation(int $mode): \type_model\runtime_lifecycle_operation|\type_model\source_lifecycle_operation {
 if($mode<6) {return new \type_model\runtime_lifecycle_operation($mode===1?'q':'p',$mode===2?'other':'id',$mode===3?'other':'copy',$mode===4?'fastcc':'ccc',role($mode===5?4:3));}
 $type=$mode===7?2:1;$link=$mode===8?'other':'copy';$cc=$mode===9?'fastcc':'ccc';$repeat=$mode===10?2:0;$body=$mode===11?1:0;$kind=($mode===12||$mode===20)?4:3;
 $members=[new \type_model\lifecycle_member($mode===13?4:3,$mode===14?1:0,null,role($body!==0?1:3))];
 if($mode===15) {$members[]=new \type_model\lifecycle_member(4,1,null,role(3));}
 if($mode===16||$mode===17||$mode===18) {$members[0]=new \type_model\lifecycle_member(3,0,operation([16=>0,17=>1,18=>6][$mode]),role(3));}
 if($mode===19) {$members=[new \type_model\lifecycle_member(4,1,null,role(3)),new \type_model\lifecycle_member(3,0,null,role(3))];}
 if($mode===20) {$members=[new \type_model\lifecycle_member(3,0,null,role(4))];}
 return new \type_model\source_lifecycle_operation($type,$link,role($kind),$members,$repeat,$cc,$body);
}
function lifetime(array $p,int $changed): \type_model\lifetime_contract {
 $copy=\type_model\copy_kind::from(['unavailable','value','construct'][$p[0]]);
 $cleanup=\type_model\cleanup_kind::from(['none','destroy'][$p[1]]);
 $construction=\type_model\construction_kind::from(['unavailable','zero','construct'][$p[2]]);
 $assignment=\type_model\assignment_kind::from(['unavailable','value','call'][$p[3]]);
 $expiring=\type_model\expiring_construction::from(['unavailable','value','copy','construct'][$p[4]]);
 $ops=[];
 foreach([1=>$p[2]===2,2=>$p[1]===1,3=>$p[0]===2,4=>$p[4]===3,5=>$p[3]===2] as $r=>$required) {$ops[$r]=$required?new \type_model\runtime_lifecycle_operation('p','id'.$r,$r===$changed?'changed':'role'.$r,'ccc',role($r)):null;}
 return new \type_model\lifetime_contract($copy,$cleanup,$ops[2],$ops[3],$construction,$ops[1],$assignment,$ops[5],$expiring,$ops[4]);
}
$out=[];
foreach(json_decode(file_get_contents($argv[1]),true,flags:JSON_THROW_ON_ERROR) as $case) {
 if($case['kind']==='operation') {$a=operation($case['left']);$b=operation($case['right']);}
 else {$a=lifetime($case['a'],0);$b=lifetime($case['b'],$case['change']);}
 $out[]=($a==$b)===$case['equal'];
}
echo json_encode($out,JSON_THROW_ON_ERROR),"\n";

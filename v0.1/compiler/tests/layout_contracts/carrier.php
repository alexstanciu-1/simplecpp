<?php
declare(strict_types=1);
$policy=new \type_model\Lifetime_Policy();$policy->copy=1;
$life=new \type_model\Lifetime_Contract($policy,[]);
$definition=new \type_model\Named_Definition('R','',\type_model\Representation::structure(0,1),$life,null,'',false,false,true);
$config=new \prepare_backend\Backend_Configuration('b','t','d','','','a','r');
$lineage=new \type_model\Type_Lineage();$member=new \type_model\Type_Member(1,'f',true);
$dependency=new \prepare_backend\Layout_Dependency(1,$definition,[$member],[]);
$count=0;
foreach ([['0'],[0.0],[false],[null],[2=>0]] as $offsets) {
 $rejected=false;
 try {$layout=new \prepare_backend\Storage_Layout($definition,$config,[$member],'i32',8,8,$offsets,$lineage,$dependency);}
 catch (\LogicException $error) {$rejected=true;}
 if (!$rejected) {throw new RuntimeException('Invalid offset carrier accepted');}++$count;
}
$rejected=false;
try {$layout=new \prepare_backend\Storage_Layout($definition,$config,[2=>$member],'i32',8,8,[0],$lineage,$dependency);}
catch (\LogicException $error) {$rejected=true;}
if (!$rejected) {throw new RuntimeException('Sparse field carrier accepted');}++$count;
echo $count," carrier rejections passed\n";

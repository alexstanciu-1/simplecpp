<?php
$root=dirname(__DIR__,3).'/compiler/reference/pre-rewrite/src/04_analyze/type_model/data/';
require $root.'lifecycle.php'; require $root.'definitions.php';
$custom=[];$source=[];$create=[];$reverse=[];
foreach(\type_model\lifecycle_operation_kind::cases() as $kind){
    $order=$kind->composition(true);$custom[]=$order->member_kind?->value;
    $source[]=$kind->has_source();$create[]=$kind->creates_destination();$reverse[]=$order->reverse_members;
}
$rejected=false;
try {new \type_model\lifetime_contract(\type_model\copy_kind::unavailable,\type_model\cleanup_kind::none,expiring:\type_model\expiring_construction::copy);}
catch(InvalidArgumentException $e){$rejected=true;}
echo json_encode([$custom,$source,$create,$reverse,$rejected],JSON_THROW_ON_ERROR),"\n";

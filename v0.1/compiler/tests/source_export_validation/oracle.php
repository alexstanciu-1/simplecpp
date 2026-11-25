<?php
declare(strict_types=1);
$base=dirname(__DIR__,2).'/reference/pre-rewrite/src/';
require $base.'04_analyze/type_model/data/lifecycle.php';
require $base.'04_analyze/resolve_types/data/export_identity.php';
require $base.'05_generate_code/prepare_backend/data/source_exports.php';
require $base.'05_generate_code/prepare_backend/source_export_preparation.php';
$roles=[1=>'default_construct',2=>'destroy',3=>'copy_construct',5=>'copy_assign'];
$out=[];
foreach (json_decode(file_get_contents($argv[1]),true,flags:JSON_THROW_ON_ERROR) as $case) {
 $identity=new \resolve_types\export_type_identity(['source','p',['r.phs','',$case['text']],[]],true);
 $out[]=\prepare_backend\Source_Export_Preparation::symbol($identity,\prepare_backend\source_export_role::from($roles[$case['role']]))===$case['symbol'];
}
echo json_encode($out,JSON_THROW_ON_ERROR),"\n";

<?php
$retained=!class_exists('type_model\\Allocation_Effect');
if ($retained) { require __DIR__.'/../../reference/pre-rewrite/src/04_analyze/type_model/data/resources.php'; }
$out=[];
foreach (['acquire','release','transfer','inspect','mutate','observe'] as $name) {
    $kind=$retained ? \type_model\allocation_effect_kind::from($name) : \type_model\Allocation_Effects::parse($name);
    foreach ([-1,0,1] as $owner) {
        foreach ([null,-1,0,1,2] as $destination) {
            try { $effect=new \type_model\Allocation_Effect($kind,$owner,$destination); $out[]=[$name,$effect->owner,$effect->destination]; }
            catch (InvalidArgumentException $e) { $out[]=false; }
        }
    }
}
echo json_encode($out),"\n";

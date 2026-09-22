<?php
// Run retained representation constructors directly, without the compiler session.
$root=dirname(__DIR__,3);
require $root.'/compiler/reference/pre-rewrite/src/04_analyze/type_model/data/semantic_calls.php';
require $root.'/compiler/reference/pre-rewrite/src/04_analyze/type_model/data/representations.php';
$widths=[];
foreach (\type_model\floating_format::cases() as $format) {
    $widths[]=(new \type_model\floating_representation($format))->bit_width();
}
$signature=new \type_model\signature_representation(7,2,3);
echo json_encode([$widths,array_map(fn($m)=>$m->value,$signature->parameter_passing),
    array_map(fn($m)=>$m->is_borrow(),\type_model\argument_passing::cases()),
    array_map(fn($m)=>$m->value,\type_model\result_production::cases())],JSON_THROW_ON_ERROR),"\n";

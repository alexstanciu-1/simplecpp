<?php
declare(strict_types=1);
// Native vectors enforce dense positions; retain PHP boundary diagnostics too.
$type = \type_model\Type_Reference::named('int', '');
$parameter = new \type_model\Semantic_Parameter($type, \type_model\PASS_VALUE);
$result = new \type_model\Semantic_Result($type, \type_model\RESULT_VALUE);
$out = [];
foreach (['sparse_parameters_rejected' => [1 => $parameter], 'named_parameters_rejected' => ['x' => $parameter]] as $key => $parameters) {
    $out[$key] = false;
    try { $signature = new \type_model\Semantic_Signature($parameters, $result); }
    catch (\InvalidArgumentException $error) { $out[$key] = true; }
}
echo json_encode($out, JSON_THROW_ON_ERROR), "\n";

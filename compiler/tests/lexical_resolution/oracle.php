<?php
declare(strict_types=1);
$root=dirname(__DIR__,2).'/reference/pre-rewrite/src/04_analyze/type_model/data/';
require $root.'lifecycle.php';require $root.'generic.php';
echo json_encode(array_map(static fn($role)=>\type_model\generic_contract::copyable_value->permits($role),\type_model\lifecycle_operation_kind::cases()),JSON_THROW_ON_ERROR),"\n";

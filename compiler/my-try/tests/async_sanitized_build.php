<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
$config = new native_cpp_config($argv[3], dirname(__DIR__, 3) . '/runtime/include', $argv[2]);
$config->optimization = 1;
$result = (new Native_CPP_Build($config))->build($argv[1]);
fwrite(STDERR, $result->diagnostics);
echo json_encode(['executable' => $result->executable], JSON_THROW_ON_ERROR), "\n";

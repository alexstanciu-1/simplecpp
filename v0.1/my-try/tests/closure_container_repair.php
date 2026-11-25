<?php
namespace scpp\compiler;
require_once __DIR__ . '/operators.php';

/** Capture a preparation rejection and require output to remain unpublished. */
function container_rejection(string $source): string
{
	$error = '';
	try {
		operator_test_source($source);
	}
	catch (\RuntimeException $e) {
		$error = $e->getMessage();
	}
	if (($error === '') || !Model::$cpp_files->is_empty()) {
		throw new \LogicException('Expected closure storage rejection');
	}
	return $error;
}

$out = $argv[1];
$path = 'tests/php/closures/strict_level_02/closures_009_container_storage_negative.phs';
$source = file_get_contents(dirname(__DIR__, 3).'/'.$path);
$record = ['path'=>$path,'source'=>$source,'sha256'=>hash('sha256',$source),'diagnostic'=>container_rejection($source)];
if (($argv[2] ?? '') === '--baseline') {
	file_put_contents($out.'/baseline.json',json_encode($record,JSON_PRETTY_PRINT)."\n");
	exit(0);
}
// Prove the new initializer independently; the negative must reach the append.
operator_test_source('$items vector<mixed> = []; return count($items);');
$info = json_decode(file_get_contents(dirname(__DIR__, 3).'/'.str_replace('.phs','.test-info.json',$path)),true);
foreach ($info['expect']['generate']['error_contains'] as $text) {
	if (!str_contains($record['diagnostic'],$text)) {
		throw new \LogicException('Fixture diagnostic does not match its declared oracle');
	}
}
$positive = <<<'PHS'
$items vector<function<int()>> = [];
$items[] = function (): int { return 7; };
$items[] = function (): int { return 9; };
echo $items[0](), ":", $items[1](), "\n";
PHS;
operator_test_source($positive);
file_put_contents($out.'/positive.cpp',CPP_Probe::text(Model::$cpp_files));
$wrong = '$items vector<function<int()>> = []; $items[] = function ():string{return "wrong";};';
$error = container_rejection($wrong);
file_put_contents($out.'/checks.json',json_encode(['fixture'=>$record,'incompatible_signature'=>['source'=>$wrong,'diagnostic'=>$error],'initializer_valid'=>true],JSON_PRETTY_PRINT)."\n");

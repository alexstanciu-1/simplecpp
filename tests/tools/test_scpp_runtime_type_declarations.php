<?php
declare(strict_types=1);
require_once __DIR__ . '/../../bin/bootstrap.php';

use Scpp\S2S\Lowering\TypeMapper;
use Scpp\S2S\Transpiler;

function check(bool $condition, string $message): void {
	if (!$condition) { throw new RuntimeException($message); }
}
$mapper = new TypeMapper();
foreach (['file_lock_handle', 'process_handle', 'process_output'] as $alias) {
	foreach ([$alias, '\\' . $alias] as $type) {
		check($mapper->isRuntimeProvidedType($type), 'runtime declaration owner: ' . $type);
		check($mapper->mapDeclaredType($type) === 'shared_p<' . $alias . '>', 'shared representation: ' . $type);
		check($mapper->mapParamType($type, true) === 'shared_p<' . $alias . '>&', 'reference representation: ' . $type);
		$source = 'function keep(' . $type . ' $x): ' . $type . ' { return $x; }' . "\n"
			. 'function output(' . $type . ' &$x): void {}' . "\n"
			. 'function wrapped(result<' . $type . '> $x): result<' . $type . '> { return $x; }' . "\n"
			. 'function many(vector<' . $type . '> $x): vector<' . $type . '> { return $x; }' . "\n"
			. 'function users(UserRecord $x): UserRecord { return $x; }';
		$result = (new Transpiler(phpProfile: 'strict'))->transpile('/tmp/runtime-types.phs', sourceOverride: $source);
		check($result->errors === [], 'generation errors: ' . json_encode($result->errors));
		$header = implode("\n", $result->headerLines);
		check(!str_contains($header, 'class ' . $alias . ';'), 'conflicting forward declaration: ' . $type);
		check(str_contains($header, 'class UserRecord;'), 'ordinary class forward declaration lost');
	}
	check(!$mapper->isRuntimeProvidedType('Application\\' . $alias), 'qualified user type mistaken for runtime alias');
}
check(!$mapper->isRuntimeProvidedType('UserRecord'), 'user class classified as runtime-owned');
echo "PASS: runtime declaration ownership, qualified aliases, shared/reference/wrapper signatures and user forwards\n";

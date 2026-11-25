<?php
declare(strict_types=1);
require_once __DIR__ . '/../../bin/bootstrap.php';

use Scpp\S2S\Lowering\TypeMapper;
use Scpp\S2S\Transpiler;

function check(bool $condition, string $message): void {
	if (!$condition) { throw new RuntimeException($message); }
}
$mapper = new TypeMapper();
foreach (['file_lock_handle', 'process_handle', 'process_output', 'json_document', 'json_node', 'json_parse_error'] as $alias) {
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
check($mapper->mapDeclaredType('\\RootRecord') === 'shared_p<::scpp::RootRecord>', 'rooted user type lost its root');
$rooted = (new Transpiler(phpProfile: 'strict'))->transpile('/tmp/rooted-types.phs', sourceOverride:
	'namespace Application; class Owner { public \\RootRecord $record; '
	. 'public function keep(\\RootRecord $record): \\RootRecord { return $record; } }');
check($rooted->errors === [], 'rooted declaration generation failed');
$rootedHeader = implode("\n", $rooted->headerLines);
check(str_contains($rootedHeader, 'shared_p<::scpp::RootRecord>'), 'rooted field/signature not preserved');
check(!str_contains($rootedHeader, 'class RootRecord;'), 'rooted external type acquired a local shadow declaration');
echo "PASS: runtime declaration ownership, qualified aliases, shared/reference/wrapper signatures and user forwards\n";

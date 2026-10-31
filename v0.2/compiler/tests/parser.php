<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';

use scpp\compiler\{assignment_intent, ast_assignment_statement, diagnostic, flavor, ast_integer_expression,
	ast_local_declaration, ast_name_expression, source_file, Source_Frontend};

function check(bool $condition, string $message): void
{
	if (!$condition) {
		throw new RuntimeException($message);
	}
}

$frontend = new Source_Frontend();
foreach ([['int count = 3; count = next;', flavor::cpp],
	['$count int = 3; $count = $next;', flavor::php],
	['let count: int = 3; count = next;', flavor::js]] as [$text, $flavor]) {
	$source = new source_file('example', $text, $flavor);
	$file = $frontend->parse($source);
	check(count($file->main()->body()->statements) === 2, 'Two shared statements');
	$local = $file->main()->body()->statements[0];
	check($local instanceof ast_local_declaration, 'Shared local declaration');
	check($local->name === 'count' && $local->type_name === 'int', 'Names and type syntax');
	check($local->initializer instanceof ast_integer_expression && $local->initializer->source_text() === '3', 'Raw integer');
	check($local->byte_start() === 0 && str_ends_with($local->source_text(), ';'), 'Statement includes semicolon');
	check($local->root()->tokens->text_at($local->name_position()) === ($flavor === flavor::php ? '$count' : 'count'), 'Original name spelling');
	check($local->source() === $source && $file->tokens->source === $source, 'Retained source identity');
	$write = $file->main()->body()->statements[1];
	check($write instanceof ast_assignment_statement && $write->name === 'count', 'Shared assignment');
	check($write->value instanceof ast_name_expression && $write->value->name === 'next', 'Normalized name reference');
	check($write->intent === ($flavor === flavor::php ? assignment_intent::declare_or_assign : assignment_intent::existing_binding), 'Binding intent');
	check($frontend->parse(new source_file('empty', '', $flavor))->declarations === [], 'Empty file');
}
foreach ([['auto x = missing;', flavor::cpp], ['let x = missing;', flavor::js]] as [$text, $flavor]) {
	$local = $frontend->parse(new source_file('inferred', $text, $flavor))->main()->body()->statements[0];
	check($local instanceof ast_local_declaration && $local->type_name === null, 'Inferred syntax without inference');
}
$file = $frontend->parse(new source_file('writes', '$x = 1; $x = 2;', flavor::php));
foreach ($file->main()->body()->statements as $write) {
	check($write instanceof ast_assignment_statement && $write->intent === assignment_intent::declare_or_assign, 'PHP writes stay unresolved');
}
// These require later profile/type/name checks, and must survive parsing unchanged.
$huge = '999999999999999999999999999999999999999999';
$file = $frontend->parse(new source_file('unresolved', 'Unknown x = ' . $huge . '; Unknown x = missing;', flavor::cpp));
check($file->main()->body()->statements[0]->initializer->source_text() === $huge, 'No host integer conversion');
check($file->main()->body()->statements[1]->initializer instanceof ast_name_expression, 'No symbol lookup or duplicate check');
foreach ([['int x = ;', flavor::cpp], ['let x = 1', flavor::js], ['$x int = 1.5;', flavor::php],
	['let x = 1;', flavor::php], ['$x int = 1;', flavor::js], ['let x: int = 1;', flavor::cpp],
	['auto x = 1;', flavor::js], ['int $x = 1;', flavor::cpp], ['$x = bare;', flavor::php],
	['const x = 1;', flavor::js], ['int x = 1 + 2;', flavor::cpp], ['{ int x = 1; }', flavor::cpp],
	['vector<int> x = y;', flavor::cpp], ['int x = f();', flavor::cpp], ['let x = 1 = 2;', flavor::js]] as [$text, $flavor]) {
	try {
		$frontend->parse(new source_file('invalid', $text, $flavor));
		throw new RuntimeException('Expected syntax rejection: ' . $text);
	} catch (diagnostic $error) {
		check($error->offset <= strlen($text), 'Source-contained error');
	}
}
try {
	$frontend->parse(new source_file('eof', "let x = 1;\nlet y =", flavor::js));
	throw new RuntimeException('Expected EOF rejection');
} catch (diagnostic $error) {
	check($error->offset === 18 && str_contains($error->getMessage(), 'eof:2:8:'), 'EOF byte location');
}
check($file->main()->body()->statements[0]->initializer->source_text() === $huge, 'Old parsed file survives failures and later scans');
check(count($frontend->parse(new source_file('flat', str_repeat('int x = 1;', 10000), flavor::cpp))->main()->body()->statements) === 10000, 'Flat statement sequence');
echo "Parser checks passed\n";

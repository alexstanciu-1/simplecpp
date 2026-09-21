<?php
declare(strict_types=1);
require_once __DIR__ . '/../../bin/bootstrap.php';

use Scpp\S2S\Analysis\FrontEndSymbolExtractor;
use Scpp\S2S\Stan\StanExpressionTypeResolver;

$extractor = new FrontEndSymbolExtractor();
$runtimePath = __DIR__ . '/../../runtime/generated/stan/runtime_symbols_strict.phs';
$runtime = $extractor->summarize($extractor->extract($runtimePath));
function analyze(string $source): array {
	global $extractor, $runtime;
	$file = $extractor->extract('/tmp/collection-typing.phs', $source);
	$summary = $extractor->summarize($file, $source);
	if ($summary['build_errors'] !== []) { throw new RuntimeException(json_encode($summary['build_errors'])); }
	return (new StanExpressionTypeResolver())->analyzeWorkspaceExpressions([$summary, $runtime], []);
}
function check(bool $condition, string $message): void {
	if (!$condition) { throw new RuntimeException($message); }
}
$source = <<<'PHS'
$input vector<int> = [1, 2];
$mapped = collection_map($input, function (int $x): string { return "x"; });
$filtered = collection_filter($input, function (int $x): bool { return true; });
$stored /** function<string(int)> */ = function (int $x): string { return "x"; };
$stored_result = collection_map($input, $stored);
$inferred = function (int $x): string { return "x"; };
$inferred_result = collection_map($input, $inferred);
$nested = collection_filter(collection_map($input, function (int $x): string { return "x"; }), function (string $x): bool { return true; });
$fixed fixed_array<int, 2> = [1, 2];
$fixed_result = collection_map($fixed, function (int $x): string { return "x"; });
$hash hash<int, int> = [7 => 1];
$hash_result = collection_map($hash, function (int $x): string { return "x"; });
$box hash<mixed> = [7 => 1];
$box_result = collection_map($box, function (mixed $x): string { return "x"; });
$m mixed = [7 => 1];
$m_result = collection_map($m, function (mixed $x): string { return "x"; });
$d dynamic = [7 => 1];
$d_result = collection_map($d, function (mixed $x): string { return "x"; });
function names(vector<int> $xs): vector<string> {
	return collection_map($xs, function (int $x): string { return "x"; });
}
PHS;
$result = analyze($source);
check($result['call_site_diagnostics'] === [], 'valid callbacks: ' . json_encode($result['call_site_diagnostics']));
$types = [];
foreach ($result['expression_chain_types'] as $row) { $types[$row['line']] = $row['resolved_type']; }
foreach ([2=>'vector<string>',3=>'vector<int>',5=>'vector<string>',7=>'vector<string>',8=>'vector<string>',10=>'vector<string>',12=>'hash<string,int>',14=>'hash<mixed>',16=>'mixed',18=>'dynamic'] as $line=>$expected) {
	check(($types[$line] ?? null) === $expected, 'line ' . $line . ': expected ' . $expected . ', got ' . json_encode($types[$line] ?? null));
}
check($result['return_type_diagnostics'] === [], 'valid generic return boundary');
foreach ([
	'collection_map($input, function (string $x): string { return $x; });' => 'value parameter',
	'collection_map($input, function (mixed $x): string { return "x"; });' => 'value parameter',
	'collection_map($input, function (int &$x): int { return $x; });' => 'value parameter',
	'collection_map($input, function (int $x, int $y): int { return $x; });' => 'value parameter',
	'collection_filter($input, function (int $x): int { return $x; });' => 'must return `bool`',
	'collection_map($input, function &(int $x): int { return $x; });' => 'storable value',
	'collection_map(2, function (int $x): int { return $x; });' => 'Unsupported collection',
	'collection_map($input);' => 'Expected one collection',
	'$wrong vector<int> = true ? collection_map($input, function (int $x): string { return "x"; }) : collection_map($input, function (int $x): string { return "x"; });' => 'Collection assignment',
	'$wrong vector<int> = collection_map($input, function (int $x): string { return "x"; });' => 'Collection assignment',
] as $statement=>$message) {
	$negative = analyze('$input vector<int> = [1, 2];' . "\n" . $statement);
	check(str_contains(json_encode($negative['call_site_diagnostics']), $message), 'missing diagnostic for ' . $statement . ': ' . json_encode($negative));
}
$positive = analyze(<<<'PHS'
$hash hash<int, int> = [7 => 1];
$typed hash<string, int> = collection_map($hash, function (int $x): string { return "x"; });
$default hash<int> = ["key" => 1];
$explicit hash<string, string> = collection_map($default, function (int $x): string { return "x"; });
$nested vector<vector<int>> = [[1]];
$lengths = collection_map($nested, function ($xs vector<int>): int { return 1; });
PHS);
check($positive['call_site_diagnostics'] === [], 'typed key spellings and nested element callback');
$negative = analyze(<<<'PHS'
$hash hash<int> = ["key" => 1];
$wrong = collection_map($hash, function (int $x): mixed { return $x; });
function wrapped(result<vector<int>> $input): void {
	collection_map($input, function (int $x): int { return $x; });
}
PHS);
check(count($negative['call_site_diagnostics']) === 2, 'unsupported boxed typed-key output and wrapped input');
// Annotation restoration must not depend on which file was most recently extracted.
$annotatedSource = '$f = function ($x vector<int>): int { return 1; };';
$annotated = $extractor->extract('/tmp/collection-annotated.phs', $annotatedSource);
$extractor->extract('/tmp/collection-unrelated.phs', '$x string = "other";');
$summary = $extractor->summarize($annotated, $annotatedSource);
check($summary['root_functions'][0]['local_descriptor_assignments'][0]['descriptor']['params'][0]['primary_type'] === 'vector<int>', 'file-owned callback annotation');
// Calls nested in concrete argument, return, and element boundaries use the same result.
$negative = analyze(<<<'PHS'
function ints(vector<int> $xs): void {}
function wrong(vector<int> $xs): vector<int> {
	return collection_map($xs, function (int $x): string { return "x"; });
}
$input vector<int> = [1];
ints(collection_map($input, function (int $x): string { return "x"; }));
function needs_int(int $x): void {}
needs_int(collection_map($input, function (int $x): string { return "x"; })[0]);
PHS);
check(count($negative['call_site_diagnostics']) === 2, 'nested argument and element rejection');
check(count($negative['return_type_diagnostics']) === 1, 'mapped return rejection');
// Constrained adapters instantiate the same callback/result contract.
$adapters = analyze(<<<'PHS'
$xs vector<int> = [10, 20, 30];
$hs hash<int, int> = [0 => 10, 1 => 20];
$seq = sequence_filter(sequence_map($xs, function (int $x): string { return "x"; }), function (string $x): bool { return true; });
$key = keyed_filter(keyed_map($hs, function (int $x): string { return "x"; }), function (string $x): bool { return true; });
$fixed fixed_array<int, 2> = [10, 20];
$fixed_out = sequence_map($fixed, function (int $x): string { return "x"; });
$box hash<mixed> = [0 => 10];
$box_out = keyed_map($box, function (mixed $x): string { return "x"; });
$table mixed = [0 => 10];
$table_out = keyed_filter($table, function (mixed $x): bool { return true; });
function adapted(vector<int> $xs): vector<string> {
    return sequence_map($xs, function (int $x): string { return "x"; });
}
$named hash<int> = ["a" => 10];
$named_out = keyed_map($named, function (int $x): string { return "x"; });
PHS);
check($adapters['call_site_diagnostics'] === [] && $adapters['return_type_diagnostics'] === [], 'valid constrained adapter calls');
$types = [];
foreach ($adapters['expression_chain_types'] as $row) { $types[$row['line']] = $row['resolved_type']; }
foreach ([3=>'vector<string>',4=>'hash<string,int>',6=>'vector<string>',8=>'hash<mixed>',10=>'mixed',15=>'hash<string>'] as $line=>$expected) {
    check(($types[$line] ?? null) === $expected, 'adapter line ' . $line . ': ' . json_encode($types[$line] ?? null));
}
foreach (['map', 'filter'] as $operation) {
    $callback = $operation === 'map' ? 'function (int $x): int { return $x; }' : 'function (int $x): bool { return true; }';
    foreach ([['sequence', 'hash<int,int>', '[0 => 10, 1 => 20]'], ['keyed', 'vector<int>', '[10, 20]'], ['keyed', 'fixed_array<int,2>', '[10, 20]']] as [$family, $type, $literal]) {
        $bad = analyze('$input ' . $type . ' = ' . $literal . '; ' . $family . '_' . $operation . '($input, ' . $callback . ');');
        check(str_contains(json_encode($bad['call_site_diagnostics']), 'requires carrier family'), 'wrong carrier accepted: ' . $family . '_' . $operation);
    }
    foreach (['sequence', 'keyed'] as $family) {
        $bad = analyze('$input dynamic = [0 => 10]; ' . $family . '_' . $operation . '($input, function (mixed $x): bool { return true; });');
        check(str_contains(json_encode($bad['call_site_diagnostics']), 'requires carrier family'), 'dynamic adapter scope expanded');
    }
}
foreach (['sequence' => 'vector<int>', 'keyed' => 'hash<int,int>'] as $family => $type) {
    $bad = analyze('$input ' . $type . ' = []; ' . $family . '_map($input, function (mixed $x): int { return 1; });');
    check(str_contains(json_encode($bad['call_site_diagnostics']), 'value parameter'), 'adapter callback checking lost');
    $bad = analyze('$input ' . $type . ' = []; ' . $family . '_filter($input, function (int $x): int { return $x; });');
    check(str_contains(json_encode($bad['call_site_diagnostics']), 'must return `bool`'), 'adapter predicate checking lost');
    $bad = analyze('$input ' . $type . ' = []; $wrong ' . $type . ' = ' . $family . '_map($input, function (int $x): string { return "x"; });');
    check(str_contains(json_encode($bad['call_site_diagnostics']), 'Collection assignment'), 'adapter typed boundary checking lost');
}
$bad = analyze(<<<'PHS'
function integers(vector<int> $xs): void {}
function wrong(vector<int> $xs): vector<int> {
    return sequence_map($xs, function (int $x): string { return "x"; });
}
$input vector<int> = [1];
integers(sequence_filter(sequence_map($input, function (int $x): string { return "x"; }), function (string $x): bool { return true; }));
function needs_int(int $x): void {}
$hash hash<int,int> = [0 => 1];
needs_int(keyed_map($hash, function (int $x): string { return "x"; })[0]);
PHS);
check(count($bad['call_site_diagnostics']) === 2 && count($bad['return_type_diagnostics']) === 1, 'adapter nested argument, element and return boundaries');
echo "PASS: collection typing (inline/stored/inferred callbacks, nested calls, carriers, boundaries, rejection)\n";

#!/usr/bin/env php
<?php
declare(strict_types=1);

function rr(string $path): string {
	$real = realpath($path);
	if ($real === false) {
		fwrite(STDERR, "ERROR: Unable to resolve path: {$path}\n");
		exit(2);
	}
	return $real;
}

function grepAll(string $pattern, string $subject): array {
	preg_match_all($pattern, $subject, $m);
	return $m[1] ?? [];
}

function normalizeType(string $t): string {
	$t = trim($t);
	$t = preg_replace('/\s+/', ' ', $t);
	$t = str_replace(['const ', '&', 'noexcept'], '', $t);
	$t = trim($t);
	if (preg_match('/^(.*?)(?:\s+[A-Za-z_][A-Za-z0-9_]*)$/', $t, $m) === 1) {
		$t = trim($m[1]);
	}
	return trim($t);
}

function hasOperator(array $operatorIndex, string $symbol, array $operands): bool {
	$key = $symbol . '|' . implode(',', $operands);
	if (isset($operatorIndex[$key])) {
		return true;
	}
	if (count($operands) === 2) {
		$key2 = $symbol . '|' . $operands[1] . ',' . $operands[0];
		return isset($operatorIndex[$key2]);
	}
	return false;
}

function compileProbe(string $projectRoot, string $name, string $body): array {
	$tmpDir = sys_get_temp_dir() . '/scpp_audit_' . bin2hex(random_bytes(6));
	if (!mkdir($tmpDir, 0777, true) && !is_dir($tmpDir)) {
		return ['name' => $name, 'status' => 'error', 'message' => 'Failed to create temp dir'];
	}
	$file = $tmpDir . '/' . $name . '.cpp';
	$code = <<<CPP
#include <scpp/runtime.hpp>
using namespace scpp;
using namespace scpp::php;

int main() {
	{$body}
	return 0;
}
CPP;
	file_put_contents($file, $code);
	$cmd = [
		'g++',
		'-std=c++23',
		'-fsyntax-only',
		'-I', $projectRoot . '/runtime/include',
		$file,
	];
	$desc = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
	$proc = proc_open($cmd, $desc, $pipes, $projectRoot);
	if (!is_resource($proc)) {
		return ['name' => $name, 'status' => 'error', 'message' => 'Failed to start compiler'];
	}
	$stdout = stream_get_contents($pipes[1]); fclose($pipes[1]);
	$stderr = stream_get_contents($pipes[2]); fclose($pipes[2]);
	$exit = proc_close($proc);
	$status = 'compile_unique';
	if ($exit !== 0) {
		$status = (stripos($stderr, 'ambiguous overload') !== false) ? 'compile_ambiguous' : 'compile_forbidden';
	}
	return [
		'name' => $name,
		'status' => $status,
		'exit_code' => $exit,
		'stdout' => $stdout,
		'stderr' => $stderr,
	];
}

$strict = in_array('--strict', $argv, true);
$jsonOut = in_array('--json', $argv, true);
$root = rr(__DIR__ . '/../../..');
$configPath = rr($root . '/runtime/specs/config.json');
$mixedHeaderPath = rr($root . '/runtime/include/scpp/support/mixed_t.hpp');
$mixedImplPath = rr($root . '/runtime/include/scpp/support/mixed_t.cpp');

$config = json_decode((string)file_get_contents($configPath), true);
if (!is_array($config)) {
	fwrite(STDERR, "ERROR: Invalid JSON in {$configPath}\n");
	exit(2);
}
$header = (string)file_get_contents($mixedHeaderPath);
$impl = (string)file_get_contents($mixedImplPath);

$outConversions = array_map('normalizeType', grepAll('/operator\s+([^\(]+)\(\)\s+const\s*;/', $header));
$constructors = [];
foreach (grepAll('/mixed_t\(([^\)]*)\)\s*(?:noexcept)?\s*;/', $header) as $sig) {
	$param = normalizeType($sig);
	if ($param === '' || str_contains($param, ',')) {
		continue;
	}
	$constructors[] = $param;
}
$constructors = array_values(array_unique($constructors));

$operatorIndex = [];
foreach (($config['overload_families'] ?? []) as $family) {
	foreach (($family['operators'] ?? []) as $op) {
		$symbol = $op['symbol'] ?? null;
		$operands = $op['operands'] ?? null;
		if (!is_string($symbol) || !is_array($operands)) {
			continue;
		}
		$key = $symbol . '|' . implode(',', $operands);
		$operatorIndex[$key] = true;
		if (($op['symmetric'] ?? false) === true && count($operands) === 2) {
			$operatorIndex[$symbol . '|' . $operands[1] . ',' . $operands[0]] = true;
		}
		if (($family['symmetric'] ?? false) === true && count($operands) === 2) {
			$operatorIndex[$symbol . '|' . $operands[1] . ',' . $operands[0]] = true;
		}
	}
}

$intoMixedByNative = [];
foreach (['bool_t','int_t','float_t','string_t'] as $native) {
	if (in_array($native, $constructors, true)) {
		$intoMixedByNative[] = $native;
	}
}

$nativeOut = array_values(array_intersect($outConversions, ['bool_t','int_t','float_t','string_t']));
$dynamicSymbols = array_values(array_unique(array_merge(
	grepAll('/friend\s+mixed_t\s+operator((?:\+|-|\*|\/|%|&|\||\^|<<|>>))/', $header),
	grepAll('/friend\s+bool_t\s+operator((?:==|!=|<=|>=|<|>|&&|\|\||!))/', $header)
)));

$potentialAmbiguities = [];
$cases = [
	['probe' => 'mixed_plus_int', 'symbol' => '+', 'native_rhs' => 'int_t', 'native_candidate_lhs' => 'int_t'],
	['probe' => 'mixed_plus_float', 'symbol' => '+', 'native_rhs' => 'float_t', 'native_candidate_lhs' => 'float_t'],
	['probe' => 'mixed_eq_int', 'symbol' => '==', 'native_rhs' => 'int_t', 'native_candidate_lhs' => 'int_t'],
	['probe' => 'mixed_and_int', 'symbol' => '&&', 'native_rhs' => 'int_t', 'native_candidate_lhs' => 'int_t'],
];
foreach ($cases as $case) {
	$symbol = $case['symbol'];
	$rhs = $case['native_rhs'];
	$lhsNative = $case['native_candidate_lhs'];
	$dynamicExists = in_array($symbol, $dynamicSymbols, true);
	$nativeExists = hasOperator($operatorIndex, $symbol, [$lhsNative, $rhs]);
	$rhsBoxesIntoMixed = in_array($rhs, $intoMixedByNative, true);
	$lhsExtractsToNative = in_array($lhsNative, $nativeOut, true);
	if ($dynamicExists && $nativeExists && $rhsBoxesIntoMixed && $lhsExtractsToNative) {
		$potentialAmbiguities[] = [
			'rule_id' => 'ra_amb_' . $case['probe'],
			'expression_shape' => 'mixed_t ' . $symbol . ' ' . $rhs,
			'dynamic_path' => 'mixed_t ' . $symbol . ' mixed_t via ' . $rhs . ' -> mixed_t',
			'native_path' => $lhsNative . ' ' . $symbol . ' ' . $rhs . ' via mixed_t -> ' . $lhsNative,
			'severity' => 'high',
		];
	}
}

$compileProbes = [
	compileProbe($root, 'mixed_plus_int', 'mixed_t m{int_t(1)}; auto v = m + int_t(5); (void)v;'),
	compileProbe($root, 'mixed_plus_float', 'mixed_t m{float_t(1.0)}; auto v = m + float_t(5.0); (void)v;'),
	compileProbe($root, 'mixed_eq_int', 'mixed_t m{int_t(1)}; auto v = (m == int_t(5)); (void)v;'),
	compileProbe($root, 'mixed_and_int', 'mixed_t m{int_t(1)}; auto v = (m && int_t(5)); (void)v;'),
	compileProbe($root, 'mixed_plus_eq_int', 'mixed_t m{int_t(1)}; m += int_t(5);'),
	compileProbe($root, 'int_plus_mixed', 'mixed_t m{int_t(1)}; auto v = int_t(5) + m; (void)v;'),
	compileProbe($root, 'float_plus_mixed', 'mixed_t m{float_t(1.0)}; auto v = float_t(5.0) + m; (void)v;'),
];

$drift = [];
if (strpos($impl, 'return cast<bool_t>(*this);') !== false || strpos($impl, 'return cast<int_t>(*this);') !== false) {
	$drift[] = [
		'rule_id' => 'ra_001',
		'message' => 'mixed_t still exposes broad implicit conversion operators; compile-surface ambiguity remains possible until runtime is tightened or generator emits explicit casts.',
		'severity' => 'warn',
	];
}
if (strpos($impl, 'if (left.type_ == mixed_t::kind_t::string_v && right.type_ == mixed_t::kind_t::string_v)') !== false) {
	$drift[] = [
		'rule_id' => 'ra_002',
		'message' => 'mixed_t operator+ contains explicit string_v + string_v special handling; keep or remove intentionally.',
		'severity' => 'info',
	];
}

$compileIssues = [];
foreach ($compileProbes as $probe) {
	if ($probe['status'] !== 'compile_unique') {
		$compileIssues[] = $probe;
	}
}

$result = [
	'config_path' => $configPath,
	'mixed_header_path' => $mixedHeaderPath,
	'native_conversions_out_of_mixed' => $nativeOut,
	'native_conversions_into_mixed' => $intoMixedByNative,
	'potential_ambiguities' => $potentialAmbiguities,
	'compile_probes' => $compileProbes,
	'runtime_drift' => $drift,
];

if ($jsonOut) {
	echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
} else {
	echo "audit_runtime_vs_config.php\n";
	echo "Config: {$configPath}\n";
	echo 'native conversions out of mixed_t: ' . (implode(', ', $nativeOut) ?: '<none>') . "\n";
	echo 'native conversions into mixed_t: ' . (implode(', ', $intoMixedByNative) ?: '<none>') . "\n";
	if ($potentialAmbiguities === []) {
		echo "Potential compile-surface ambiguities: none detected by static rule\n";
	} else {
		echo "Potential compile-surface ambiguities:\n";
		foreach ($potentialAmbiguities as $item) {
			echo ' - [' . $item['rule_id'] . '] ' . $item['expression_shape'] . PHP_EOL;
			echo '   dynamic: ' . $item['dynamic_path'] . PHP_EOL;
			echo '   native : ' . $item['native_path'] . PHP_EOL;
		}
	}
	echo "Compile probes:\n";
	foreach ($compileProbes as $probe) {
			echo ' - ' . $probe['name'] . ': ' . $probe['status'] . PHP_EOL;
			if ($probe['status'] !== 'compile_unique') {
				$line = trim((string)preg_replace('/\s+/', ' ', substr($probe['stderr'] ?? '', 0, 240)));
				if ($line !== '') {
					echo '   ' . $line . PHP_EOL;
				}
			}
	}
	if ($drift !== []) {
		echo "Runtime drift notes:\n";
		foreach ($drift as $item) {
			echo ' - [' . $item['rule_id'] . '] ' . $item['message'] . PHP_EOL;
		}
	}
}

$hasFail = ($potentialAmbiguities !== [] || $compileIssues !== []);
if ($strict && $hasFail) {
	exit(1);
}
exit(0);

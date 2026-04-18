<?php

declare(strict_types=1);

/**
 * Emit concrete PHP matrix tests from deterministic test seeds.
 *
 * @param list<array<string, mixed>> $seeds
 * @param array<string, mixed> $options
 * @return array<string, mixed>
 */
function om_emit_matrix_tests(string $repoRoot, array $seeds, array $options): array
{
	$phpMatrixRoot = $repoRoot . '/tests/php-matrix';
	$runtimeMatrixRoot = $repoRoot . '/tests/runtime-matrix';

	om_recreate_generated_tree($phpMatrixRoot);
	om_recreate_generated_tree($runtimeMatrixRoot);

	$emitted = [
		'php-matrix' => [
			'test_count' => 0,
			'source_files' => 0,
			'info_files' => 0,
			'enabled_test_count' => 0,
			'disabled_test_count' => 0,
		],
		'runtime-matrix' => [
			'test_count' => 0,
			'source_files' => 0,
			'info_files' => 0,
			'enabled_test_count' => 0,
			'disabled_test_count' => 0,
		],
		'negative_generate' => [
			'emit_mode' => (string) ($options['emit_negative_generate'] ?? 'all'),
			'enable_mode' => (string) ($options['enable_negative_generate'] ?? 'none'),
			'disabled_status' => (string) ($options['negative_generate_disabled_status'] ?? 'experimental'),
			'strict_enable' => (bool) ($options['strict_negative_generate_enable'] ?? false),
			'enabled_diagnostics' => array_values($options['enable_negative_generate_diagnostic'] ?? []),
			'disabled_diagnostics' => array_values($options['disable_negative_generate_diagnostic'] ?? []),
			'emitted_count' => 0,
			'enabled_count' => 0,
			'disabled_count' => 0,
			'per_diagnostic' => [],
		],
	];
	$manifest = [];

	$featureOrdinals = [];
	foreach ($seeds as $seed) {
		$suite = (string) ($seed['suite'] ?? '');
		if ($suite !== 'php-matrix') {
			continue;
		}
		if (!om_should_emit_seed($seed, $options)) {
			continue;
		}

		$feature = (string) ($seed['feature'] ?? 'matrix');
		$featureOrdinals[$feature] = ($featureOrdinals[$feature] ?? 0) + 1;
		$stem = om_build_emitted_test_stem($seed, (int) $featureOrdinals[$feature]);
		$relativeDirectory = 'tests/' . $suite . '/' . $feature . '/' . (string) ($seed['level'] ?? 'level_01');
		$relativeSourcePath = $relativeDirectory . '/' . $stem . '.php';
		$relativeInfoPath = $relativeDirectory . '/' . $stem . '.test-info.json';
		$sourcePath = $repoRoot . '/' . $relativeSourcePath;
		$infoPath = $repoRoot . '/' . $relativeInfoPath;
		om_ensure_directory(dirname($sourcePath));
		om_ensure_directory(dirname($infoPath));

		$sourceContent = om_render_php_matrix_source($seed);
		$infoContent = om_build_php_matrix_test_info($seed, $stem, $options);
		file_put_contents($sourcePath, $sourceContent);
		om_write_json_file($infoPath, $infoContent);

		$enabled = (bool) ($infoContent['enabled'] ?? false);
		$emitted[$suite]['test_count']++;
		$emitted[$suite]['source_files']++;
		$emitted[$suite]['info_files']++;
		$emitted[$suite][$enabled ? 'enabled_test_count' : 'disabled_test_count']++;

		if ((string) ($seed['outcome_class'] ?? '') === 'negative_generate') {
			$diagnosticClass = om_seed_diagnostic_class($seed);
			if (!isset($emitted['negative_generate']['per_diagnostic'][$diagnosticClass])) {
				$emitted['negative_generate']['per_diagnostic'][$diagnosticClass] = [
					'emitted_count' => 0,
					'enabled_count' => 0,
					'disabled_count' => 0,
				];
			}
			$emitted['negative_generate']['emitted_count']++;
			$emitted['negative_generate'][$enabled ? 'enabled_count' : 'disabled_count']++;
			$emitted['negative_generate']['per_diagnostic'][$diagnosticClass]['emitted_count']++;
			$emitted['negative_generate']['per_diagnostic'][$diagnosticClass][$enabled ? 'enabled_count' : 'disabled_count']++;
		}

		$manifest[] = [
			'seed_id' => $seed['seed_id'],
			'suite' => $suite,
			'source_path' => $relativeSourcePath,
			'info_path' => $relativeInfoPath,
			'outcome_class' => $seed['outcome_class'],
			'test_seed_class' => $seed['test_seed_class'],
			'diagnostic_class' => om_seed_diagnostic_class($seed),
			'enabled' => $enabled,
			'status' => $infoContent['status'],
		];
	}

	om_write_json_file($phpMatrixRoot . '/_manifest.json', [
		'generated_at_utc' => gmdate('c'),
		'suite' => 'php-matrix',
		'test_count' => $emitted['php-matrix']['test_count'],
		'entries' => $manifest,
	]);
	om_write_json_file($runtimeMatrixRoot . '/_manifest.json', [
		'generated_at_utc' => gmdate('c'),
		'suite' => 'runtime-matrix',
		'test_count' => 0,
		'entries' => [],
	]);

	om_assert_strict_negative_generate_enablement($emitted['negative_generate']);
	ksort($emitted['negative_generate']['per_diagnostic']);

	return $emitted;
}

function om_recreate_generated_tree(string $root): void
{
	om_delete_directory($root);
	om_ensure_directory($root);
	file_put_contents($root . '/.gitkeep', '');
}

function om_delete_directory(string $path): void
{
	if (!is_dir($path)) {
		return;
	}

	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
		RecursiveIteratorIterator::CHILD_FIRST
	);
	foreach ($iterator as $entry) {
		$entryPath = $entry->getPathname();
		if ($entry->isDir()) {
			rmdir($entryPath);
		} else {
			unlink($entryPath);
		}
	}
	rmdir($path);
}

/**
 * @param array<string, mixed> $seed
 */
function om_render_php_matrix_source(array $seed): string
{
	$lines = [
		'<?php',
		'declare(strict_types=1);',
		'',
		'// Generated by tools/operator_matrix/generator.php.',
		'// seed_id: ' . (string) $seed['seed_id'],
		'// item: ' . (string) $seed['item_id'],
		'// test_seed_class: ' . (string) $seed['test_seed_class'],
		'// notes: ' . om_render_single_line_comment((string) ($seed['notes'] ?? '')), 
	];

	$operands = is_array($seed['operands'] ?? null) ? $seed['operands'] : [];
	$declared = [];
	foreach (['lhs', 'rhs', 'third'] as $name) {
		$operand = is_array($operands[$name] ?? null) ? $operands[$name] : [];
		$type = $operand['type'] ?? null;
		$profile = $operand['profile'] ?? null;
		if (!is_string($type) || $type === '' || !is_string($profile) || $profile === '') {
			continue;
		}
		$declared[] = '$' . $name . ' /** ' . om_map_seed_type_to_php_annotation($type) . ' */ = ' . om_render_seed_profile_literal($type, $profile) . ';';
	}

	if ($declared !== []) {
		$lines[] = '';
		foreach ($declared as $line) {
			$lines[] = $line;
		}
	}

	$lines[] = '';
	$lines[] = 'var_dump(' . om_render_seed_expression($seed) . ');';
	$lines[] = '';

	return implode(PHP_EOL, $lines);
}


/**
 * @param array<string, mixed> $seed
 */
function om_build_emitted_test_stem(array $seed, int $ordinal): string
{
	$feature = strtolower((string) ($seed['feature'] ?? 'matrix'));
	$ordinalPart = str_pad((string) $ordinal, 4, '0', STR_PAD_LEFT);
	$detail = om_slugify_emitted_component((string) ($seed['test_seed_class'] ?? 'seed'));
	$diagnostic = om_slugify_emitted_component((string) (($seed['expected']['diagnostic_class'] ?? '') ?: ''));
	$hash = substr(sha1((string) ($seed['seed_id'] ?? '')), 0, 10);
	$parts = [$feature, $ordinalPart, $detail];
	if ($diagnostic !== '') {
		$parts[] = $diagnostic;
	}
	$parts[] = $hash;
	return implode('__', $parts);
}

function om_slugify_emitted_component(string $value): string
{
	$value = strtolower($value);
	$value = str_replace(['.', ' '], ['_', '_'], $value);
	$value = preg_replace('/[^a-z0-9_]+/', '_', $value) ?? $value;
	$value = preg_replace('/_+/', '_', $value) ?? $value;
	return trim($value, '_');
}

function om_render_single_line_comment(string $value): string
{
	$value = str_replace(["\r", "\n"], ' ', $value);
	$value = preg_replace('/\s+/', ' ', $value) ?? $value;
	return trim($value);
}

/**
 * @param array<string, mixed> $seed
 */
function om_render_seed_expression(array $seed): string
{
	$itemId = (string) ($seed['item_id'] ?? '');
	return match ($itemId) {
		'coalesce' => '$lhs ?? $rhs',
		'elvis' => '$lhs ?: $rhs',
		'ternary' => '$lhs ? $rhs : $third',
		default => throw new RuntimeException('Unsupported seed item_id for PHP emitter: ' . $itemId),
	};
}

function om_map_seed_type_to_php_annotation(string $type): string
{
	return match ($type) {
		'bool_t' => 'bool',
		'int_t' => 'int',
		'float_t' => 'float',
		'string_t' => 'string',
		'mixed_t' => 'mixed',
		'nullable<bool_t>' => '?bool',
		'nullable<int_t>' => '?int',
		'nullable<float_t>' => '?float',
		'nullable<string_t>' => '?string',
		'result<bool_t>' => 'result<bool>',
		'result<int_t>' => 'result<int>',
		'result<float_t>' => 'result<float>',
		'result<string_t>' => 'result<string>',
		'result_or_false<bool_t>' => 'result_or_false<bool>',
		'result_or_false<int_t>' => 'result_or_false<int>',
		'result_or_false<float_t>' => 'result_or_false<float>',
		'result_or_false<string_t>' => 'result_or_false<string>',
		'result_or_bool<bool_t>' => 'result_or_bool<bool>',
		'result_or_bool<int_t>' => 'result_or_bool<int>',
		'result_or_bool<float_t>' => 'result_or_bool<float>',
		'result_or_bool<string_t>' => 'result_or_bool<string>',
		default => throw new RuntimeException('Unsupported seed type annotation: ' . $type),
	};
}

function om_render_seed_profile_literal(string $type, string $profile): string
{
	return match ($profile) {
		'bool.false' => 'false',
		'bool.true' => 'true',
		'int.zero' => '0',
		'int.nonzero' => '7',
		'float.zero' => '0.0',
		'float.nonzero' => '3.5',
		'string.empty' => '""',
		'string.zero_string' => '"0"',
		'string.bool_false_literal' => '"false"',
		'string.bool_true_literal' => '"true"',
		'string.nonempty_nonzero_nonbool_literal' => '"hello"',
		'mixed.null' => 'null',
		'mixed.bool.false' => 'false',
		'mixed.bool.true' => 'true',
		'mixed.int.zero' => '0',
		'mixed.int.nonzero' => '7',
		'mixed.float.zero' => '0.0',
		'mixed.float.nonzero' => '3.5',
		'mixed.string.empty' => '""',
		'mixed.string.zero_string' => '"0"',
		'mixed.string.bool_false_literal' => '"false"',
		'mixed.string.bool_true_literal' => '"true"',
		'mixed.string.nonempty_nonzero_nonbool_literal' => '"hello"',
		'mixed.hash.empty' => '[]',
		'mixed.hash.nonempty' => '["k" => 1]',
		'nullable.empty' => 'null',
		'nullable.present.bool.false' => 'false',
		'nullable.present.bool.true' => 'true',
		'nullable.present.int.zero' => '0',
		'nullable.present.int.nonzero' => '7',
		'nullable.present.float.zero' => '0.0',
		'nullable.present.float.nonzero' => '3.5',
		'nullable.present.string.empty' => '""',
		'nullable.present.string.zero_string' => '"0"',
		'nullable.present.string.bool_false_literal' => '"false"',
		'nullable.present.string.bool_true_literal' => '"true"',
		'nullable.present.string.nonempty_nonzero_nonbool_literal' => '"hello"',
		'result.failure' => 'error',
		'result.success.bool.false' => 'false',
		'result.success.bool.true' => 'true',
		'result.success.int.zero' => '0',
		'result.success.int.nonzero' => '7',
		'result.success.float.zero' => '0.0',
		'result.success.float.nonzero' => '3.5',
		'result.success.string.empty' => '""',
		'result.success.string.zero_string' => '"0"',
		'result.success.string.bool_false_literal' => '"false"',
		'result.success.string.bool_true_literal' => '"true"',
		'result.success.string.nonempty_nonzero_nonbool_literal' => '"hello"',
		'result_or_false.sentinel.false' => 'false',
		'result_or_false.success.bool.false' => 'false',
		'result_or_false.success.bool.true' => 'true',
		'result_or_false.success.int.zero' => '0',
		'result_or_false.success.int.nonzero' => '7',
		'result_or_false.success.float.zero' => '0.0',
		'result_or_false.success.float.nonzero' => '3.5',
		'result_or_false.success.string.empty' => '""',
		'result_or_false.success.string.zero_string' => '"0"',
		'result_or_false.success.string.bool_false_literal' => '"false"',
		'result_or_false.success.string.bool_true_literal' => '"true"',
		'result_or_false.success.string.nonempty_nonzero_nonbool_literal' => '"hello"',
		'result_or_bool.sentinel.false' => 'false',
		'result_or_bool.sentinel.true' => 'true',
		'result_or_bool.success.bool.false' => 'false',
		'result_or_bool.success.bool.true' => 'true',
		'result_or_bool.success.int.zero' => '0',
		'result_or_bool.success.int.nonzero' => '7',
		'result_or_bool.success.float.zero' => '0.0',
		'result_or_bool.success.float.nonzero' => '3.5',
		'result_or_bool.success.string.empty' => '""',
		'result_or_bool.success.string.zero_string' => '"0"',
		'result_or_bool.success.string.bool_false_literal' => '"false"',
		'result_or_bool.success.string.bool_true_literal' => '"true"',
		'result_or_bool.success.string.nonempty_nonzero_nonbool_literal' => '"hello"',
		default => throw new RuntimeException('Unsupported seed profile literal: ' . $type . ' / ' . $profile),
	};
}


/**
 * @param array<string, mixed> $seed
 */
function om_should_emit_seed(array $seed, array $options): bool
{
	if ((string) ($seed['outcome_class'] ?? '') !== 'negative_generate') {
		return true;
	}

	return (string) ($options['emit_negative_generate'] ?? 'all') !== 'none';
}

/**
 * @param array<string, mixed> $seed
 */
function om_should_enable_seed(array $seed, array $options): bool
{
	if ((string) ($seed['outcome_class'] ?? '') !== 'negative_generate') {
		return om_should_enable_seed_by_default($seed);
	}

	$diagnosticClass = om_seed_diagnostic_class($seed);
	$disabledDiagnostics = array_fill_keys($options['disable_negative_generate_diagnostic'] ?? [], true);
	if (isset($disabledDiagnostics[$diagnosticClass])) {
		return false;
	}

	$enabledDiagnostics = array_fill_keys($options['enable_negative_generate_diagnostic'] ?? [], true);
	if (isset($enabledDiagnostics[$diagnosticClass])) {
		return true;
	}

	return (string) ($options['enable_negative_generate'] ?? 'none') === 'all';
}

/**
 * @param array<string, mixed> $seed
 */
function om_seed_diagnostic_class(array $seed): string
{
	$diagnosticClass = (string) (($seed['expected']['diagnostic_class'] ?? '') ?: 'unspecified_negative_generate');
	return $diagnosticClass;
}

/**
 * @param array<string, mixed> $seed
 */
function om_should_enable_seed_by_default(array $seed): bool
{
	if ((string) ($seed['outcome_class'] ?? '') !== 'positive') {
		return false;
	}

	$operands = is_array($seed['operands'] ?? null) ? $seed['operands'] : [];
	foreach (['lhs', 'rhs', 'third'] as $name) {
		$operand = is_array($operands[$name] ?? null) ? $operands[$name] : [];
		$type = (string) (($operand['type'] ?? '') ?: '');
		if ($type === '') {
			continue;
		}
		if (str_contains($type, 'nullable<') || str_contains($type, 'result')) {
			return false;
		}
	}

	return true;
}

/**
 * @param array<string, mixed> $seed
 * @param array<string, mixed> $options
 * @return array<string, mixed>
 */
function om_build_php_matrix_test_info(array $seed, string $id, array $options): array
{
	$outcome = (string) $seed['outcome_class'];
	$positive = ($outcome === 'positive');
	$negativeGenerate = ($outcome === 'negative_generate');
	$negativeRuntime = ($outcome === 'negative_runtime');
	$expected = is_array($seed['expected'] ?? null) ? $seed['expected'] : [];
	$stdout = $positive ? om_render_expected_var_dump((string) ($expected['result_profile'] ?? '')) : '';
	$enabled = om_should_enable_seed($seed, $options);
	$status = $enabled
		? 'active'
		: (($negativeGenerate ? (string) ($options['negative_generate_disabled_status'] ?? 'experimental') : 'experimental'));

	return [
		'id' => $id,
		'feature' => (string) $seed['feature'],
		'level' => (string) $seed['level'],
		'outcome' => $outcome,
		'enabled' => $enabled,
		'status' => $status,
		'php_as_oracle' => false,
		'compare' => [
			'stdout' => 'exact',
			'stderr' => 'exact',
			'generator_error' => 'substring_all',
			'compile_error' => 'substring_all',
			'normalize_stdout_newlines' => true,
			'normalize_stderr_newlines' => true,
			'trim_final_stdout_newline' => false,
			'trim_final_stderr_newline' => false,
			'case_sensitive_errors' => true,
		],
		'expect' => [
			'php' => [
				'run' => false,
				'exit_code' => 0,
				'stdout' => '',
				'stderr' => '',
			],
			'generate' => [
				'success' => !$negativeGenerate,
				'error_contains' => om_expected_generator_substrings((string) ($expected['diagnostic_class'] ?? '')),
			],
			'compile' => [
				'success' => $positive || $negativeRuntime,
				'error_contains' => [],
			],
			'run' => $positive
				? [
					'success' => true,
					'exit_code' => 0,
					'stdout' => $stdout,
					'stderr' => '',
				]
				: [
					'success' => !$negativeRuntime ? false : false,
				],
		],
		'tags' => om_build_seed_tags($seed),
		'notes' => om_build_seed_notes($seed),
		'build' => [
			'sanitizers' => null,
		],
	];
}

/**
 * @param array<string, mixed> $seed
 * @return list<string>
 */
function om_build_seed_tags(array $seed): array
{
	$tags = [
		'operator_matrix',
		(string) $seed['item_id'],
		str_replace('-', '_', (string) $seed['outcome_class']),
		str_replace('-', '_', (string) $seed['test_seed_class']),
	];
	$diagnosticClass = (string) (($seed['expected']['diagnostic_class'] ?? '') ?: '');
	if ($diagnosticClass !== '') {
		$tags[] = $diagnosticClass;
	}
	return array_values(array_unique(array_map(static fn (string $tag): string => strtolower(str_replace('.', '_', $tag)), $tags)));
}

/**
 * @param array<string, mixed> $seed
 */
function om_build_seed_notes(array $seed): string
{
	$notes = trim((string) ($seed['notes'] ?? ''));
	if ($notes !== '') {
		return 'Generated matrix seed. ' . $notes;
	}
	return 'Generated matrix seed.';
}

/**
 * @return list<string>
 */
function om_expected_generator_substrings(string $diagnosticClass): array
{
	return match ($diagnosticClass) {
		'coalesce_reject_result_or_bool' => ['result_or_bool', 'coalesce'],
		'unsupported_elvis_lhs_type' => ['elvis'],
		'coalesce_rhs_has_no_usable_value_domain' => ['coalesce'],
		default => [],
	};
}


/**
 * @param array<string, mixed> $negativeGenerateReport
 */
function om_assert_strict_negative_generate_enablement(array $negativeGenerateReport): void
{
	if (!(bool) ($negativeGenerateReport['strict_enable'] ?? false)) {
		return;
	}

	foreach (($negativeGenerateReport['enabled_diagnostics'] ?? []) as $diagnosticClass) {
		$diagnosticClass = (string) $diagnosticClass;
		$stats = $negativeGenerateReport['per_diagnostic'][$diagnosticClass] ?? null;
		if (!is_array($stats) || (int) ($stats['emitted_count'] ?? 0) === 0) {
			throw new RuntimeException('Strict negative_generate enablement failed: diagnostic class emitted zero tests: ' . $diagnosticClass);
		}
	}
}

function om_render_expected_var_dump(string $profile): string
{
	return match ($profile) {
		'bool.false', 'mixed.bool.false' => "bool(false)\n",
		'bool.true', 'mixed.bool.true' => "bool(true)\n",
		'int.zero', 'mixed.int.zero' => "int(0)\n",
		'int.nonzero', 'mixed.int.nonzero' => "int(7)\n",
		'float.zero', 'mixed.float.zero' => "float(0)\n",
		'float.nonzero', 'mixed.float.nonzero' => "float(3.5)\n",
		'string.empty', 'mixed.string.empty' => "string(0) \"\"\n",
		'string.zero_string', 'mixed.string.zero_string' => "string(1) \"0\"\n",
		'string.bool_false_literal', 'mixed.string.bool_false_literal' => "string(5) \"false\"\n",
		'string.bool_true_literal', 'mixed.string.bool_true_literal' => "string(4) \"true\"\n",
		'string.nonempty_nonzero_nonbool_literal', 'mixed.string.nonempty_nonzero_nonbool_literal' => "string(5) \"hello\"\n",
		'mixed.null' => "NULL\n",
		'mixed.hash.empty' => "array(0) {\n}\n",
		'mixed.hash.nonempty' => "array(1) {\n  [\"k\"]=>\n  int(1)\n}\n",
		default => throw new RuntimeException('Unsupported expected var_dump profile: ' . $profile),
	};
}

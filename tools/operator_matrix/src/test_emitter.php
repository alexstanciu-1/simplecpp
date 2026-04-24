<?php

declare(strict_types=1);

/**
 * Emit concrete matrix tests from deterministic test seeds.
 *
 * @param list<array<string, mixed>> $seeds
 * @param array<string, mixed> $options
 * @return array<string, mixed>
 */
function om_emit_matrix_tests(string $repoRoot, array $seeds, array $options): array
{
	$emitterRoutes = om_emitter_routes();
	om_assert_emitter_routes_are_valid($emitterRoutes);

	$rootsBySuite = [];
	$emitted = [];
	foreach ($emitterRoutes as $route) {
		$suite = (string) $route['suite'];
		$root = $repoRoot . '/tests/' . $suite;
		$rootsBySuite[$suite] = $root;
		om_recreate_generated_tree($root);
		$emitted[$suite] = [
			'test_count' => 0,
			'source_files' => 0,
			'info_files' => 0,
			'enabled_test_count' => 0,
			'disabled_test_count' => 0,
		];
	}

	$emitted['negative_generate'] = [
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
	];

	$manifestsBySuite = [];
	$featureOrdinalsBySuite = [];
	foreach ($seeds as $seed) {
		$route = om_resolve_emitter_route($seed, $emitterRoutes);
		$emit = $route['emitter'];
		$emittedResult = $emit($repoRoot, $seed, $options, $route, $featureOrdinalsBySuite);
		if ($emittedResult === null) {
			continue;
		}

		$suite = (string) $route['suite'];
		$enabled = (bool) ($emittedResult['info']['enabled'] ?? false);
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

		$manifestsBySuite[$suite][] = [
			'seed_id' => $seed['seed_id'],
			'suite' => $suite,
			'source_path' => $emittedResult['relative_source_path'],
			'info_path' => $emittedResult['relative_info_path'],
			'outcome_class' => $seed['outcome_class'],
			'test_seed_class' => $seed['test_seed_class'],
			'diagnostic_class' => om_seed_diagnostic_class($seed),
			'enabled' => $enabled,
			'status' => $emittedResult['info']['status'],
		];
	}

	foreach ($emitterRoutes as $route) {
		$suite = (string) $route['suite'];
		$root = $rootsBySuite[$suite];
		om_write_json_file($root . '/_manifest.json', [
			'generated_at_utc' => gmdate('c'),
			'suite' => $suite,
			'test_count' => $emitted[$suite]['test_count'],
			'entries' => array_values($manifestsBySuite[$suite] ?? []),
		]);
	}

	om_assert_strict_negative_generate_enablement($emitted['negative_generate']);
	ksort($emitted['negative_generate']['per_diagnostic']);

	return $emitted;
}

/**
 * @return list<array<string, mixed>>
 */
function om_emitter_routes(): array
{
	return [
		[
			'suite' => 'php-matrix',
			'target_flow' => 'php',
			'emitter' => 'om_emit_php_matrix_seed',
		],
		[
			'suite' => 'runtime-matrix',
			'target_flow' => 'runtime',
			'emitter' => 'om_emit_runtime_matrix_seed',
		],
	];
}

/**
 * @param list<array<string, mixed>> $routes
 */
function om_assert_emitter_routes_are_valid(array $routes): void
{
	$seenSuites = [];
	foreach ($routes as $routeIndex => $route) {
		$suite = (string) ($route['suite'] ?? '');
		if ($suite === '') {
			throw new RuntimeException('Emitter route is missing suite at index ' . $routeIndex);
		}
		if (isset($seenSuites[$suite])) {
			throw new RuntimeException('Multiple emitter routes declared for suite: ' . $suite);
		}

		$emitter = $route['emitter'] ?? null;
		if (!is_string($emitter) || $emitter === '' || !function_exists($emitter)) {
			throw new RuntimeException('Emitter route for suite ' . $suite . ' references an unknown emitter.');
		}

		$targetFlow = $route['target_flow'] ?? null;
		if (!is_string($targetFlow) || $targetFlow === '') {
			throw new RuntimeException('Emitter route for suite ' . $suite . ' is missing target_flow.');
		}

		$seenSuites[$suite] = true;
	}
}

/**
 * @param list<array<string, mixed>> $seeds
 */
function om_validate_emitter_routing(array $seeds): void
{
	$routes = om_emitter_routes();
	om_assert_emitter_routes_are_valid($routes);

	foreach ($seeds as $index => $seed) {
		try {
			om_resolve_emitter_route($seed, $routes);
		} catch (RuntimeException $exception) {
			$seedId = (string) ($seed['seed_id'] ?? 'missing-seed-id');
			throw new RuntimeException(
				'Emitter routing failed for seed[' . $index . '] (' . $seedId . '): ' . $exception->getMessage(),
				0,
				$exception
			);
		}
	}
}

/**
 * @param array<string, mixed> $seed
 * @param list<array<string, mixed>> $routes
 * @return array<string, mixed>
 */
function om_resolve_emitter_route(array $seed, array $routes): array
{
	$suite = (string) ($seed['suite'] ?? '');
	$targetFlow = (string) ($seed['target_flow'] ?? '');
	foreach ($routes as $route) {
		if (($route['suite'] ?? null) !== $suite) {
			continue;
		}
		if (($route['target_flow'] ?? null) !== $targetFlow) {
			throw new RuntimeException(
				'Emitter route target_flow mismatch for suite ' . $suite . ': seed=' . $targetFlow . ' route=' . (string) $route['target_flow']
			);
		}
		return $route;
	}

	throw new RuntimeException('No emitter route defined for suite: ' . $suite);
}

function om_recreate_generated_tree(string $root): void
{
	om_ensure_directory($root);
	om_clear_directory_contents($root);
	file_put_contents($root . '/.gitkeep', '');
}

function om_clear_directory_contents(string $path): void
{
	if (!is_dir($path)) {
		return;
	}

	$entries = scandir($path);
	if ($entries === false) {
		throw new RuntimeException('Unable to scan generated tree: ' . $path);
	}

	foreach ($entries as $entry) {
		if ($entry === '.' || $entry === '..') {
			continue;
		}

		$entryPath = $path . '/' . $entry;
		om_delete_directory_entry($entryPath);
	}
}

function om_delete_directory_entry(string $path): void
{
	if (is_dir($path)) {
		om_clear_directory_contents($path);
		om_retry_delete(static function () use ($path): bool {
			if (@rmdir($path)) {
				return true;
			}

			if (is_dir($path)) {
				om_clear_directory_contents($path);
				return @rmdir($path);
			}

			return false;
		}, $path, 'directory');
		return;
	}

	om_retry_delete(static function () use ($path): bool {
		return @unlink($path);
	}, $path, 'file');
}

/**
 * @param callable(): bool $deleter
 */
function om_retry_delete(callable $deleter, string $path, string $kind): void
{
	$attempts = 5;
	for ($attempt = 0; $attempt < $attempts; $attempt++) {
		clearstatcache(true, $path);
		if (!file_exists($path) && !is_dir($path)) {
			return;
		}
		if ($deleter()) {
			clearstatcache(true, $path);
			if (!file_exists($path) && !is_dir($path)) {
				return;
			}
		}
		usleep(50000);
	}

	clearstatcache(true, $path);
	if (file_exists($path) || is_dir($path)) {
		throw new RuntimeException('Unable to delete generated ' . $kind . ': ' . $path);
	}
}

function om_delete_directory(string $path): void
{
	if (!is_dir($path)) {
		return;
	}

	om_clear_directory_contents($path);
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
		RecursiveIteratorIterator::CHILD_FIRST
	);
	foreach ($iterator as $entry) {
		$entryPath = $entry->getPathname();
		if ($entry->isDir()) {
			@rmdir($entryPath);
			continue;
		}

		@unlink($entryPath);
	}

	@rmdir($path);
	if (is_dir($path)) {
		throw new RuntimeException('Unable to fully delete generated tree: ' . $path);
	}
}

/**
 * @param array<string, mixed> $seed
 * @param array<string, mixed> $options
 * @param array<string, mixed> $route
 * @param array<string, array<string, int>> $featureOrdinalsBySuite
 * @return array<string, mixed>|null
 */
function om_emit_php_matrix_seed(string $repoRoot, array $seed, array $options, array $route, array &$featureOrdinalsBySuite): ?array
{
	if (!om_should_emit_seed($seed, $options)) {
		return null;
	}

	$suite = (string) $route['suite'];
	$feature = (string) ($seed['feature'] ?? 'matrix');
	$featureOrdinalsBySuite[$suite][$feature] = ($featureOrdinalsBySuite[$suite][$feature] ?? 0) + 1;
	$stem = om_build_emitted_test_stem($seed, (int) $featureOrdinalsBySuite[$suite][$feature]);
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

	return [
		'relative_source_path' => $relativeSourcePath,
		'relative_info_path' => $relativeInfoPath,
		'info' => $infoContent,
	];
}

/**
 * @param array<string, mixed> $seed
 * @param array<string, mixed> $options
 * @param array<string, mixed> $route
 * @param array<string, array<string, int>> $featureOrdinalsBySuite
 * @return array<string, mixed>|null
 */
function om_emit_runtime_matrix_seed(string $repoRoot, array $seed, array $options, array $route, array &$featureOrdinalsBySuite): ?array
{
	return null;
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
		$targetKind = (string) (($operand['target_kind'] ?? '') ?: '');
		if (!is_string($type) || $type === '' || !is_string($profile) || $profile === '') {
			continue;
		}
		if ($name === 'lhs' && (string) ($seed['item_id'] ?? '') === 'unset_keyed' && $type === 'mixed_t' && $profile === 'mixed.hash.empty') {
			$declared[] = '$lhs /** mixed */ = ["k" => 1];';
			continue;
		}
		if ($name === 'lhs' && $targetKind === 'keyed_element' && om_seed_supports_keyed_element_target($seed)) {
			$declared[] = '$lhs /** ' . om_map_seed_type_to_vector_annotation($type) . ' */ = [' . om_render_seed_profile_literal($type, $profile) . '];';
			continue;
		}
		if ($name === 'lhs' && $targetKind === 'member_property' && om_seed_supports_member_property_target($seed)) {
			$declared[] = 'class OMMemberTarget { ' . om_render_seed_property_declaration('value', $type) . ' }';
			$declared[] = '$lhs = new OMMemberTarget();';
			$declared[] = '$lhs->value = ' . om_render_seed_profile_literal($type, $profile) . ';';
			continue;
		}
		if ($name === 'lhs' && $targetKind === 'chained_writable_path' && om_seed_supports_chained_writable_path_target($seed)) {
			$declared[] = 'class OMChainLeaf { ' . om_render_seed_property_declaration('value', $type) . ' }';
			$declared[] = 'class OMChainedTarget { public OMChainLeaf $slot; }';
			$declared[] = '$lhs = new OMChainedTarget();';
			$declared[] = '$lhs->slot = new OMChainLeaf();';
			$declared[] = '$lhs->slot->value = ' . om_render_seed_profile_literal($type, $profile) . ';';
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
	foreach (om_render_seed_php_lines($seed) as $line) {
		$lines[] = $line;
	}
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
function om_render_seed_php_lines(array $seed): array
{
	$itemId = (string) ($seed['item_id'] ?? '');
	if ($itemId === 'if_condition') {
		return [
			'if ($lhs) {',
			"\t" . 'var_dump(true);',
			'} else {',
			"\t" . 'var_dump(false);',
			'}',
		];
	}
	if ($itemId === 'unset_value') {
		$lhsOperand = is_array(($seed['operands']['lhs'] ?? null)) ? $seed['operands']['lhs'] : [];
		$lhsTargetKind = (string) (($lhsOperand['target_kind'] ?? '') ?: '');
		if ($lhsTargetKind === 'temporary_result') {
			return [
				'function om_make_unset_value_temp() {',
				"\t" . 'return 1;',
				'}',
				'unset(om_make_unset_value_temp());',
				'var_dump(true);',
			];
		}
		return [
			'unset($lhs);',
			'var_dump(true);',
		];
	}
	if ($itemId === 'unset_keyed') {
		$lhsOperand = is_array(($seed['operands']['lhs'] ?? null)) ? $seed['operands']['lhs'] : [];
		$lhsTargetKind = (string) (($lhsOperand['target_kind'] ?? '') ?: '');
		if ($lhsTargetKind === 'temporary_result') {
			return [
				'function om_make_unset_keyed_temp() {',
				"\t" . 'return ["k" => 1];',
				'}',
				'unset(om_make_unset_keyed_temp()["k"]);',
				'var_dump(true);',
			];
		}
		return [
			'unset($lhs["k"]);',
			'var_dump(isset($lhs["k"]));',
		];
	}
	if (in_array($itemId, ['pre_increment', 'post_increment', 'pre_decrement', 'post_decrement'], true)) {
		return ['var_dump(' . om_render_seed_expression($seed) . ');'];
	}
	if (in_array($itemId, [
		'add_assign',
		'subtract_assign',
		'multiply_assign',
		'divide_assign',
		'modulo_assign',
		'bitwise_and_assign',
		'bitwise_or_assign',
		'bitwise_xor_assign',
		'shift_left_assign',
		'shift_right_assign',
	], true)) {
		return ['var_dump(' . om_render_seed_expression($seed) . ');'];
	}

	return ['var_dump(' . om_render_seed_expression($seed) . ');'];
}

/**
 * @param array<string, mixed> $seed
 */
function om_render_seed_expression(array $seed): string
{
	$itemId = (string) ($seed['item_id'] ?? '');
	$lhsOperand = is_array(($seed['operands']['lhs'] ?? null)) ? $seed['operands']['lhs'] : [];
	$lhsTargetKind = (string) (($lhsOperand['target_kind'] ?? '') ?: '');
	$lhsValue = match (true) {
		$lhsTargetKind === 'keyed_element' && om_seed_supports_keyed_element_target($seed) => '$lhs[0]',
		$lhsTargetKind === 'member_property' && om_seed_supports_member_property_target($seed) => '$lhs->value',
		$lhsTargetKind === 'chained_writable_path' && om_seed_supports_chained_writable_path_target($seed) => '$lhs->slot->value',
		default => '$lhs',
	};
	return match ($itemId) {
		'add_assign' => $lhsValue . ' += $rhs',
		'subtract_assign' => $lhsValue . ' -= $rhs',
		'multiply_assign' => $lhsValue . ' *= $rhs',
		'divide_assign' => $lhsValue . ' /= $rhs',
		'modulo_assign' => $lhsValue . ' %= $rhs',
		'bitwise_and_assign' => $lhsValue . ' &= $rhs',
		'bitwise_or_assign' => $lhsValue . ' |= $rhs',
		'bitwise_xor_assign' => $lhsValue . ' ^= $rhs',
		'shift_left_assign' => $lhsValue . ' <<= $rhs',
		'shift_right_assign' => $lhsValue . ' >>= $rhs',
		'add' => '$lhs + $rhs',
		'subtract' => '$lhs - $rhs',
		'multiply' => '$lhs * $rhs',
		'divide' => '$lhs / $rhs',
		'modulo' => '$lhs % $rhs',
		'bitwise_and' => '$lhs & $rhs',
		'bitwise_or' => '$lhs | $rhs',
		'bitwise_xor' => '$lhs ^ $rhs',
		'shift_left' => '$lhs << $rhs',
		'shift_right' => '$lhs >> $rhs',
		'logical_and' => '$lhs && $rhs',
		'logical_or' => '$lhs || $rhs',
		'equal' => '$lhs == $rhs',
		'not_equal' => '$lhs != $rhs',
		'identical' => '$lhs === $rhs',
		'not_identical' => '!($lhs === $rhs)',
		'less_than' => '$lhs < $rhs',
		'less_than_or_equal' => '$lhs <= $rhs',
		'greater_than' => '$lhs > $rhs',
		'greater_than_or_equal' => '$lhs >= $rhs',
		'cast_bool' => '(bool) $lhs',
		'cast_int' => '(int) $lhs',
		'cast_float' => '(float) $lhs',
		'cast_string' => '(string) $lhs',
		'isset_value' => 'isset($lhs)',
		'empty_value' => 'empty($lhs)',
		'count_value' => 'count($lhs)',
		'isset_keyed' => 'isset($lhs["k"])',
		'empty_keyed' => 'empty($lhs["k"])',
		'logical_not' => '!$lhs',
		'unary_plus' => '+$lhs',
		'unary_minus' => '-$lhs',
		'bitwise_not' => '~$lhs',
		'pre_increment' => '++$lhs',
		'post_increment' => '$lhs++',
		'pre_decrement' => '--$lhs',
		'post_decrement' => '$lhs--',
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

function om_map_seed_type_to_vector_annotation(string $type): string
{
	return 'vector<' . om_map_seed_type_to_php_annotation($type) . '>';
}

function om_render_seed_property_declaration(string $propertyName, string $type): string
{
	$annotation = om_map_seed_type_to_php_annotation($type);
	if (str_contains($annotation, '<')) {
		return 'public /** ' . $annotation . ' */ $' . $propertyName . ';';
	}

	return 'public ' . $annotation . ' $' . $propertyName . ';';
}

/**
 * @param array<string, mixed> $seed
 */
function om_seed_supports_keyed_element_target(array $seed): bool
{
	$itemId = (string) ($seed['item_id'] ?? '');
	return in_array($itemId, [
		'add_assign',
		'subtract_assign',
		'multiply_assign',
		'divide_assign',
		'modulo_assign',
		'bitwise_and_assign',
		'bitwise_or_assign',
		'bitwise_xor_assign',
		'shift_left_assign',
		'shift_right_assign',
	], true);
}

/**
 * @param array<string, mixed> $seed
 */
function om_seed_supports_member_property_target(array $seed): bool
{
	return om_seed_supports_keyed_element_target($seed);
}

/**
 * @param array<string, mixed> $seed
 */
function om_seed_supports_chained_writable_path_target(array $seed): bool
{
	return om_seed_supports_keyed_element_target($seed);
}

function om_render_seed_profile_literal(string $type, string $profile): string
{
	if (str_starts_with($type, 'result_or_false<') && $profile === 'result_or_false.sentinel.false') {
		return 'null';
	}

	if ($type === 'result_or_bool<bool_t>') {
		if ($profile === 'result_or_bool.sentinel.false') {
			return 'null';
		}
		if ($profile === 'result_or_bool.sentinel.true') {
			return 'true_sentinel';
		}
	}

	return match ($profile) {
		'bool.false' => 'false',
		'bool.true' => 'true',
		'int.zero' => '0',
		'int.one' => '1',
		'int.fourteen' => '14',
		'int.forty_nine' => '49',
		'int.neg_seven' => '-7',
		'int.nonzero' => '7',
		'float.zero' => '0.0',
		'float.one' => '1.0',
		'float.two' => '2.0',
		'float.half' => '0.5',
		'float.neg_seven' => '-7.0',
		'float.nonzero' => '3.5',
		'float.seven' => '7.0',
		'float.ten_point_five' => '10.5',
		'float.twelve_point_twenty_five' => '12.25',
		'float.twenty_four_point_five' => '24.5',
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
	$outcomeClass = (string) ($seed['outcome_class'] ?? '');
	$feature = (string) ($seed['feature'] ?? '');
	$familyId = (string) ($seed['family_id'] ?? '');
	$operands = is_array($seed['operands'] ?? null) ? $seed['operands'] : [];
	$lhsOperand = is_array($operands['lhs'] ?? null) ? $operands['lhs'] : [];
	$lhsType = (string) (($lhsOperand['type'] ?? '') ?: '');
	if ($outcomeClass === 'negative_runtime') {
		if ($familyId === 'condition_truthiness') {
			return true;
		}
		if (om_seed_supports_cast_wrapper_enablement_by_default($seed)) {
			return true;
		}
		return om_feature_supports_negative_runtime_enablement_by_default($feature);
	}

	if ($outcomeClass === 'negative_compile') {
		return $feature === 'unset_value';
	}

	if ($outcomeClass !== 'positive') {
		return false;
	}

	if ($familyId === 'condition_truthiness') {
		return true;
	}

	if (om_seed_supports_cast_wrapper_enablement_by_default($seed)) {
		return true;
	}

	$wrapperEnabledFeatures = [
		'add_assign' => true,
		'subtract_assign' => true,
		'multiply_assign' => true,
		'divide_assign' => true,
		'modulo_assign' => true,
		'bitwise_and' => true,
		'bitwise_or' => true,
		'bitwise_xor' => true,
		'shift_left' => true,
		'shift_right' => true,
		'bitwise_and_assign' => true,
		'bitwise_or_assign' => true,
		'bitwise_xor_assign' => true,
		'shift_left_assign' => true,
		'shift_right_assign' => true,
	];

	$hasWrapperOperand = false;
	$lhsTargetKind = '';
	foreach (['lhs', 'rhs', 'third'] as $name) {
		$operand = is_array($operands[$name] ?? null) ? $operands[$name] : [];
		$type = (string) (($operand['type'] ?? '') ?: '');
		if ($name === 'lhs') {
			$lhsTargetKind = (string) (($operand['target_kind'] ?? '') ?: '');
		}
		if ($type === '') {
			continue;
		}
		if (str_contains($type, 'nullable<') || str_contains($type, 'result')) {
			$hasWrapperOperand = true;
		}
	}

	if ($hasWrapperOperand) {
		return isset($wrapperEnabledFeatures[$feature]);
	}

	return true;
}

/**
 * @param array<string, mixed> $seed
 */
function om_seed_supports_cast_wrapper_enablement_by_default(array $seed): bool
{
	$familyId = (string) ($seed['family_id'] ?? '');
	return $familyId === 'casts_explicit' || $familyId === 'condition_truthiness';
}

function om_feature_supports_negative_runtime_enablement_by_default(string $feature): bool
{
	return isset([
		'logical_not' => true,
		'unary_plus' => true,
		'unary_minus' => true,
		'bitwise_not' => true,
		'pre_increment' => true,
		'post_increment' => true,
		'pre_decrement' => true,
		'post_decrement' => true,
		'add_assign' => true,
		'subtract_assign' => true,
		'multiply_assign' => true,
		'divide_assign' => true,
		'modulo_assign' => true,
		'bitwise_and' => true,
		'bitwise_or' => true,
		'bitwise_xor' => true,
		'shift_left' => true,
		'shift_right' => true,
		'bitwise_and_assign' => true,
		'bitwise_or_assign' => true,
		'bitwise_xor_assign' => true,
		'shift_left_assign' => true,
		'shift_right_assign' => true,
	][$feature]);
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
	$negativeCompile = ($outcome === 'negative_compile');
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
					'error_contains' => [],
				]
				: [
					'success' => false,
					'error_contains' => [],
					'error_json' => $negativeRuntime ? om_expected_runtime_json((string) ($expected['diagnostic_class'] ?? '')) : [],
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
		'unsupported_elvis_lhs_type' => ['elvis'],
		'coalesce_selected_branch_has_no_usable_value_domain' => [],
		default => [],
	};
}

/**
 * @return array<string, string>
 */
function om_expected_runtime_json(string $diagnosticClass): array
{
	return match ($diagnosticClass) {
		'coalesce_reject_result_or_bool' => [
			'code' => 'coalesce_reject_result_or_bool',
			'component' => 'php::coalesce_eval',
			'operator' => '??',
		],
		'coalesce_selected_branch_has_no_usable_value_domain' => [
			'code' => 'coalesce_selected_branch_has_no_usable_value_domain',
			'component' => 'php::coalesce_eval',
			'operator' => '??',
		],
		'invalid_cast_string_literal' => [
			'code' => 'invalid_cast_string_literal',
			'component' => 'scpp::cast<bool_t>',
		],
		'invalid_cast_int_literal' => [
			'code' => 'invalid_cast_int_literal',
			'component' => 'scpp::cast<int_t>',
		],
		'invalid_cast_float_literal' => [
			'code' => 'invalid_cast_float_literal',
			'component' => 'scpp::cast<float_t>',
		],
		'invalid_mixed_kind_for_cast_bool' => [
			'code' => 'invalid_mixed_kind_for_cast_bool',
			'component' => 'scpp::cast<bool_t>',
		],
		'invalid_mixed_kind_for_cast_int' => [
			'code' => 'invalid_mixed_kind_for_cast_int',
			'component' => 'scpp::cast<int_t>',
		],
		'invalid_mixed_kind_for_cast_float' => [
			'code' => 'invalid_mixed_kind_for_cast_float',
			'component' => 'scpp::cast<float_t>',
		],
		'invalid_mixed_kind_for_cast_string' => [
			'code' => 'invalid_mixed_kind_for_cast_string',
			'component' => 'scpp::cast<string_t>',
		],
		'invalid_nullable_unwrap_empty' => [
			'code' => 'invalid_nullable_unwrap_empty',
			'component' => 'scpp::nullable_unwrap',
		],
		'division_by_zero' => [
			'code' => 'division_by_zero',
		],
		'modulo_by_zero' => [
			'code' => 'modulo_by_zero',
		],
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
		'int.one', 'mixed.int.one' => "int(1)\n",
		'int.fourteen', 'mixed.int.fourteen' => "int(14)\n",
		'int.forty_nine', 'mixed.int.forty_nine' => "int(49)\n",
		'int.three', 'mixed.int.three' => "int(3)\n",
		'int.six', 'mixed.int.six' => "int(6)\n",
		'int.eight', 'mixed.int.eight' => "int(8)\n",
		'int.eight_hundred_ninety_six', 'mixed.int.eight_hundred_ninety_six' => "int(896)\n",
		'int.neg_one', 'mixed.int.neg_one' => "int(-1)\n",
		'int.neg_seven', 'mixed.int.neg_seven' => "int(-7)\n",
		'int.neg_eight', 'mixed.int.neg_eight' => "int(-8)\n",
		'int.nonzero', 'mixed.int.nonzero' => "int(7)\n",
		'float.zero', 'mixed.float.zero' => "float(0)\n",
		'float.neg_zero', 'mixed.float.neg_zero' => "float(-0)\n",
		'float.one', 'mixed.float.one' => "float(1)\n",
		'float.two', 'mixed.float.two' => "float(2)\n",
		'float.half', 'mixed.float.half' => "float(0.5)\n",
		'float.neg_one', 'mixed.float.neg_one' => "float(-1)\n",
		'float.neg_seven', 'mixed.float.neg_seven' => "float(-7)\n",
		'float.two_point_five', 'mixed.float.two_point_five' => "float(2.5)\n",
		'float.four_point_five', 'mixed.float.four_point_five' => "float(4.5)\n",
		'float.seven', 'mixed.float.seven' => "float(7)\n",
		'float.ten_point_five', 'mixed.float.ten_point_five' => "float(10.5)\n",
		'float.twelve_point_twenty_five', 'mixed.float.twelve_point_twenty_five' => "float(12.25)\n",
		'float.twenty_four_point_five', 'mixed.float.twenty_four_point_five' => "float(24.5)\n",
		'float.neg_3_5', 'mixed.float.neg_3_5' => "float(-3.5)\n",
		'float.nonzero', 'mixed.float.nonzero' => "float(3.5)\n",
		'string.empty', 'mixed.string.empty' => "string(0) \"\"\n",
		'string.one', 'mixed.string.one' => "string(1) \"1\"\n",
		'string.seven', 'mixed.string.seven' => "string(1) \"7\"\n",
		'string.float_3_5', 'mixed.string.float_3_5' => "string(3) \"3.5\"\n",
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

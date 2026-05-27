<?php
declare(strict_types=1);

use Scpp\S2S\Lowering\TypeMapper;
use Scpp\S2S\Stan\StanWorkspaceSession;

/** @return array<string,mixed> */
function build_debug_function_plan(string $projectRoot, array $options): array
{
	$sessionId = isset($options['session_id']) && is_string($options['session_id']) && $options['session_id'] !== ''
		? $options['session_id']
		: debug_generate_session_id();
	$sessionLabel = isset($options['session_label']) && is_string($options['session_label']) && $options['session_label'] !== ''
		? $options['session_label']
		: 'function-debug';
	$callableName = trim((string) ($options['callable'] ?? ''));
	if ($callableName === '') {
		scpp_fail('`--call` requires a callable name.' . PHP_EOL, 1);
	}
	$callArgsJson = isset($options['call_args_json']) && is_string($options['call_args_json'])
		? $options['call_args_json']
		: '[]';
	$callThisJson = isset($options['call_this_json']) && is_string($options['call_this_json']) && trim($options['call_this_json']) !== ''
		? $options['call_this_json']
		: null;
	$resolved = resolve_debug_function_target($projectRoot, $callableName, $callArgsJson, $callThisJson);

	return [
		'version' => 1,
		'session' => [
			'id' => $sessionId,
			'label' => $sessionLabel,
			'created_at' => gmdate('c'),
		],
		'mode' => 'function',
		'target' => [
			'project_root' => normalize_path($projectRoot),
			'entry' => [
				'kind' => (string) ($resolved['kind'] ?? 'function'),
				'callable' => (string) $resolved['callable'],
				'resolved_file' => (string) $resolved['resolved_file'],
				'resolved_line' => (int) $resolved['resolved_line'],
				'namespace' => (string) $resolved['namespace'],
				'function_name' => (string) $resolved['function_name'],
				'params' => $resolved['params'],
				'return_type' => (string) $resolved['return_type'],
				'cpp_return_type' => (string) $resolved['cpp_return_type'],
				'cpp_callable' => (string) $resolved['cpp_callable'],
				'owner_class' => (string) ($resolved['owner_class'] ?? ''),
				'this_cpp_type' => (string) ($resolved['this_cpp_type'] ?? ''),
			],
		],
		'inputs' => [
			'call_args_json' => $callArgsJson,
			'call_this_json' => is_string($resolved['call_this_json'] ?? null) ? (string) $resolved['call_this_json'] : null,
			'argv' => [],
			'env' => $options['env'] ?? [],
		],
		'actions' => array_values($options['actions'] ?? []),
		'output' => [
			'format' => (string) ($options['format'] ?? 'text'),
			'summary' => (bool) ($options['summary'] ?? true),
			'destination' => [
				'kind' => 'stdout',
			],
		],
		'resolution' => [
			'resolver' => 'stan',
			'status' => 'resolved',
		],
		'build' => [
			'variant' => 'debug',
			'instrumentation_scope' => 'narrow',
			'build_options' => $options['build_options'] ?? [],
		],
	];
}

/** @return array<string,mixed> */
function build_debug_exec_plan(string $projectRoot, array $options): array
{
	$sessionId = isset($options['session_id']) && is_string($options['session_id']) && $options['session_id'] !== ''
		? $options['session_id']
		: debug_generate_session_id();
	$expression = trim((string) ($options['exec_expression'] ?? ''));
	if ($expression === '') {
		scpp_fail('`--exec` requires an expression.' . PHP_EOL, 1);
	}

	return [
		'version' => 1,
		'session' => [
			'id' => $sessionId,
			'label' => 'exec-debug',
			'created_at' => gmdate('c'),
		],
		'mode' => 'exec',
		'target' => [
			'project_root' => normalize_path($projectRoot),
			'entry' => [
				'kind' => 'exec',
				'expression' => $expression,
			],
		],
		'inputs' => [
			'argv' => [],
			'env' => $options['env'] ?? [],
		],
		'actions' => [],
		'output' => [
			'format' => (string) ($options['format'] ?? 'text'),
			'summary' => (bool) ($options['summary'] ?? true),
			'destination' => [
				'kind' => 'stdout',
			],
		],
		'resolution' => [
			'resolver' => 'none',
			'status' => 'resolved',
		],
		'build' => [
			'variant' => 'debug',
			'instrumentation_scope' => 'narrow',
			'build_options' => $options['build_options'] ?? [],
		],
	];
}

/** @return array<string,mixed> */
function resolve_debug_function_target(string $projectRoot, string $callableName, string $callArgsJson, ?string $callThisJson = null): array
{
	$project = find_project_config($projectRoot);
	if ($project === null) {
		scpp_fail('No ' . SCPP_PROJECT_CONFIG . ' found for debug call resolution.' . PHP_EOL, 1);
	}

	try {
		$decodedArgs = json_decode($callArgsJson, true, 512, JSON_THROW_ON_ERROR);
	} catch (JsonException $exception) {
		scpp_fail('Invalid `--call-args` JSON: ' . $exception->getMessage() . PHP_EOL, 1);
	}
	if (!is_array($decodedArgs) || !array_is_list($decodedArgs)) {
		scpp_fail('`--call-args` must decode to a JSON array.' . PHP_EOL, 1);
	}

	$session = new StanWorkspaceSession();
	$snapshot = $session->createBridgeSnapshot($project['project_root'], $project['config_path'], []);
	$semanticResult = is_array($snapshot['semantic_result'] ?? null) ? $snapshot['semantic_result'] : [];
	$symbolIndex = is_array($semanticResult['symbol_index'] ?? null) ? $semanticResult['symbol_index'] : [];
	$symbol = find_debug_function_symbol($symbolIndex, $callableName);
	if ($symbol === null) {
		scpp_fail('Debug callable not found: `' . $callableName . '`.' . PHP_EOL, 1);
	}

	$params = is_array($symbol['params'] ?? null) ? $symbol['params'] : [];
	if (count($decodedArgs) !== count($params)) {
		scpp_fail(
			'`--call-args` count mismatch for `' . $callableName . '`: expected '
			. count($params)
			. ', got '
			. count($decodedArgs)
			. '.'
			. PHP_EOL,
			1
		);
	}

	$typeMapper = new TypeMapper();
	$resolvedParams = [];
	foreach ($params as $index => $param) {
		if (!is_array($param)) {
			scpp_fail('Callable parameter metadata is malformed for `' . $callableName . '`.' . PHP_EOL, 1);
		}
		$paramType = trim((string) ($param['type'] ?? ''));
		$paramName = (string) ($param['name'] ?? ('arg' . $index));
		if ($paramType === '') {
			scpp_fail('`--call` currently requires explicit parameter types; `' . $callableName . '` parameter $' . ltrim($paramName, '$') . ' is untyped.' . PHP_EOL, 1);
		}
		$mappedCppType = $typeMapper->mapDeclaredType($paramType);
		if (!debug_call_is_supported_cpp_type($mappedCppType)) {
			scpp_fail(
				'`--call` does not yet support parameter type `'
				. $paramType
				. '` for $'
				. ltrim($paramName, '$')
				. ' in `'
				. $callableName
				. '`.'
				. PHP_EOL,
				1
			);
		}
		$resolvedParams[] = [
			'name' => $paramName,
			'type' => $paramType,
			'cpp_type' => $mappedCppType,
		];
	}

	$scope = trim((string) ($symbol['scope'] ?? ''));
	$functionName = (string) ($symbol['name'] ?? '');
	$kind = (string) ($symbol['kind'] ?? 'function');
	$qualifiedCallable = $kind === 'method'
		? ($scope === '' ? $functionName : $scope . '::' . $functionName)
		: ($scope === '' ? $functionName : $scope . '\\' . $functionName);
	$returnType = trim((string) ($symbol['return_type'] ?? ''));
	$cppReturnType = $returnType === '' ? 'auto' : $typeMapper->mapDeclaredType($returnType);
	$isStatic = (bool) ($symbol['is_static'] ?? false);
	$ownerClass = (string) ($symbol['owner_class'] ?? '');
	$thisCppType = '';
	if ($kind === 'method' && !$isStatic) {
		if ($callThisJson === null || trim($callThisJson) === '') {
			scpp_fail('Instance method debug calls require `--call-this=<json-value>`.' . PHP_EOL, 1);
		}
		$ownerPhpType = str_replace('::', '\\', $scope);
		$thisCppType = $typeMapper->mapDeclaredType($ownerPhpType);
	}
	return [
		'callable' => $qualifiedCallable,
		'resolved_file' => normalize_path((string) ($symbol['path'] ?? '')),
		'resolved_line' => (int) ($symbol['line'] ?? 0),
		'namespace' => $scope,
		'function_name' => $functionName,
		'params' => $resolvedParams,
		'return_type' => $returnType,
		'cpp_return_type' => $cppReturnType,
		'cpp_callable' => build_debug_cpp_callable($kind, $scope, $functionName),
		'kind' => $kind === 'method' ? ($isStatic ? 'static_method' : 'method') : 'function',
		'owner_class' => $ownerClass,
		'this_cpp_type' => $thisCppType,
		'call_this_json' => $callThisJson,
	];
}

/** @param list<array<string,mixed>> $symbolIndex @return array<string,mixed>|null */
function find_debug_function_symbol(array $symbolIndex, string $callableName): ?array
{
	$normalized = ltrim(trim($callableName), '\\');
	$matches = [];
	foreach ($symbolIndex as $symbol) {
		if (!is_array($symbol) || (string) ($symbol['kind'] ?? '') !== 'function') {
			if ((string) ($symbol['kind'] ?? '') !== 'method') {
				continue;
			}
		}
		$scope = trim((string) ($symbol['scope'] ?? ''));
		$name = trim((string) ($symbol['name'] ?? ''));
		if ($name === '') {
			continue;
		}
		$qualified = $scope === '' ? $name : $scope . '::' . $name;
		$phpQualified = str_replace('::', '\\', $qualified);
		if ($qualified === $normalized || $phpQualified === $normalized || $name === $normalized) {
			$matches[] = $symbol;
		}
	}
	if ($matches === []) {
		return null;
	}
	if (count($matches) > 1) {
		$qualifiedMatches = array_map(
			static function (array $symbol): string {
				$scope = trim((string) ($symbol['scope'] ?? ''));
				$name = trim((string) ($symbol['name'] ?? ''));
				return $scope === '' ? $name : $scope . '\\' . $name;
			},
			$matches
		);
		sort($qualifiedMatches, SORT_STRING);
		scpp_fail(
			'Debug callable `' . $callableName . '` is ambiguous. Use one of: '
			. implode(', ', $qualifiedMatches)
			. PHP_EOL,
			1
		);
	}
	return $matches[0];
}

function build_debug_cpp_callable(string $kind, string $namespacePhp, string $functionName): string
{
	$namespacePhp = trim($namespacePhp);
	if ($kind === 'function') {
		if ($namespacePhp === '') {
			return '::scpp::' . $functionName;
		}
		return '::scpp::' . str_replace('\\', '::', $namespacePhp) . '::' . $functionName;
	}
	return '::scpp::' . str_replace(['\\', '::'], '::', $namespacePhp) . '::' . $functionName;
}

function debug_call_is_supported_cpp_type(string $cppType): bool
{
	$cppType = trim($cppType);
	if ($cppType === '') {
		return false;
	}
	if (in_array($cppType, ['bool_t', 'int_t', 'float_t', 'string_t', 'mixed_t', 'dynamic_t<>'], true)) {
		return true;
	}
	if (preg_match('/^nullable<(.+)>$/', $cppType, $matches) === 1) {
		return debug_call_is_supported_cpp_type(trim($matches[1]));
	}
	if (preg_match('/^vector_t<(.+)>$/', $cppType, $matches) === 1) {
		return debug_call_is_supported_cpp_type(trim($matches[1]));
	}
	if (preg_match('/^hash_t<(.+)>$/', $cppType, $matches) === 1) {
		return debug_call_is_supported_cpp_type(trim($matches[1]));
	}
	if (preg_match('/^hash_t<(.+),\s*string_t>$/', $cppType, $matches) === 1) {
		return debug_call_is_supported_cpp_type(trim($matches[1]));
	}
	return false;
}

/** @param array{slot_name:string,slot_root:string,slot_relative_root:string,source_root:string,generated_root:string,cache_root:string,build_root:string,native_cpp_root:string,session_metadata_path:string} $debugWorkspace
 *  @return array{entry_relative:string,entry_path:string,native_cpp_path:string}
 */
function materialize_debug_function_harness(array $plan, array $debugWorkspace): array
{
	$projectRoot = (string) (($plan['target']['project_root'] ?? ''));
	$target = is_array($plan['target']['entry'] ?? null) ? $plan['target']['entry'] : [];
	$sourceDir = (string) $debugWorkspace['source_root'];
	$nativeDir = (string) $debugWorkspace['native_cpp_root'];
	ensure_directory($sourceDir);
	ensure_directory($nativeDir);

	$entryRelative = normalize_config_path((string) $debugWorkspace['slot_relative_root'] . '/source/__scpp_debug_call_main.phs');
	$entryPath = normalize_path($projectRoot . '/' . $entryRelative);
	$nativeCppPath = normalize_path($nativeDir . '/__scpp_debug_call_entry.cpp');
	$generatedHeader = debug_function_generated_header_path($projectRoot, (string) $debugWorkspace['generated_root'], (string) ($target['resolved_file'] ?? ''));
	$callArgsJson = (string) ($plan['inputs']['call_args_json'] ?? '[]');
	$cppCallable = (string) ($target['cpp_callable'] ?? '');
	$params = is_array($target['params'] ?? null) ? $target['params'] : [];
	$returnType = (string) ($target['cpp_return_type'] ?? 'auto');

	write_text_file($entryPath, "__scpp_debug_call_entry();\n");
	write_text_file(
		$nativeCppPath,
		render_debug_function_native_cpp(
			$generatedHeader,
			(string) ($target['kind'] ?? 'function'),
			$cppCallable,
			(string) ($target['function_name'] ?? ''),
			(string) ($target['this_cpp_type'] ?? ''),
			$params,
			(string) ($plan['inputs']['call_this_json'] ?? ''),
			$callArgsJson,
			$returnType
		)
	);

	return [
		'entry_relative' => $entryRelative,
		'entry_path' => $entryPath,
		'native_cpp_path' => $nativeCppPath,
	];
}

function debug_function_generated_header_path(string $projectRoot, string $generatedDir, string $resolvedFile): string
{
	$relativePhp = normalize_config_path(relative_path($projectRoot, $resolvedFile));
	return build_generated_base($generatedDir, $relativePhp) . '.hpp';
}

/** @param list<array{name:string,type:string,cpp_type:string}> $params */
function render_debug_function_native_cpp(
	string $generatedHeader,
	string $entryKind,
	string $cppCallable,
	string $methodName,
	string $thisCppType,
	array $params,
	string $callThisJson,
	string $callArgsJson,
	string $returnType
): string
{
	$lines = [];
	$lines[] = '#include ' . debug_cpp_include_literal($generatedHeader);
	$lines[] = '#include "scpp/json/from_json.hpp"';
	$lines[] = '#include "scpp/support/dbg.hpp"';
	$lines[] = '';
	$lines[] = 'namespace scpp::php {';
	$lines[] = 'namespace {';
	$lines[] = 'inline std::string __scpp_debug_json_escape(std::string_view value) {';
	$lines[] = '	return ::scpp::runtime_error_json_escape(value);';
	$lines[] = '}';
	$lines[] = '';
	$lines[] = 'template <typename TValue>';
	$lines[] = 'void __scpp_debug_emit_call_result(const TValue& value) {';
	$lines[] = '	std::cout';
	$lines[] = '		<< "__SCPP_DEBUG_EVENT__ "';
	$lines[] = '		<< "{\"event\":\"call_result\",\"body\":{\"value\":{\"type\":\""';
	$lines[] = '		<< __scpp_debug_json_escape(::scpp::php::dbg_detail::type_name<TValue>())';
	$lines[] = '		<< "\",\"preview\":\""';
	$lines[] = '		<< __scpp_debug_json_escape(::scpp::php::dbg_detail::inline_value(value))';
	$lines[] = '		<< "\"}}}"';
	$lines[] = '		<< std::endl;';
	$lines[] = '}';
	$lines[] = '';
	$lines[] = 'inline void __scpp_debug_emit_call_result_void() {';
	$lines[] = '	std::cout';
	$lines[] = '		<< "__SCPP_DEBUG_EVENT__ "';
	$lines[] = '		<< "{\"event\":\"call_result\",\"body\":{\"value\":{\"type\":\"void_t\",\"preview\":\"void\"}}}"';
	$lines[] = '		<< std::endl;';
	$lines[] = '}';
	$lines[] = '} // namespace';
	$lines[] = '';
	$lines[] = 'void __scpp_debug_call_entry() {';
	if ($entryKind === 'method') {
		$lines[] = '	auto __scpp_this_value = ::scpp::json::json_decode(::scpp::string_t(' . debug_cpp_string_literal($callThisJson) . '));';
		$lines[] = '	auto __scpp_this = ::scpp::json::from_json<' . $thisCppType . '>(__scpp_this_value);';
	}
	$lines[] = '	const auto __scpp_args_value = ::scpp::json::json_decode(::scpp::string_t(' . debug_cpp_string_literal($callArgsJson) . '));';
	$lines[] = '	const auto* __scpp_args_table = __scpp_args_value.table_if();';
	$lines[] = '	if (__scpp_args_table == nullptr || !static_cast<bool>(__scpp_args_table->is_packed())) {';
	$lines[] = '		throw ::scpp::runtime_error(';
	$lines[] = '			"Debug call args must decode to a JSON array.",';
	$lines[] = '			"debug_call_wrong_payload_shape",';
	$lines[] = '			"scpp::debug_call",';
	$lines[] = '			"",';
	$lines[] = '			std::vector<::scpp::runtime_error_detail_t>{';
	$lines[] = '				::scpp::runtime_error_detail_t{"target", "call_args_json"},';
	$lines[] = '				::scpp::runtime_error_detail_t{"expected_shape", "json_array"}';
	$lines[] = '			}';
	$lines[] = '		);';
	$lines[] = '	}';
	$lines[] = '	if (__scpp_args_table->size() != ' . count($params) . 'u) {';
	$lines[] = '		throw ::scpp::runtime_error(';
	$lines[] = '			"Debug call args count mismatch.",';
	$lines[] = '			"debug_call_arg_count_mismatch",';
	$lines[] = '			"scpp::debug_call",';
	$lines[] = '			"",';
	$lines[] = '			std::vector<::scpp::runtime_error_detail_t>{';
	$lines[] = '				::scpp::runtime_error_detail_t{"expected_count", "' . count($params) . '"},';
	$lines[] = '				::scpp::runtime_error_detail_t{"actual_count", std::to_string(__scpp_args_table->size())}';
	$lines[] = '			}';
	$lines[] = '		);';
	$lines[] = '	}';
	foreach ($params as $index => $param) {
		$cppType = (string) $param['cpp_type'];
		$varName = '__scpp_arg_' . $index;
		$lines[] = '	' . $cppType . ' ' . $varName . ' = ::scpp::json::from_json<' . $cppType . '>(__scpp_args_table->at(::scpp::int_t(' . $index . ')));';
	}
	$argList = implode(', ', array_map(static fn (int $index): string => '__scpp_arg_' . $index, array_keys($params)));
	$invokeExpr = match ($entryKind) {
		'method' => '__scpp_this->' . $methodName . '(' . $argList . ')',
		default => $cppCallable . '(' . $argList . ')',
	};
	if ($returnType === 'void') {
		$lines[] = '	' . $invokeExpr . ';';
		$lines[] = '	__scpp_debug_emit_call_result_void();';
	} else {
		$lines[] = '	auto __scpp_result = ' . $invokeExpr . ';';
		$lines[] = '	__scpp_debug_emit_call_result(__scpp_result);';
	}
	$lines[] = '}';
	$lines[] = '';
	$lines[] = '} // namespace scpp::php';
	return implode("\n", $lines) . "\n";
}

/** @param array{slot_name:string,slot_root:string,slot_relative_root:string,source_root:string,generated_root:string,cache_root:string,build_root:string,native_cpp_root:string,session_metadata_path:string} $debugWorkspace
 *  @return array{entry_relative:string,entry_path:string}
 */
function materialize_debug_exec_harness(array $plan, array $debugWorkspace): array
{
	$projectRoot = (string) (($plan['target']['project_root'] ?? ''));
	$sourceDir = (string) $debugWorkspace['source_root'];
	ensure_directory($sourceDir);
	$entryRelative = normalize_config_path((string) $debugWorkspace['slot_relative_root'] . '/source/__scpp_debug_exec_main.phs');
	$entryPath = normalize_path($projectRoot . '/' . $entryRelative);
	$expression = trim((string) (($plan['target']['entry']['expression'] ?? '')));
	$label = "'" . str_replace(['\\', "'"], ['\\\\', "\\'"], $expression) . "'";
	$source = '$__scpp_debug_exec_value = (' . $expression . ");\n";
	$source .= "__scpp_debug_dump('before', " . $label . ", \$__scpp_debug_exec_value);\n";
	write_text_file($entryPath, $source);
	return [
		'entry_relative' => $entryRelative,
		'entry_path' => $entryPath,
	];
}

function debug_cpp_include_literal(string $path): string
{
	return '"' . addcslashes($path, "\\\"\n\r\t\v\f") . '"';
}

function debug_cpp_string_literal(string $value): string
{
	return '"' . addcslashes($value, "\\\"\n\r\t\v\f") . '"';
}

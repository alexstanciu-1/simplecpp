<?php
declare(strict_types=1);

function handle_debug(string $cwd, array $args): void
{
	if ($args !== [] && in_array($args[0], ['-h', '--help'], true)) {
		print_debug_help();
		return;
	}

	$parsed = parse_debug_command_arguments($cwd, $args);
	$plan = isset($parsed['load_session_path']) && is_string($parsed['load_session_path']) && $parsed['load_session_path'] !== ''
		? load_debug_plan_from_file($parsed['load_session_path'])
		: (($parsed['mode'] ?? 'process') === 'function'
			? build_debug_function_plan($parsed['project_root'], $parsed)
			: (($parsed['mode'] ?? 'process') === 'exec'
				? build_debug_exec_plan($parsed['project_root'], $parsed)
				: build_debug_process_plan($parsed['project_root'], $parsed)));
	$plan = validate_debug_plan($plan);
	$projectRoot = (string) $plan['target']['project_root'];
	with_debug_session_lock($projectRoot, static function () use ($plan, $parsed, $projectRoot): void {
		$debugWorkspace = allocate_debug_slot_workspace($projectRoot, (string) (($plan['session']['id'] ?? 'debug')));
		$sourceOverrides = build_debug_source_overrides($plan);
		persist_debug_source_overrides((string) $debugWorkspace['source_root'], $projectRoot, $sourceOverrides);
		persist_debug_session_plan_artifact((string) $debugWorkspace['slot_root'], $plan);

		if (isset($parsed['save_session_path']) && is_string($parsed['save_session_path']) && $parsed['save_session_path'] !== '') {
			save_debug_plan_to_file($parsed['save_session_path'], $plan);
		}

		$events = ($plan['mode'] ?? 'process') === 'function'
			? execute_debug_function_session($plan, (bool) ($parsed['plan_only'] ?? false), $sourceOverrides, $debugWorkspace)
			: (($plan['mode'] ?? 'process') === 'exec'
				? execute_debug_exec_session($plan, (bool) ($parsed['plan_only'] ?? false), $debugWorkspace)
				: execute_debug_process_session($plan, (bool) ($parsed['plan_only'] ?? false), $sourceOverrides, $debugWorkspace));
		persist_debug_session_events_artifact((string) $debugWorkspace['slot_root'], $events);
		touch_debug_slot_workspace($projectRoot, $debugWorkspace, (string) debug_find_summary_status($events));
		emit_debug_events($plan, $events);

		$summaryStatus = debug_find_summary_status($events);
		if ($summaryStatus === 'failed') {
			exit(1);
		}
	});
}

function print_debug_help(): void
{
	echo "Usage: scpp debug [options]\n";
	echo "  --format=text|json|ndjson\n";
	echo "  --args=<json-array-of-strings>\n";
	echo "  --env=NAME=VALUE\n";
	echo "  --stdin-file=<path>\n";
	echo "  --entry=<path>\n";
	echo "  --call=<function-name>\n";
	echo "  --call-args=<json-array>\n";
	echo "  --call-this=<json-value>\n";
	echo "  --exec=<expr>\n";
	echo "  --exit=<file:line>\n";
	echo "  --break=<file:line>\n";
	echo "  --dump-before=<file:line:expr>\n";
	echo "  --dump-after=<file:line:expr>\n";
	echo "  --build-runtime\n";
	echo "  --build-dependencies\n";
	echo "  --force\n";
	echo "  --no-stan\n";
	echo "  --plan-only\n";
	echo "  --save-session=<path>\n";
	echo "  --load-session=<path>\n";
	echo "  --summary | --no-summary\n";
}

/** @return array<string,mixed> */
function parse_debug_command_arguments(string $cwd, array $args): array
{
	$project = find_project_config($cwd);
	if ($project === null) {
		scpp_fail('No ' . SCPP_PROJECT_CONFIG . ' found in the current directory or any parent directory.' . PHP_EOL . 'Run `scpp init` in the project root first.' . PHP_EOL, 1);
	}

	$result = [
		'project_root' => $project['project_root'],
		'mode' => 'process',
		'format' => 'text',
		'argv' => [],
		'env' => [],
		'stdin_file' => null,
		'callable' => null,
		'call_args_json' => '[]',
		'call_this_json' => null,
		'exec_expression' => null,
		'plan_only' => false,
		'save_session_path' => null,
		'load_session_path' => null,
		'summary' => true,
		'build_options' => [
			'entry_override' => null,
			'compile_runtime' => false,
			'compile_dependencies' => false,
			'force_runtime_rebuild' => false,
			'disable_stan' => false,
		],
		'actions' => [],
	];

	foreach ($args as $arg) {
		if (str_starts_with($arg, '--format=')) {
			$result['format'] = substr($arg, strlen('--format='));
			continue;
		}
		if (str_starts_with($arg, '--args=')) {
			$result['argv'] = parse_debug_argv_json(substr($arg, strlen('--args=')));
			continue;
		}
		if (str_starts_with($arg, '--env=')) {
			$pair = substr($arg, strlen('--env='));
			[$name, $value] = parse_debug_env_assignment($pair);
			$result['env'][$name] = $value;
			continue;
		}
		if (str_starts_with($arg, '--stdin-file=')) {
			$result['stdin_file'] = normalize_path(resolve_cli_input_path($project['project_root'], substr($arg, strlen('--stdin-file='))));
			continue;
		}
		if (str_starts_with($arg, '--call=')) {
			$result['mode'] = 'function';
			$result['callable'] = trim(substr($arg, strlen('--call=')));
			continue;
		}
		if (str_starts_with($arg, '--call-args=')) {
			$result['call_args_json'] = substr($arg, strlen('--call-args='));
			continue;
		}
		if (str_starts_with($arg, '--call-this=')) {
			$result['call_this_json'] = substr($arg, strlen('--call-this='));
			continue;
		}
		if (str_starts_with($arg, '--exec=')) {
			$result['mode'] = 'exec';
			$result['exec_expression'] = trim(substr($arg, strlen('--exec=')));
			continue;
		}
		if (str_starts_with($arg, '--exit=')) {
			$result['actions'][] = build_debug_exit_action($project['project_root'], substr($arg, strlen('--exit=')));
			continue;
		}
		if (str_starts_with($arg, '--break=')) {
			$result['actions'][] = build_debug_break_action($project['project_root'], substr($arg, strlen('--break=')));
			continue;
		}
		if (str_starts_with($arg, '--dump-before=')) {
			$result['actions'][] = build_debug_dump_before_action($project['project_root'], substr($arg, strlen('--dump-before=')));
			continue;
		}
		if (str_starts_with($arg, '--dump-after=')) {
			$result['actions'][] = build_debug_dump_after_action($project['project_root'], substr($arg, strlen('--dump-after=')));
			continue;
		}
		if (str_starts_with($arg, '--save-session=')) {
			$result['save_session_path'] = normalize_path(resolve_cli_input_path($cwd, substr($arg, strlen('--save-session='))));
			continue;
		}
		if (str_starts_with($arg, '--load-session=')) {
			$result['load_session_path'] = normalize_path(resolve_cli_input_path($cwd, substr($arg, strlen('--load-session='))));
			continue;
		}
		if ($arg === '--plan-only') {
			$result['plan_only'] = true;
			continue;
		}
		if ($arg === '--summary') {
			$result['summary'] = true;
			continue;
		}
		if ($arg === '--no-summary') {
			$result['summary'] = false;
			continue;
		}
		if (str_starts_with($arg, '--entry=')) {
			$result['build_options']['entry_override'] = substr($arg, strlen('--entry='));
			continue;
		}
		if ($arg === '--build-runtime') {
			$result['build_options']['compile_runtime'] = true;
			continue;
		}
		if ($arg === '--build-dependencies') {
			$result['build_options']['compile_dependencies'] = true;
			continue;
		}
		if ($arg === '--force') {
			$result['build_options']['compile_runtime'] = true;
			$result['build_options']['force_runtime_rebuild'] = true;
			continue;
		}
		if ($arg === '--no-stan') {
			$result['build_options']['disable_stan'] = true;
			continue;
		}
		scpp_fail('Unknown option for `scpp debug`: ' . $arg . PHP_EOL, 1);
	}

	return $result;
}

/** @return array<string,mixed> */
function build_debug_dump_before_action(string $projectRoot, string $spec): array
{
	return build_debug_dump_action($projectRoot, $spec, 'dump_before', '--dump-before');
}

/** @return array<string,mixed> */
function build_debug_break_action(string $projectRoot, string $spec): array
{
	$location = build_debug_source_location_action($projectRoot, $spec, '--break');
	return [
		'kind' => 'break',
		'location' => $location['location'],
	];
}

/** @return array<string,mixed> */
function build_debug_dump_after_action(string $projectRoot, string $spec): array
{
	return build_debug_dump_action($projectRoot, $spec, 'dump_after', '--dump-after');
}

/** @return array<string,mixed> */
function build_debug_dump_action(string $projectRoot, string $spec, string $kind, string $flagName): array
{
	$lastColon = strrpos($spec, ':');
	if ($lastColon === false || $lastColon === 0 || $lastColon === strlen($spec) - 1) {
		scpp_fail('`' . $flagName . '` must use file:line:expr.' . PHP_EOL, 1);
	}
	$subject = substr($spec, $lastColon + 1);
	$locationSpec = substr($spec, 0, $lastColon);
	$location = build_debug_exit_action($projectRoot, $locationSpec);
	return [
		'kind' => $kind,
		'location' => $location['location'],
		'subject' => parse_debug_dump_subject($subject, $flagName),
	];
}

/** @return array<string,mixed> */
function parse_debug_dump_subject(string $subject, string $flagName): array
{
	$subject = trim($subject);
	if ($subject === '') {
		scpp_fail('`' . $flagName . '` requires an injected expression.' . PHP_EOL, 1);
	}
	if (preg_match('/^\$[A-Za-z_][A-Za-z0-9_]*$/', $subject) === 1) {
		return [
			'kind' => 'local_name',
			'name' => $subject,
			'text' => $subject,
		];
	}
	return [
		'kind' => 'expr_text',
		'text' => $subject,
	];
}

/** @return array<string,mixed> */
function build_debug_exit_action(string $projectRoot, string $spec): array
{
	$location = build_debug_source_location_action($projectRoot, $spec, '--exit');
	return [
		'kind' => 'exit',
		'location' => $location['location'],
	];
}

/** @return array<string,mixed> */
function build_debug_source_location_action(string $projectRoot, string $spec, string $flagName): array
{
	$colon = strrpos($spec, ':');
	if ($colon === false || $colon === 0 || $colon === strlen($spec) - 1) {
		scpp_fail('`' . $flagName . '` must use file:line.' . PHP_EOL, 1);
	}
	$file = substr($spec, 0, $colon);
	$lineText = substr($spec, $colon + 1);
	if (!ctype_digit($lineText) || (int) $lineText <= 0) {
		scpp_fail('`' . $flagName . '` requires a positive line number.' . PHP_EOL, 1);
	}
	$resolvedFile = normalize_path(resolve_cli_input_path($projectRoot, $file));
	if (!is_file($resolvedFile)) {
		scpp_fail('Debug source file not found for ' . $flagName . ': ' . $resolvedFile . PHP_EOL, 1);
	}
	return [
		'location' => [
			'file' => $resolvedFile,
			'line' => (int) $lineText,
		],
	];
}

/** @return list<string> */
function parse_debug_argv_json(string $json): array
{
	try {
		$data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
	} catch (JsonException $exception) {
		scpp_fail('Invalid `--args` JSON: ' . $exception->getMessage() . PHP_EOL, 1);
	}
	if (!is_array($data)) {
		scpp_fail('`--args` must decode to a JSON array of strings.' . PHP_EOL, 1);
	}
	$result = [];
	foreach ($data as $value) {
		if (!is_string($value)) {
			scpp_fail('`--args` must contain only strings.' . PHP_EOL, 1);
		}
		$result[] = $value;
	}
	return $result;
}

/** @return array{0:string,1:string} */
function parse_debug_env_assignment(string $assignment): array
{
	$pos = strpos($assignment, '=');
	if ($pos === false || $pos === 0) {
		scpp_fail('`--env` must use NAME=VALUE.' . PHP_EOL, 1);
	}
	return [substr($assignment, 0, $pos), substr($assignment, $pos + 1)];
}

/** @param array<string,array{source:string,line_map:list<array{debug_line:int,original_line:int,relation:string}>}> $sourceOverrides
 *  @param array{slot_name:string,slot_root:string,slot_relative_root:string,source_root:string,generated_root:string,cache_root:string,build_root:string,native_cpp_root:string,session_metadata_path:string} $debugWorkspace
 *  @return list<array<string,mixed>>
 */
function execute_debug_process_session(array $plan, bool $planOnly, array $sourceOverrides = [], array $debugWorkspace = []): array
{
	$events = [];
	$seq = 1;
	$sessionId = (string) (($plan['session']['id'] ?? ''));
	$summaryEnabled = (bool) (($plan['output']['summary'] ?? true));
	$startedAt = microtime(true);

	$events[] = build_debug_event('session_start', $seq++, $sessionId, [
		'mode' => 'process',
		'format' => (string) (($plan['output']['format'] ?? 'text')),
		'summary_enabled' => $summaryEnabled,
		'plan_only' => $planOnly,
	]);

	if ($planOnly) {
		if ($summaryEnabled) {
			$events[] = build_debug_event('session_summary', $seq++, $sessionId, [
				'status' => 'completed',
				'event_count' => count($events) + 1,
				'hit_count' => 0,
				'dump_count' => 0,
				'error_count' => 0,
				'exit_code' => 0,
				'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
				'plan_only' => true,
			]);
		}
		return $events;
	}

	$runResult = run_debug_process_plan($plan, $sourceOverrides, $debugWorkspace);
	if (($runResult['build_ok'] ?? true) !== true) {
		$buildFailure = classify_build_failure(
			(string) ($runResult['build_stdout'] ?? ''),
			(string) ($runResult['build_stderr'] ?? '')
		);
		$events[] = build_debug_event('build_error', $seq++, $sessionId, [
			'message' => (string) ($buildFailure['short_message'] ?? 'Debug build failed.'),
			'category' => (string) ($buildFailure['category'] ?? 'build'),
			'subcategory' => (string) ($buildFailure['subcategory'] ?? 'build_failed'),
			'guidance' => array_values(is_array($buildFailure['guidance'] ?? null) ? $buildFailure['guidance'] : []),
			'build_stdout' => (string) ($runResult['build_stdout'] ?? ''),
			'build_stderr' => (string) ($runResult['build_stderr'] ?? ''),
		]);
	}
	foreach ((array) ($runResult['debug_events'] ?? []) as $debugEvent) {
		$body = is_array($debugEvent['body'] ?? null) ? $debugEvent['body'] : [];
		$source = is_array($debugEvent['source'] ?? null) ? $debugEvent['source'] : null;
		$events[] = build_debug_event(
			(string) ($debugEvent['event'] ?? 'debug_event'),
			$seq++,
			$sessionId,
			$body,
			$source
		);
	}
	if ($runResult['runtime_error'] !== null) {
		$diagnostic = $runResult['runtime_error'];
		$source = null;
		$originalFile = isset($diagnostic['original_file']) && is_string($diagnostic['original_file']) ? $diagnostic['original_file'] : null;
		$originalLine = isset($diagnostic['original_line']) && is_int($diagnostic['original_line']) ? $diagnostic['original_line'] : null;
		if ($originalFile !== null && $originalFile !== '' && $originalLine !== null && $originalLine > 0) {
			$source = ['file' => $originalFile, 'line' => $originalLine];
		}
		$runtimeFailure = classify_debug_runtime_failure($diagnostic);
		$sourceTrace = build_runtime_source_trace_frames((string) ($plan['target']['project_root'] ?? ''), $diagnostic);
		$events[] = build_debug_event('runtime_error', $seq++, $sessionId, [
			'code' => (string) ($diagnostic['code'] ?? 'runtime_error'),
			'component' => (string) ($diagnostic['operation'] ?? ''),
			'operator' => isset($diagnostic['operator']) ? (string) $diagnostic['operator'] : null,
			'message' => (string) ($diagnostic['message'] ?? 'Runtime error.'),
			'category' => (string) ($runtimeFailure['category'] ?? 'runtime'),
			'subcategory' => (string) ($runtimeFailure['subcategory'] ?? 'runtime_error'),
			'guidance' => array_values(is_array($runtimeFailure['guidance'] ?? null) ? $runtimeFailure['guidance'] : []),
			'expected_type' => isset($diagnostic['expected_type']) ? (string) $diagnostic['expected_type'] : null,
			'actual_runtime_kind' => isset($diagnostic['actual_runtime_kind']) ? (string) $diagnostic['actual_runtime_kind'] : null,
			'json_path' => isset($diagnostic['json_path']) ? (string) $diagnostic['json_path'] : null,
			'target_type' => isset($diagnostic['target_type']) ? (string) $diagnostic['target_type'] : null,
			'actual_kind' => isset($diagnostic['actual_kind']) ? (string) $diagnostic['actual_kind'] : null,
			'expression' => isset($diagnostic['expression']) ? (string) $diagnostic['expression'] : null,
			'trace' => array_values(array_filter(
				is_array($diagnostic['trace'] ?? null) ? $diagnostic['trace'] : [],
				static fn ($line): bool => is_string($line) && trim($line) !== ''
			)),
			'source_trace' => $sourceTrace,
		], $source);
	}

	if ($summaryEnabled) {
		$errorCount = 0;
		$hasExitEvent = false;
		$hasBreakEvent = false;
		$hitCount = 0;
		$dumpCount = 0;
		foreach ((array) ($runResult['debug_events'] ?? []) as $debugEvent) {
			$type = (string) ($debugEvent['event'] ?? '');
			if ($type === 'exit') {
				$hasExitEvent = true;
			}
			if ($type === 'break') {
				$hasBreakEvent = true;
			}
			if ($type === 'hit') {
				$hitCount++;
			}
			if ($type === 'dump') {
				$dumpCount++;
			}
		}
		if (($runResult['runtime_error'] ?? null) !== null) {
			$errorCount++;
		}
		if (($runResult['build_ok'] ?? true) !== true) {
			$errorCount++;
		}
		$events[] = build_debug_event('session_summary', $seq++, $sessionId, [
			'status' => ($runResult['exit_code'] === 0 && $runResult['runtime_error'] === null && ($runResult['build_ok'] ?? true) === true)
				? ($hasBreakEvent ? 'stopped' : ($hasExitEvent ? 'exited' : 'completed'))
				: 'failed',
			'event_count' => count($events) + 1,
			'hit_count' => $hitCount,
			'dump_count' => $dumpCount,
			'error_count' => $errorCount,
			'exit_code' => $runResult['exit_code'],
			'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
			'build_ok' => (bool) ($runResult['build_ok'] ?? true),
			'build_stdout' => (string) ($runResult['build_stdout'] ?? ''),
			'build_stderr' => (string) ($runResult['build_stderr'] ?? ''),
			'program_stdout' => $runResult['stdout'],
			'program_stderr' => $runResult['stderr'],
		]);
	}

	if ((string) ($plan['output']['format'] ?? 'text') === 'text') {
		if ((string) ($runResult['build_stdout'] ?? '') !== '') {
			scpp_write((string) $runResult['build_stdout'], 'stdout');
		}
		if ((string) ($runResult['build_stderr'] ?? '') !== '') {
			scpp_write((string) $runResult['build_stderr'], 'stderr');
		}
		if ($runResult['stdout'] !== '') {
			scpp_write($runResult['stdout'], 'stdout');
		}
		if ($runResult['stderr'] !== '') {
			scpp_write($runResult['stderr'], 'stderr');
		}
	}

	return $events;
}

/** @param array<string,mixed> $diagnostic @return array{category:string,subcategory:string,guidance:list<string>} */
function classify_debug_runtime_failure(array $diagnostic): array
{
	$code = strtolower(trim((string) ($diagnostic['code'] ?? '')));
	$component = strtolower(trim((string) ($diagnostic['operation'] ?? '')));
	$expectedType = trim((string) ($diagnostic['expected_type'] ?? ''));
	$actualRuntimeKind = trim((string) ($diagnostic['actual_runtime_kind'] ?? ''));
	$expression = trim((string) ($diagnostic['expression'] ?? ''));

	if ($expectedType !== '' || $actualRuntimeKind !== '') {
		$guidance = [
			'Check the value shape at the failing source line and stabilize mixed values earlier if needed.',
		];
		if ($expectedType !== '' && $actualRuntimeKind !== '') {
			$guidance[] = 'Expected type `' . $expectedType . '` but saw runtime kind `' . $actualRuntimeKind . '`.';
		} elseif ($expectedType !== '') {
			$guidance[] = 'Expected type `' . $expectedType . '` at the failing source line.';
		}
		if ($expression !== '') {
			$guidance[] = 'Inspect the failing expression `' . $expression . '` in the rewritten debug source.';
		}
		return [
			'category' => 'runtime_type',
			'subcategory' => 'type_mismatch',
			'guidance' => $guidance,
		];
	}

	if (str_starts_with($code, 'coalesce_') || str_contains($component, 'coalesce')) {
		return [
			'category' => 'runtime_operator',
			'subcategory' => 'coalesce',
			'guidance' => [
				'Inspect the selected branch and make sure it can produce a usable value at runtime.',
			],
		];
	}

	if (str_starts_with($code, 'ternary_') || str_contains($component, 'ternary')) {
		return [
			'category' => 'runtime_operator',
			'subcategory' => 'ternary',
			'guidance' => [
				'Check the condition and branch values around the failing ternary expression.',
			],
		];
	}

	if ($code === 'json_from_json_conversion_failed' || $component === 'scpp::json::from_json') {
		$jsonPath = trim((string) ($diagnostic['json_path'] ?? ''));
		$targetType = trim((string) ($diagnostic['target_type'] ?? ''));
		$actualKind = trim((string) ($diagnostic['actual_kind'] ?? ''));
		$guidance = [
			'Check the JSON payload shape passed to `--call-args` against the callable parameter types.',
		];
		if ($targetType !== '') {
			$guidance[] = 'The conversion target was `' . $targetType . '`.';
		}
		if ($actualKind !== '') {
			$guidance[] = 'The JSON value kind at runtime was `' . $actualKind . '`.';
		}
		if ($jsonPath !== '') {
			$guidance[] = 'The failing JSON path was `' . $jsonPath . '`.';
		}
		return [
			'category' => 'runtime_input',
			'subcategory' => 'json_conversion',
			'guidance' => $guidance,
		];
	}

	return [
		'category' => 'runtime',
		'subcategory' => $code !== '' ? $code : 'runtime_error',
		'guidance' => [
			'Inspect the saved runtime diagnostic and the rewritten debug source around the failing line.',
		],
	];
}

/** @param array<string,array{source:string,line_map:list<array{debug_line:int,original_line:int,relation:string}>}> $sourceOverrides
 *  @param array{slot_name:string,slot_root:string,slot_relative_root:string,source_root:string,generated_root:string,cache_root:string,build_root:string,native_cpp_root:string,session_metadata_path:string} $debugWorkspace
 *  @return list<array<string,mixed>>
 */
function execute_debug_function_session(array $plan, bool $planOnly, array $sourceOverrides = [], array $debugWorkspace = []): array
{
	$artifacts = materialize_debug_function_harness($plan, $debugWorkspace);
	$events = [];
	$seq = 1;
	$sessionId = (string) (($plan['session']['id'] ?? ''));
	$summaryEnabled = (bool) (($plan['output']['summary'] ?? true));
	$startedAt = microtime(true);

	$events[] = build_debug_event('session_start', $seq++, $sessionId, [
		'mode' => 'function',
		'format' => (string) (($plan['output']['format'] ?? 'text')),
		'summary_enabled' => $summaryEnabled,
		'plan_only' => $planOnly,
		'callable' => (string) (($plan['target']['entry']['callable'] ?? '')),
	]);

	if ($planOnly) {
		if ($summaryEnabled) {
			$events[] = build_debug_event('session_summary', $seq++, $sessionId, [
				'status' => 'completed',
				'event_count' => count($events) + 1,
				'hit_count' => 0,
				'dump_count' => 0,
				'error_count' => 0,
				'exit_code' => 0,
				'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
				'plan_only' => true,
			]);
		}
		return $events;
	}

	$runResult = run_debug_function_plan($plan, $artifacts, $sourceOverrides, $debugWorkspace);
	if (($runResult['build_ok'] ?? true) !== true) {
		$buildFailure = classify_build_failure(
			(string) ($runResult['build_stdout'] ?? ''),
			(string) ($runResult['build_stderr'] ?? '')
		);
		$events[] = build_debug_event('build_error', $seq++, $sessionId, [
			'message' => (string) ($buildFailure['short_message'] ?? 'Debug build failed.'),
			'category' => (string) ($buildFailure['category'] ?? 'build'),
			'subcategory' => (string) ($buildFailure['subcategory'] ?? 'build_failed'),
			'guidance' => array_values(is_array($buildFailure['guidance'] ?? null) ? $buildFailure['guidance'] : []),
			'build_stdout' => (string) ($runResult['build_stdout'] ?? ''),
			'build_stderr' => (string) ($runResult['build_stderr'] ?? ''),
		]);
	}
	foreach ((array) ($runResult['debug_events'] ?? []) as $debugEvent) {
		$body = is_array($debugEvent['body'] ?? null) ? $debugEvent['body'] : [];
		$source = is_array($debugEvent['source'] ?? null) ? $debugEvent['source'] : null;
		$events[] = build_debug_event(
			(string) ($debugEvent['event'] ?? 'debug_event'),
			$seq++,
			$sessionId,
			$body,
			$source
		);
	}
	if ($runResult['runtime_error'] !== null) {
		$diagnostic = $runResult['runtime_error'];
		$runtimeFailure = classify_debug_runtime_failure($diagnostic);
		$sourceTrace = build_runtime_source_trace_frames((string) ($plan['target']['project_root'] ?? ''), $diagnostic);
		$events[] = build_debug_event('runtime_error', $seq++, $sessionId, [
			'code' => (string) ($diagnostic['code'] ?? 'runtime_error'),
			'component' => (string) ($diagnostic['operation'] ?? ''),
			'operator' => isset($diagnostic['operator']) ? (string) $diagnostic['operator'] : null,
			'message' => (string) ($diagnostic['message'] ?? 'Runtime error.'),
			'category' => (string) ($runtimeFailure['category'] ?? 'runtime'),
			'subcategory' => (string) ($runtimeFailure['subcategory'] ?? 'runtime_error'),
			'guidance' => array_values(is_array($runtimeFailure['guidance'] ?? null) ? $runtimeFailure['guidance'] : []),
			'expected_type' => isset($diagnostic['expected_type']) ? (string) $diagnostic['expected_type'] : null,
			'actual_runtime_kind' => isset($diagnostic['actual_runtime_kind']) ? (string) $diagnostic['actual_runtime_kind'] : null,
			'json_path' => isset($diagnostic['json_path']) ? (string) $diagnostic['json_path'] : null,
			'target_type' => isset($diagnostic['target_type']) ? (string) $diagnostic['target_type'] : null,
			'actual_kind' => isset($diagnostic['actual_kind']) ? (string) $diagnostic['actual_kind'] : null,
			'expression' => isset($diagnostic['expression']) ? (string) $diagnostic['expression'] : null,
			'trace' => array_values(array_filter(
				is_array($diagnostic['trace'] ?? null) ? $diagnostic['trace'] : [],
				static fn ($line): bool => is_string($line) && trim($line) !== ''
			)),
			'source_trace' => $sourceTrace,
		]);
	}

	if ($summaryEnabled) {
		$errorCount = 0;
		foreach ((array) ($runResult['debug_events'] ?? []) as $debugEvent) {
			if (($debugEvent['event'] ?? null) === 'runtime_error') {
				$errorCount++;
			}
		}
		if (($runResult['runtime_error'] ?? null) !== null) {
			$errorCount++;
		}
		if (($runResult['build_ok'] ?? true) !== true) {
			$errorCount++;
		}
		$events[] = build_debug_event('session_summary', $seq++, $sessionId, [
			'status' => ($runResult['exit_code'] === 0 && $runResult['runtime_error'] === null && ($runResult['build_ok'] ?? true) === true)
				? 'completed'
				: 'failed',
			'event_count' => count($events) + 1,
			'hit_count' => 0,
			'dump_count' => 0,
			'error_count' => $errorCount,
			'exit_code' => $runResult['exit_code'],
			'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
			'build_ok' => (bool) ($runResult['build_ok'] ?? true),
			'build_stdout' => (string) ($runResult['build_stdout'] ?? ''),
			'build_stderr' => (string) ($runResult['build_stderr'] ?? ''),
			'program_stdout' => $runResult['stdout'],
			'program_stderr' => $runResult['stderr'],
		]);
	}

	if ((string) ($plan['output']['format'] ?? 'text') === 'text') {
		if ((string) ($runResult['build_stdout'] ?? '') !== '') {
			scpp_write((string) $runResult['build_stdout'], 'stdout');
		}
		if ((string) ($runResult['build_stderr'] ?? '') !== '') {
			scpp_write((string) $runResult['build_stderr'], 'stderr');
		}
		if ($runResult['stdout'] !== '') {
			scpp_write($runResult['stdout'], 'stdout');
		}
		if ($runResult['stderr'] !== '') {
			scpp_write($runResult['stderr'], 'stderr');
		}
	}

	return $events;
}

/** @param array{slot_name:string,slot_root:string,slot_relative_root:string,source_root:string,generated_root:string,cache_root:string,build_root:string,native_cpp_root:string,session_metadata_path:string} $debugWorkspace
 *  @return list<array<string,mixed>>
 */
function execute_debug_exec_session(array $plan, bool $planOnly, array $debugWorkspace = []): array
{
	$artifacts = materialize_debug_exec_harness($plan, $debugWorkspace);
	$projectRoot = (string) (($plan['target']['project_root'] ?? ''));
	$buildOptions = is_array($plan['build']['build_options'] ?? null) ? $plan['build']['build_options'] : [];
	$buildOptions['entry_override'] = (string) $artifacts['entry_relative'];
	$execPlan = $plan;
	$execPlan['build']['build_options'] = $buildOptions;
	$events = execute_debug_process_session($execPlan, $planOnly, [], $debugWorkspace);
	if (isset($events[0]['event']) && $events[0]['event'] === 'session_start' && is_array($events[0]['body'] ?? null)) {
		$events[0]['body']['mode'] = 'exec';
	}
	return $events;
}

/** @param array<string,array{source:string,line_map:list<array{debug_line:int,original_line:int,relation:string}>}> $sourceOverrides
 *  @param array{slot_name:string,slot_root:string,slot_relative_root:string,source_root:string,generated_root:string,cache_root:string,build_root:string,native_cpp_root:string,session_metadata_path:string} $debugWorkspace
 *  @return array{exit_code:int,stdout:string,stderr:string,runtime_error:?array<string,mixed>,build_ok:bool,build_stdout:string,build_stderr:string,debug_events:list<array<string,mixed>>}
 */
function run_debug_process_plan(array $plan, array $sourceOverrides = [], array $debugWorkspace = []): array
{
	$projectRoot = (string) (($plan['target']['project_root'] ?? ''));
	$configPath = $projectRoot . '/' . SCPP_PROJECT_CONFIG;
	$buildOptions = is_array($plan['build']['build_options'] ?? null) ? $plan['build']['build_options'] : [];
	$buildOptions['build_mode'] = 'debug';
	$buildOptions['debug_session_id'] = (string) (($plan['session']['id'] ?? 'debug'));
	if ($debugWorkspace !== []) {
		$buildOptions['debug_session_root'] = (string) $debugWorkspace['slot_relative_root'];
	}
	$buildOptions['use_pch'] = false;
	if ($sourceOverrides !== []) {
		$buildOptions['source_overrides'] = flatten_debug_source_override_map($sourceOverrides);
	}
	$buildService = run_debug_build_with_runtime_retry($projectRoot, $configPath, $buildOptions);
	if (($buildService['ok'] ?? false) !== true) {
		return [
			'exit_code' => (int) ($buildService['exit_code'] ?? 1),
			'stdout' => '',
			'stderr' => '',
			'runtime_error' => null,
			'build_ok' => false,
			'build_stdout' => (string) ($buildService['output'] ?? ''),
			'build_stderr' => (string) ($buildService['error'] ?? ''),
			'debug_events' => [],
		];
	}
	$buildResult = is_array($buildService['result'] ?? null) ? $buildService['result'] : null;
	if ($buildResult === null) {
		scpp_fail('Debug build completed without a build result payload.' . PHP_EOL, 4);
	}

	$command = [$buildResult['output_path']];
	foreach ((array) ($plan['inputs']['argv'] ?? []) as $arg) {
		$command[] = $arg;
	}

	$stdinSpec = $plan['inputs']['stdin'] ?? null;
	$stdinPath = is_array($stdinSpec) && ($stdinSpec['kind'] ?? null) === 'file' ? (string) ($stdinSpec['path'] ?? '') : '';
	$stdinDescriptor = ['file', 'php://stdin', 'r'];
	if ($stdinPath !== '') {
		if (!is_file($stdinPath)) {
			scpp_fail('Debug stdin file not found: ' . $stdinPath . PHP_EOL, 1);
		}
		$stdinDescriptor = ['file', $stdinPath, 'r'];
	}

	$descriptor = [
		0 => $stdinDescriptor,
		1 => ['pipe', 'w'],
		2 => ['pipe', 'w'],
	];
	$processEnv = scpp_runtime_library_process_environment(
		is_string($buildResult['runtime_library_dir'] ?? null) ? $buildResult['runtime_library_dir'] : null
	);
	$processEnv['SCPP_ERROR_FORMAT'] = 'json';
	foreach ((array) ($plan['inputs']['env'] ?? []) as $name => $value) {
		$processEnv[(string) $name] = (string) $value;
	}
	$process = proc_open($command, $descriptor, $pipes, $projectRoot, scpp_build_process_environment($processEnv));
	if (!is_resource($process)) {
		scpp_fail('Failed to start debug program.' . PHP_EOL, 4);
	}
	$runOutput = scpp_collect_process_output($process, $pipes);
	$status = $runOutput['status'];
	if (!is_int($status)) {
		scpp_fail('Failed to read debug program exit status.' . PHP_EOL, 4);
	}
	$programStdout = (string) ($runOutput['stdout'] ?? '');
	$programStderr = (string) ($runOutput['stderr'] ?? '');
	$parsedDebugStdout = extract_debug_marker_events($programStdout);
	$programStdout = $parsedDebugStdout['stdout'];
	$parsedDebugStderr = extract_debug_marker_events($programStderr);
	$programStderr = $parsedDebugStderr['stdout'];
	$parsedEvents = array_merge($parsedDebugStdout['events'], $parsedDebugStderr['events']);
	$runtimeDiagnostic = $status !== 0 ? collect_runtime_error_diagnostic($programStderr) : null;
	if ($runtimeDiagnostic !== null) {
		$runtimeDiagnostic = remap_runtime_diagnostic(
			$projectRoot,
			(string) ($buildResult['build_dir'] ?? ''),
			$runtimeDiagnostic,
			is_array($buildResult['generated_artifact_origins'] ?? null) ? $buildResult['generated_artifact_origins'] : []
		);
	}
	$stderrToShow = $runtimeDiagnostic !== null ? trim(remove_runtime_error_json_lines($programStderr)) : $programStderr;

	return [
		'exit_code' => $status,
		'stdout' => $programStdout,
		'stderr' => $stderrToShow === '' ? '' : $stderrToShow . (str_ends_with($stderrToShow, PHP_EOL) ? '' : PHP_EOL),
		'runtime_error' => $runtimeDiagnostic,
		'build_ok' => true,
		'build_stdout' => (string) ($buildService['output'] ?? ''),
		'build_stderr' => (string) ($buildService['error'] ?? ''),
		'debug_events' => $parsedEvents,
	];
}

/** @param array{entry_relative:string,entry_path:string,native_cpp_path:string} $artifacts
 *  @param array<string,array{source:string,line_map:list<array{debug_line:int,original_line:int,relation:string}>}> $sourceOverrides
 *  @param array{slot_name:string,slot_root:string,slot_relative_root:string,source_root:string,generated_root:string,cache_root:string,build_root:string,native_cpp_root:string,session_metadata_path:string} $debugWorkspace
 *  @return array{exit_code:int,stdout:string,stderr:string,runtime_error:?array<string,mixed>,build_ok:bool,build_stdout:string,build_stderr:string,debug_events:list<array<string,mixed>>}
 */
function run_debug_function_plan(array $plan, array $artifacts, array $sourceOverrides = [], array $debugWorkspace = []): array
{
	$projectRoot = (string) (($plan['target']['project_root'] ?? ''));
	$configPath = $projectRoot . '/' . SCPP_PROJECT_CONFIG;
	$buildOptions = is_array($plan['build']['build_options'] ?? null) ? $plan['build']['build_options'] : [];
	$buildOptions['build_mode'] = 'debug';
	$buildOptions['debug_session_id'] = (string) (($plan['session']['id'] ?? 'debug'));
	if ($debugWorkspace !== []) {
		$buildOptions['debug_session_root'] = (string) $debugWorkspace['slot_relative_root'];
	}
	$buildOptions['use_pch'] = false;
	$buildOptions['entry_override'] = (string) $artifacts['entry_relative'];
	$buildOptions['extra_native_cpp_files'] = [(string) $artifacts['native_cpp_path']];
	$buildOptions['disable_stan'] = true;
	$buildOptions['append_runtime_modules'] = ['json'];
	if ($sourceOverrides !== []) {
		$buildOptions['source_overrides'] = flatten_debug_source_override_map($sourceOverrides);
	}

	$buildService = run_debug_build_with_runtime_retry($projectRoot, $configPath, $buildOptions);
	if (($buildService['ok'] ?? false) !== true) {
		return [
			'exit_code' => (int) ($buildService['exit_code'] ?? 1),
			'stdout' => '',
			'stderr' => '',
			'runtime_error' => null,
			'build_ok' => false,
			'build_stdout' => (string) ($buildService['output'] ?? ''),
			'build_stderr' => (string) ($buildService['error'] ?? ''),
			'debug_events' => [],
		];
	}
	$buildResult = is_array($buildService['result'] ?? null) ? $buildService['result'] : null;
	if ($buildResult === null) {
		scpp_fail('Debug build completed without a build result payload.' . PHP_EOL, 4);
	}

	$descriptor = [
		0 => ['file', 'php://stdin', 'r'],
		1 => ['pipe', 'w'],
		2 => ['pipe', 'w'],
	];
	$processEnv = scpp_runtime_library_process_environment(
		is_string($buildResult['runtime_library_dir'] ?? null) ? $buildResult['runtime_library_dir'] : null
	);
	$processEnv['SCPP_ERROR_FORMAT'] = 'json';
	foreach ((array) ($plan['inputs']['env'] ?? []) as $name => $value) {
		$processEnv[(string) $name] = (string) $value;
	}
	$process = proc_open([$buildResult['output_path']], $descriptor, $pipes, $projectRoot, scpp_build_process_environment($processEnv));
	if (!is_resource($process)) {
		scpp_fail('Failed to start debug call program.' . PHP_EOL, 4);
	}
	$runOutput = scpp_collect_process_output($process, $pipes);
	$status = $runOutput['status'];
	if (!is_int($status)) {
		scpp_fail('Failed to read debug call exit status.' . PHP_EOL, 4);
	}
	$programStdout = (string) ($runOutput['stdout'] ?? '');
	$programStderr = (string) ($runOutput['stderr'] ?? '');
	$parsedDebugStdout = extract_debug_marker_events($programStdout);
	$programStdout = $parsedDebugStdout['stdout'];
	$parsedDebugStderr = extract_debug_marker_events($programStderr);
	$programStderr = $parsedDebugStderr['stdout'];
	$parsedEvents = array_merge($parsedDebugStdout['events'], $parsedDebugStderr['events']);
	$runtimeDiagnostic = $status !== 0 ? collect_runtime_error_diagnostic($programStderr) : null;
	if ($runtimeDiagnostic !== null) {
		$runtimeDiagnostic = remap_runtime_diagnostic(
			$projectRoot,
			(string) ($buildResult['build_dir'] ?? ''),
			$runtimeDiagnostic,
			is_array($buildResult['generated_artifact_origins'] ?? null) ? $buildResult['generated_artifact_origins'] : []
		);
	}
	$stderrToShow = $runtimeDiagnostic !== null ? trim(remove_runtime_error_json_lines($programStderr)) : $programStderr;

	return [
		'exit_code' => $status,
		'stdout' => $programStdout,
		'stderr' => $stderrToShow === '' ? '' : $stderrToShow . (str_ends_with($stderrToShow, PHP_EOL) ? '' : PHP_EOL),
		'runtime_error' => $runtimeDiagnostic,
		'build_ok' => true,
		'build_stdout' => (string) ($buildService['output'] ?? ''),
		'build_stderr' => (string) ($buildService['error'] ?? ''),
		'debug_events' => $parsedEvents,
	];
}

/** @param array<string,mixed> $buildOptions @return array{ok:bool,result:?array<string,mixed>,output:string,error:string,exit_code:int|null} */
function run_debug_build_with_runtime_retry(string $projectRoot, string $configPath, array $buildOptions): array
{
	$buildService = scpp_run_build_service($projectRoot, $configPath, $buildOptions);
	if (($buildService['ok'] ?? false) === true || ($buildOptions['compile_runtime'] ?? false) === true) {
		return $buildService;
	}

	$buildFailure = classify_build_failure(
		(string) ($buildService['output'] ?? ''),
		(string) ($buildService['error'] ?? '')
	);
	if (($buildFailure['category'] ?? '') !== 'runtime_cache' || ($buildFailure['subcategory'] ?? '') !== 'missing_runtime_artifact') {
		return $buildService;
	}

	$retryOptions = $buildOptions;
	$retryOptions['compile_runtime'] = true;
	$retryBuildService = scpp_run_build_service($projectRoot, $configPath, $retryOptions);
	$retryBuildService['output'] = (string) ($buildService['output'] ?? '') . (string) ($buildService['error'] ?? '')
		. 'Retrying debug build with --build-runtime due to missing runtime artifact.' . PHP_EOL
		. (string) ($retryBuildService['output'] ?? '');
	return $retryBuildService;
}

/** @return array{stdout:string,events:list<array<string,mixed>>} */
function extract_debug_marker_events(string $stdout): array
{
	$events = [];
	$clean = [];
	foreach (preg_split('/\R/', $stdout) ?: [] as $line) {
		if (str_starts_with($line, '__SCPP_DEBUG_EVENT__ ')) {
			$json = substr($line, strlen('__SCPP_DEBUG_EVENT__ '));
			try {
				$data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
			} catch (JsonException) {
				continue;
			}
			if (is_array($data)) {
				$events[] = $data;
			}
			continue;
		}
		$clean[] = $line;
	}
	$cleanStdout = implode(PHP_EOL, $clean);
	if ($cleanStdout !== '' && str_ends_with($stdout, PHP_EOL)) {
		$cleanStdout .= PHP_EOL;
	}
	return [
		'stdout' => $cleanStdout,
		'events' => $events,
	];
}

/**
 * @template T
 * @param callable():T $callback
 * @return T
 */
function with_debug_session_lock(string $projectRoot, callable $callback)
{
	$debugRoot = normalize_path($projectRoot . '/.prism/debug');
	ensure_directory($debugRoot);
	$lockPath = $debugRoot . '/debug.lock';
	$lockHandle = fopen($lockPath, 'c+');
	if ($lockHandle === false) {
		scpp_fail('Failed to create debug session lock: ' . $lockPath . PHP_EOL, 2);
	}

	try {
		if (!flock($lockHandle, LOCK_EX)) {
			scpp_fail('Failed to lock debug session: ' . $lockPath . PHP_EOL, 2);
		}
		return $callback();
	} finally {
		flock($lockHandle, LOCK_UN);
		fclose($lockHandle);
	}
}

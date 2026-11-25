<?php
declare(strict_types=1);

function debug_generate_session_id(): string
{
	$random = bin2hex(random_bytes(4));
	$stamp = gmdate('Y-m-d\TH-i-s\Z');
	return 'dbg-' . $stamp . '-' . $random;
}

/** @return array<string,mixed> */
function build_debug_process_plan(string $projectRoot, array $options): array
{
	$sessionId = isset($options['session_id']) && is_string($options['session_id']) && $options['session_id'] !== ''
		? $options['session_id']
		: debug_generate_session_id();
	$sessionLabel = isset($options['session_label']) && is_string($options['session_label']) && $options['session_label'] !== ''
		? $options['session_label']
		: 'process-debug';

	$stdin = null;
	if (isset($options['stdin_file']) && is_string($options['stdin_file']) && $options['stdin_file'] !== '') {
		$stdin = [
			'kind' => 'file',
			'path' => normalize_path($options['stdin_file']),
		];
	}

	$plan = [
		'version' => 1,
		'session' => [
			'id' => $sessionId,
			'label' => $sessionLabel,
			'created_at' => gmdate('c'),
		],
		'mode' => 'process',
		'target' => [
			'project_root' => normalize_path($projectRoot),
			'entry' => [
				'kind' => 'project_entry',
			],
		],
		'inputs' => [
			'argv' => array_values($options['argv'] ?? []),
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
			'resolver' => 'none',
			'status' => 'resolved',
		],
		'build' => [
			'variant' => 'debug',
			'instrumentation_scope' => 'narrow',
			'build_options' => $options['build_options'] ?? [],
		],
	];
	if ($stdin !== null) {
		$plan['inputs']['stdin'] = $stdin;
	}
	return $plan;
}

/** @return array<string,mixed> */
function validate_debug_plan(array $plan): array
{
	if (($plan['version'] ?? null) !== 1) {
		scpp_fail('Unsupported debug plan version; expected `1`.' . PHP_EOL, 1);
	}
	$mode = (string) ($plan['mode'] ?? '');
	if (!in_array($mode, ['process', 'function', 'exec'], true)) {
		scpp_fail('This debug slice currently supports only `process`, `function`, and `exec` modes.' . PHP_EOL, 1);
	}
	$projectRoot = is_array($plan['target'] ?? null) ? (string) (($plan['target']['project_root'] ?? '')) : '';
	if ($projectRoot === '') {
		scpp_fail('Debug plan is missing `target.project_root`.' . PHP_EOL, 1);
	}
	$format = is_array($plan['output'] ?? null) ? (string) (($plan['output']['format'] ?? '')) : '';
	if (!in_array($format, ['text', 'json', 'ndjson'], true)) {
		scpp_fail('Unsupported debug output format `' . $format . '`.' . PHP_EOL, 1);
	}
	$argv = is_array($plan['inputs']['argv'] ?? null) ? $plan['inputs']['argv'] : [];
	foreach ($argv as $arg) {
		if (!is_string($arg)) {
			scpp_fail('Debug plan `inputs.argv` must contain only strings.' . PHP_EOL, 1);
		}
	}
	$env = is_array($plan['inputs']['env'] ?? null) ? $plan['inputs']['env'] : [];
	foreach ($env as $key => $value) {
		if (!is_string($key) || $key === '' || !is_string($value)) {
			scpp_fail('Debug plan `inputs.env` must be a string-to-string map.' . PHP_EOL, 1);
		}
	}
	$stdin = $plan['inputs']['stdin'] ?? null;
	if ($stdin !== null) {
		if (!is_array($stdin) || ($stdin['kind'] ?? null) !== 'file' || !is_string($stdin['path'] ?? null) || (string) $stdin['path'] === '') {
			scpp_fail('Debug plan `inputs.stdin` currently supports only `{kind:file,path:...}`.' . PHP_EOL, 1);
		}
	}
	$actions = is_array($plan['actions'] ?? null) ? $plan['actions'] : [];
	if ($mode === 'function') {
		$entry = is_array($plan['target']['entry'] ?? null) ? $plan['target']['entry'] : null;
		if ($entry === null || !in_array(($entry['kind'] ?? null), ['function', 'static_method', 'method'], true)) {
			scpp_fail('Function debug plans require `target.entry.kind = function|static_method|method`.' . PHP_EOL, 1);
		}
		if (!is_string($entry['callable'] ?? null) || trim((string) $entry['callable']) === '') {
			scpp_fail('Function debug plans require `target.entry.callable`.' . PHP_EOL, 1);
		}
		if (!is_string($entry['resolved_file'] ?? null) || trim((string) $entry['resolved_file']) === '') {
			scpp_fail('Function debug plans require `target.entry.resolved_file`.' . PHP_EOL, 1);
		}
		if (!is_int($entry['resolved_line'] ?? null) || (int) $entry['resolved_line'] <= 0) {
			scpp_fail('Function debug plans require `target.entry.resolved_line`.' . PHP_EOL, 1);
		}
		if (!is_string($entry['cpp_callable'] ?? null) || trim((string) $entry['cpp_callable']) === '') {
			scpp_fail('Function debug plans require `target.entry.cpp_callable`.' . PHP_EOL, 1);
		}
		if (!is_array($entry['params'] ?? null)) {
			scpp_fail('Function debug plans require `target.entry.params`.' . PHP_EOL, 1);
		}
		if (!is_string($plan['inputs']['call_args_json'] ?? null)) {
			scpp_fail('Function debug plans require `inputs.call_args_json`.' . PHP_EOL, 1);
		}
		if (($entry['kind'] ?? null) === 'method') {
			if (!is_string($plan['inputs']['call_this_json'] ?? null)) {
				scpp_fail('Instance method debug plans require `inputs.call_this_json`.' . PHP_EOL, 1);
			}
			if (!is_string($entry['this_cpp_type'] ?? null) || trim((string) $entry['this_cpp_type']) === '') {
				scpp_fail('Instance method debug plans require `target.entry.this_cpp_type`.' . PHP_EOL, 1);
			}
		}
	}
	if ($mode === 'exec') {
		$entry = is_array($plan['target']['entry'] ?? null) ? $plan['target']['entry'] : null;
		if ($entry === null || ($entry['kind'] ?? null) !== 'exec') {
			scpp_fail('Exec debug plans require `target.entry.kind = exec`.' . PHP_EOL, 1);
		}
		if (!is_string($entry['expression'] ?? null) || trim((string) $entry['expression']) === '') {
			scpp_fail('Exec debug plans require `target.entry.expression`.' . PHP_EOL, 1);
		}
	}
	foreach ($actions as $index => $action) {
		$action = is_array($action) ? $action : null;
		if ($action === null || !in_array(($action['kind'] ?? null), ['exit', 'break', 'dump_before', 'dump_after'], true)) {
			scpp_fail('Debug action #' . ($index + 1) . ' must be `exit`, `break`, `dump_before`, or `dump_after`.' . PHP_EOL, 1);
		}
		$location = is_array($action['location'] ?? null) ? $action['location'] : null;
		if ($location === null || !is_string($location['file'] ?? null) || !is_int($location['line'] ?? null) || (int) $location['line'] <= 0) {
			scpp_fail('Debug action #' . ($index + 1) . ' requires a valid `location.file` and positive `location.line`.' . PHP_EOL, 1);
		}
		if (in_array(($action['kind'] ?? null), ['dump_before', 'dump_after'], true)) {
			$subject = is_array($action['subject'] ?? null) ? $action['subject'] : null;
			$subjectKind = (string) ($subject['kind'] ?? '');
			$isLocalName = $subject !== null
				&& $subjectKind === 'local_name'
				&& is_string($subject['name'] ?? null)
				&& trim((string) $subject['name']) !== '';
			$isExprText = $subject !== null
				&& $subjectKind === 'expr_text'
				&& is_string($subject['text'] ?? null)
				&& trim((string) $subject['text']) !== '';
			if (!$isLocalName && !$isExprText) {
				scpp_fail('Debug dump action #' . ($index + 1) . ' requires a supported subject.' . PHP_EOL, 1);
			}
		}
	}
	return $plan;
}

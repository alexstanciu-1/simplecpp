<?php
declare(strict_types=1);

/** @return array<string,mixed> */
function load_debug_plan_from_file(string $path): array
{
	$data = read_json_file($path);
	if ($data === null) {
		scpp_fail('Failed to load debug session from `' . $path . '`.' . PHP_EOL, 1);
	}
	return validate_debug_plan($data);
}

function save_debug_plan_to_file(string $path, array $plan): void
{
	write_json_file_atomic($path, $plan);
}

function persist_debug_session_plan_artifact(string $debugRoot, array $plan): void
{
	ensure_directory($debugRoot);
	write_json_file_atomic($debugRoot . '/plan.json', $plan);
}

function persist_debug_session_events_artifact(string $debugRoot, array $events): void
{
	ensure_directory($debugRoot);

	$ndjsonLines = [];
	foreach ($events as $event) {
		$ndjsonLines[] = (string) json_encode($event, JSON_UNESCAPED_SLASHES);
	}
	write_text_file($debugRoot . '/events.ndjson', implode(PHP_EOL, $ndjsonLines) . ($ndjsonLines === [] ? '' : PHP_EOL));

	$aggregate = [
		'version' => 1,
		'session_id' => (string) debug_session_events_session_id($events),
		'status' => (string) debug_find_summary_status($events),
		'events' => array_values($events),
	];
	write_json_file_atomic($debugRoot . '/events.json', $aggregate);
}

/** @return string */
function debug_session_events_session_id(array $events)
{
	foreach ($events as $event) {
		if (is_array($event) && is_string($event['session_id'] ?? null) && (string) $event['session_id'] !== '') {
			return (string) $event['session_id'];
		}
	}
	return '';
}

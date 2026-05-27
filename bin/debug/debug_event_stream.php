<?php
declare(strict_types=1);

/** @return array<string,mixed> */
function build_debug_event(string $type, int $seq, string $sessionId, array $body, ?array $source = null): array
{
	$event = [
		'version' => 1,
		'event' => $type,
		'seq' => $seq,
		'session_id' => $sessionId,
		'timestamp' => gmdate('c'),
		'body' => $body,
	];
	if ($source !== null) {
		$event['source'] = $source;
	}
	return $event;
}

function emit_debug_events(array $plan, array $events): void
{
	$output = is_array($plan['output'] ?? null) ? $plan['output'] : [];
	$format = (string) ($output['format'] ?? 'text');
	if ($format === 'ndjson') {
		foreach ($events as $event) {
			scpp_write(json_encode($event, JSON_UNESCAPED_SLASHES) . PHP_EOL, 'stdout');
		}
		return;
	}
	if ($format === 'json') {
		$aggregate = [
			'version' => 1,
			'session_id' => (string) (($plan['session']['id'] ?? '')),
			'status' => (string) debug_find_summary_status($events),
			'events' => array_values($events),
		];
		scpp_write((string) json_encode($aggregate, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . PHP_EOL, 'stdout');
		return;
	}
	emit_debug_text_output($plan, $events);
}

function emit_debug_text_output(array $plan, array $events): void
{
	$session = is_array($plan['session'] ?? null) ? $plan['session'] : [];
	$sessionId = (string) ($session['id'] ?? '');
	$mode = (string) ($plan['mode'] ?? 'process');
	scpp_write('Debug session ' . $sessionId . ' (' . $mode . ')' . PHP_EOL, 'stdout');
	foreach ($events as $event) {
		$type = (string) ($event['event'] ?? '');
		$body = is_array($event['body'] ?? null) ? $event['body'] : [];
		$source = is_array($event['source'] ?? null) ? $event['source'] : null;
		$sourceLabel = debug_format_text_source($source);
		if ($type === 'dump') {
			$subject = is_array($body['subject'] ?? null) ? $body['subject'] : [];
			$text = is_string($subject['text'] ?? null) ? (string) $subject['text'] : '<expr>';
			$phase = is_string($body['phase'] ?? null) ? (string) $body['phase'] : 'injected';
			$value = is_array($body['value'] ?? null) ? $body['value'] : [];
			$preview = is_string($value['preview'] ?? null) ? (string) $value['preview'] : '<no preview>';
			scpp_write('Dump ' . $phase . ' ' . $sourceLabel . ': ' . $text . ' => ' . $preview . PHP_EOL, 'stdout');
			continue;
		}
		if ($type === 'hit') {
			scpp_write('Hit ' . $sourceLabel . PHP_EOL, 'stdout');
			continue;
		}
		if ($type === 'exit') {
			$reason = is_string($body['reason'] ?? null) ? (string) $body['reason'] : 'debug_exit';
			scpp_write('Exit ' . $sourceLabel . ' reason=' . $reason . PHP_EOL, 'stdout');
			continue;
		}
		if ($type === 'break') {
			$reason = is_string($body['reason'] ?? null) ? (string) $body['reason'] : 'debug_break';
			scpp_write('Break ' . $sourceLabel . ' reason=' . $reason . PHP_EOL, 'stdout');
			continue;
		}
		if ($type === 'call_result') {
			$value = is_array($body['value'] ?? null) ? $body['value'] : [];
			$typeLabel = is_string($value['type'] ?? null) ? (string) $value['type'] : '<unknown>';
			$preview = is_string($value['preview'] ?? null) ? (string) $value['preview'] : '<no preview>';
			scpp_write('Call result type=' . $typeLabel . ' value=' . $preview . PHP_EOL, 'stdout');
			continue;
		}
		if ($type === 'runtime_error') {
			$message = (string) ($body['message'] ?? 'Runtime error.');
			$category = is_string($body['category'] ?? null) ? (string) $body['category'] : '';
			$subcategory = is_string($body['subcategory'] ?? null) ? (string) $body['subcategory'] : '';
			$suffix = $category !== '' ? ' [' . $category . ($subcategory !== '' ? '/' . $subcategory : '') . ']' : '';
			scpp_write('Runtime error ' . $sourceLabel . $suffix . ': ' . $message . PHP_EOL, 'stderr');
			continue;
		}
		if ($type === 'build_error') {
			$message = (string) ($body['message'] ?? 'Debug build failed.');
			$category = is_string($body['category'] ?? null) ? (string) $body['category'] : '';
			$subcategory = is_string($body['subcategory'] ?? null) ? (string) $body['subcategory'] : '';
			$suffix = $category !== '' ? ' [' . $category . ($subcategory !== '' ? '/' . $subcategory : '') . ']' : '';
			scpp_write('Build error' . $suffix . ': ' . $message . PHP_EOL, 'stderr');
			continue;
		}
		if ($type === 'session_summary') {
			$status = (string) ($body['status'] ?? 'completed');
			$exitCode = isset($body['exit_code']) ? (int) $body['exit_code'] : 0;
			scpp_write('Summary: status=' . $status . ' exit_code=' . $exitCode . ' duration_ms=' . (int) ($body['duration_ms'] ?? 0) . PHP_EOL, 'stdout');
			continue;
		}
	}
}

/** @param null|array<string,mixed> $source */
function debug_format_text_source(?array $source): string
{
	if ($source === null) {
		return '<no-source>';
	}
	$file = is_string($source['file'] ?? null) ? basename((string) $source['file']) : '<unknown-file>';
	$line = is_int($source['line'] ?? null) ? (int) $source['line'] : 0;
	if ($line > 0) {
		return $file . ':' . $line;
	}
	return $file;
}

/** @return string */
function debug_find_summary_status(array $events)
{
	foreach (array_reverse($events) as $event) {
		if (($event['event'] ?? null) === 'session_summary') {
			$body = is_array($event['body'] ?? null) ? $event['body'] : [];
			return (string) ($body['status'] ?? 'completed');
		}
	}
	return 'completed';
}

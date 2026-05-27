<?php
declare(strict_types=1);

/** @return array<string,array{source:string,line_map:list<array{debug_line:int,original_line:int,relation:string}>}> */
function build_debug_source_overrides(array $plan): array
{
	$actions = is_array($plan['actions'] ?? null) ? $plan['actions'] : [];
	if ($actions === []) {
		return [];
	}

	/** @var array<string,list<array<string,mixed>>> $byFile */
	$byFile = [];
	foreach ($actions as $index => $action) {
		if (!is_array($action)) {
			continue;
		}
		$location = is_array($action['location'] ?? null) ? $action['location'] : null;
		$file = is_string($location['file'] ?? null) ? normalize_path((string) $location['file']) : '';
		$line = is_int($location['line'] ?? null) ? (int) $location['line'] : 0;
		if ($file === '' || $line <= 0) {
			continue;
		}
		$action['order'] = $index;
		$byFile[$file][] = $action;
	}

	$overrides = [];
	foreach ($byFile as $file => $fileActions) {
		$source = file_get_contents($file);
		if ($source === false) {
			scpp_fail('Failed to read debug source file: ' . $file . PHP_EOL, 1);
		}
		$overrides[$file] = rewrite_debug_source_file($source, $fileActions);
	}

	return $overrides;
}

/** @param array<string,array{source:string,line_map:list<array{debug_line:int,original_line:int,relation:string}>}> $sourceOverrides */
function persist_debug_source_overrides(string $debugSourceRoot, string $projectRoot, array $sourceOverrides): void
{
	reset_debug_source_override_artifacts($debugSourceRoot);
	ensure_directory($debugSourceRoot);
	if ($sourceOverrides === []) {
		write_json_file_atomic($debugSourceRoot . '/manifest.json', [
			'version' => 1,
			'files' => [],
		]);
		return;
	}
	$manifestEntries = [];
	foreach ($sourceOverrides as $sourcePath => $override) {
		$contents = (string) ($override['source'] ?? '');
		$relativePath = ltrim(normalize_config_path(relative_path($projectRoot, $sourcePath)), '/');
		if ($relativePath === '' || str_starts_with($relativePath, '..')) {
			$relativePath = basename($sourcePath);
		}
		$targetPath = normalize_path($debugSourceRoot . '/' . $relativePath);
		ensure_directory(dirname($targetPath));
		write_text_file($targetPath, $contents);
		write_debug_source_line_map_file($targetPath . '.line.tsv', is_array($override['line_map'] ?? null) ? $override['line_map'] : []);
		$manifestEntries[] = [
			'logical_source' => $sourcePath,
			'rewritten_source' => $targetPath,
			'line_map' => $targetPath . '.line.tsv',
			'relative_path' => $relativePath,
		];
	}
	write_json_file_atomic($debugSourceRoot . '/manifest.json', [
		'version' => 1,
		'files' => $manifestEntries,
	]);
}

function reset_debug_source_override_artifacts(string $debugSourceRoot): void
{
	$debugSourceRoot = normalize_path($debugSourceRoot);
	if (!is_dir($debugSourceRoot)) {
		return;
	}
	remove_directory_tree($debugSourceRoot);
}

/** @param array<string,array{source:string,line_map:list<array{debug_line:int,original_line:int,relation:string}>}> $sourceOverrides @return array<string,string> */
function flatten_debug_source_override_map(array $sourceOverrides): array
{
	$flat = [];
	foreach ($sourceOverrides as $sourcePath => $override) {
		$flat[$sourcePath] = (string) ($override['source'] ?? '');
	}
	return $flat;
}

/** @param list<array<string,mixed>> $actions @return array{source:string,line_map:list<array{debug_line:int,original_line:int,relation:string}>} */
function rewrite_debug_source_file(string $source, array $actions): array
{
	$source = str_replace(["\r\n", "\r"], "\n", $source);
	$hasTrailingNewline = $source === '' || str_ends_with($source, "\n");
	$lines = explode("\n", $source);
	if ($hasTrailingNewline) {
		array_pop($lines);
	}

	/** @var array<int,list<array<string,mixed>>> $before */
	$before = [];
	/** @var array<int,list<array<string,mixed>>> $after */
	$after = [];
	foreach ($actions as $action) {
		$line = (int) (($action['location']['line'] ?? 0));
		if ($line <= 0) {
			continue;
		}
		$kind = (string) ($action['kind'] ?? '');
		if ($kind === 'dump_before' || $kind === 'exit' || $kind === 'break') {
			$before[$line][] = $action;
			continue;
		}
		if ($kind === 'dump_after') {
			$after[$line][] = $action;
		}
	}

	$out = [];
	$lineMap = [];
	$debugLine = 1;
	$lineCount = count($lines);
	for ($lineNo = 1; $lineNo <= $lineCount; $lineNo++) {
		foreach (($before[$lineNo] ?? []) as $action) {
			$out[] = build_debug_injected_source_line($action);
			$lineMap[] = ['debug_line' => $debugLine++, 'original_line' => $lineNo, 'relation' => 'above'];
		}
		$out[] = $lines[$lineNo - 1];
		$lineMap[] = ['debug_line' => $debugLine++, 'original_line' => $lineNo, 'relation' => 'exact'];
		foreach (($after[$lineNo] ?? []) as $action) {
			$out[] = build_debug_injected_source_line($action);
			$lineMap[] = ['debug_line' => $debugLine++, 'original_line' => $lineNo, 'relation' => 'below'];
		}
	}

	$rewritten = implode("\n", $out);
	if ($hasTrailingNewline) {
		$rewritten .= "\n";
	}
	return [
		'source' => $rewritten,
		'line_map' => $lineMap,
	];
}

/** @param array<string,mixed> $action */
function build_debug_injected_source_line(array $action): string
{
	$kind = (string) ($action['kind'] ?? '');
	if ($kind === 'exit') {
		return '__scpp_debug_exit();';
	}
	if ($kind === 'break') {
		return '__scpp_debug_break();';
	}
	$subject = is_array($action['subject'] ?? null) ? $action['subject'] : null;
	$text = '';
	if (($subject['kind'] ?? null) === 'local_name') {
		$text = (string) ($subject['name'] ?? '');
	} elseif (($subject['kind'] ?? null) === 'expr_text') {
		$text = (string) ($subject['text'] ?? '');
	}
	if (trim($text) === '') {
		scpp_fail('Debug dump action is missing an injected expression.' . PHP_EOL, 1);
	}
	$text = normalize_debug_injected_expr_text($text);
	$label = "'" . str_replace(['\\', "'"], ['\\\\', "\\'"], $text) . "'";
	$phase = $kind === 'dump_after' ? "'after'" : "'before'";
	return '__scpp_debug_dump(' . $phase . ', ' . $label . ', ' . $text . ');';
}

function normalize_debug_injected_expr_text(string $text): string
{
	$trimmed = trim($text);
	if ($trimmed === '') {
		return $trimmed;
	}
	if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*(?=(->|\[|$))/', $trimmed, $matches) === 1) {
		return '$' . $matches[0] . substr($trimmed, strlen($matches[0]));
	}
	return $trimmed;
}

/** @param list<array{debug_line:int,original_line:int,relation:string}> $lineMap */
function write_debug_source_line_map_file(string $path, array $lineMap): void
{
	$lines = ["debug_line\toriginal_line\trelation"];
	foreach ($lineMap as $entry) {
		$lines[] = (string) $entry['debug_line'] . "\t" . (string) $entry['original_line'] . "\t" . $entry['relation'];
	}
	write_text_file($path, implode(PHP_EOL, $lines) . PHP_EOL);
}

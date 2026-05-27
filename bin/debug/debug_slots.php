<?php
declare(strict_types=1);

/** @return array{slot_count:int,slot_ttl_minutes:int} */
function load_debug_slot_settings(string $projectRoot): array
{
	$configPath = normalize_path($projectRoot . '/' . SCPP_PROJECT_CONFIG);
	$slotCount = 5;
	$slotTtlMinutes = 120;
	if (is_file($configPath)) {
		$config = read_json_file($configPath);
		if (is_array($config)) {
			$debug = is_array($config['debug'] ?? null) ? $config['debug'] : [];
			$configuredSlotCount = isset($debug['slot_count']) ? (int) $debug['slot_count'] : 0;
			$configuredTtl = isset($debug['slot_ttl_minutes']) ? (int) $debug['slot_ttl_minutes'] : 0;
			if ($configuredSlotCount > 0) {
				$slotCount = $configuredSlotCount;
			}
			if ($configuredTtl > 0) {
				$slotTtlMinutes = $configuredTtl;
			}
		}
	}
	return [
		'slot_count' => $slotCount,
		'slot_ttl_minutes' => $slotTtlMinutes,
	];
}

function debug_slots_root(string $projectRoot): string
{
	return normalize_path($projectRoot . '/.prism/debug/slots');
}

function debug_index_path(string $projectRoot): string
{
	return normalize_path($projectRoot . '/.prism/debug/index.json');
}

/** @return array{slot_name:string,slot_root:string,slot_relative_root:string,source_root:string,generated_root:string,cache_root:string,build_root:string,native_cpp_root:string,session_metadata_path:string} */
function allocate_debug_slot_workspace(string $projectRoot, string $sessionId): array
{
	$settings = load_debug_slot_settings($projectRoot);
	reset_legacy_debug_session_workspace($projectRoot);
	$slotsRoot = debug_slots_root($projectRoot);
	ensure_directory($slotsRoot);
	$ttlSeconds = $settings['slot_ttl_minutes'] * 60;
	$now = time();
	$oldestSlot = null;
	$oldestTimestamp = null;

	for ($index = 1; $index <= $settings['slot_count']; $index++) {
		$slotName = sprintf('slot-%02d', $index);
		$slotRoot = normalize_path($slotsRoot . '/' . $slotName);
		$sessionMeta = read_debug_slot_session_metadata($slotRoot . '/session.json');
		if ($sessionMeta !== null) {
			$lastUsedAt = parse_debug_slot_timestamp((string) ($sessionMeta['last_used_at'] ?? ''));
			if ($lastUsedAt !== null && ($now - $lastUsedAt) > $ttlSeconds) {
				reset_debug_slot_root($slotRoot);
				$sessionMeta = null;
			}
		}

		if (debug_slot_is_empty($slotRoot)) {
			$workspace = initialize_debug_slot_workspace($projectRoot, $slotName, $slotRoot, $sessionId);
			write_debug_slot_index($projectRoot, $workspace);
			return $workspace;
		}

		$candidateTime = $sessionMeta !== null
			? (parse_debug_slot_timestamp((string) ($sessionMeta['last_used_at'] ?? '')) ?? @filemtime($slotRoot) ?: 0)
			: (@filemtime($slotRoot) ?: 0);
		if ($oldestTimestamp === null || $candidateTime < $oldestTimestamp) {
			$oldestTimestamp = $candidateTime;
			$oldestSlot = ['name' => $slotName, 'root' => $slotRoot];
		}
	}

	if ($oldestSlot === null) {
		scpp_fail('Failed to allocate a debug slot workspace.' . PHP_EOL, 2);
	}

	reset_debug_slot_root($oldestSlot['root']);
	$workspace = initialize_debug_slot_workspace($projectRoot, $oldestSlot['name'], $oldestSlot['root'], $sessionId);
	write_debug_slot_index($projectRoot, $workspace);
	return $workspace;
}

/** @return array<string,mixed>|null */
function read_debug_slot_session_metadata(string $path): ?array
{
	if (!is_file($path)) {
		return null;
	}
	$data = read_json_file($path);
	return is_array($data) ? $data : null;
}

function parse_debug_slot_timestamp(string $value): ?int
{
	$value = trim($value);
	if ($value === '') {
		return null;
	}
	$timestamp = strtotime($value);
	return $timestamp === false ? null : $timestamp;
}

function debug_slot_is_empty(string $slotRoot): bool
{
	if (!is_dir($slotRoot)) {
		return true;
	}
	$items = scandir($slotRoot);
	if ($items === false) {
		return true;
	}
	foreach ($items as $item) {
		if ($item === '.' || $item === '..') {
			continue;
		}
		return false;
	}
	return true;
}

function reset_debug_slot_root(string $slotRoot): void
{
	$slotRoot = normalize_path($slotRoot);
	if (is_dir($slotRoot)) {
		remove_directory_tree($slotRoot);
	}
}

/** @return array{slot_name:string,slot_root:string,slot_relative_root:string,source_root:string,generated_root:string,cache_root:string,build_root:string,native_cpp_root:string,session_metadata_path:string} */
function initialize_debug_slot_workspace(string $projectRoot, string $slotName, string $slotRoot, string $sessionId): array
{
	ensure_directory($slotRoot);
	$slotRelativeRoot = normalize_config_path(relative_path($projectRoot, $slotRoot));
	$workspace = [
		'slot_name' => $slotName,
		'slot_root' => $slotRoot,
		'slot_relative_root' => $slotRelativeRoot,
		'source_root' => normalize_path($slotRoot . '/source'),
		'generated_root' => normalize_path($slotRoot . '/generated'),
		'cache_root' => normalize_path($slotRoot . '/cache'),
		'build_root' => normalize_path($slotRoot . '/build'),
		'native_cpp_root' => normalize_path($slotRoot . '/native_cpp'),
		'session_metadata_path' => normalize_path($slotRoot . '/session.json'),
	];
	write_json_file_atomic($workspace['session_metadata_path'], [
		'version' => 1,
		'session_id' => $sessionId,
		'slot' => $slotName,
		'created_at' => gmdate('c'),
		'last_used_at' => gmdate('c'),
		'status' => 'active',
	]);
	return $workspace;
}

function touch_debug_slot_workspace(string $projectRoot, array $debugWorkspace, string $status): void
{
	$metadataPath = (string) ($debugWorkspace['session_metadata_path'] ?? '');
	if ($metadataPath !== '' && is_file($metadataPath)) {
		$metadata = read_json_file($metadataPath);
		if (!is_array($metadata)) {
			$metadata = [];
		}
		$metadata['version'] = 1;
		$metadata['session_id'] = (string) ($metadata['session_id'] ?? '');
		$metadata['slot'] = (string) ($debugWorkspace['slot_name'] ?? '');
		$metadata['created_at'] = (string) ($metadata['created_at'] ?? gmdate('c'));
		$metadata['last_used_at'] = gmdate('c');
		$metadata['status'] = $status;
		write_json_file_atomic($metadataPath, $metadata);
	}
	write_debug_slot_index($projectRoot, $debugWorkspace);
}

function write_debug_slot_index(string $projectRoot, array $activeWorkspace = []): void
{
	$settings = load_debug_slot_settings($projectRoot);
	$slotsRoot = debug_slots_root($projectRoot);
	ensure_directory($slotsRoot);
	$slots = [];
	for ($index = 1; $index <= $settings['slot_count']; $index++) {
		$slotName = sprintf('slot-%02d', $index);
		$slotRoot = normalize_path($slotsRoot . '/' . $slotName);
		$metadataPath = normalize_path($slotRoot . '/session.json');
		$metadata = read_debug_slot_session_metadata($metadataPath);
		$slots[] = [
			'name' => $slotName,
			'root' => normalize_config_path(relative_path($projectRoot, $slotRoot)),
			'empty' => debug_slot_is_empty($slotRoot),
			'session' => $metadata,
			'events_path' => is_file($slotRoot . '/events.json')
				? normalize_config_path(relative_path($projectRoot, $slotRoot . '/events.json'))
				: null,
			'plan_path' => is_file($slotRoot . '/plan.json')
				? normalize_config_path(relative_path($projectRoot, $slotRoot . '/plan.json'))
				: null,
			'source_manifest_path' => is_file($slotRoot . '/source/manifest.json')
				? normalize_config_path(relative_path($projectRoot, $slotRoot . '/source/manifest.json'))
				: null,
		];
	}

	write_json_file_atomic(debug_index_path($projectRoot), [
		'version' => 1,
		'slot_count' => $settings['slot_count'],
		'slot_ttl_minutes' => $settings['slot_ttl_minutes'],
		'active_slot' => $activeWorkspace !== [] ? (string) ($activeWorkspace['slot_name'] ?? '') : null,
		'slots_root' => normalize_config_path(relative_path($projectRoot, $slotsRoot)),
		'slots' => $slots,
	]);
}

function reset_legacy_debug_session_workspace(string $projectRoot): void
{
	$legacyRoot = normalize_path($projectRoot . '/.prism/debug/session');
	if (is_dir($legacyRoot)) {
		remove_directory_tree($legacyRoot);
	}
}

<?php
declare(strict_types=1);

// Diagnostic-only source projection. Never publish this output as a compiler build.
$repository = dirname(__DIR__, 3);
require $repository . '/tools/php_portability/src/converter.php';
require $repository . '/tools/php_portability/src/project_cache.php';

function project_storage(string $source): string {
	$tokens = token_get_all($source);
	$result = '';
	$previous = [];
	$names = ['Storage' => 'Conversion_Only_Storage', 'Keyed_Storage' => 'Conversion_Only_Keyed_Storage'];
	foreach ($tokens as $token) {
		$id = is_array($token) ? $token[0] : 0;
		$text = is_array($token) ? $token[1] : $token;
		if ($id === T_DOC_COMMENT && preg_match('~^/\*\*\s*(Storage|Keyed_Storage)<.*>\s*\*/$~sD', $text, $match)) {
			$last = $previous[count($previous) - 1] ?? '';
			$before = $previous[count($previous) - 2] ?? '';
			// Typed properties/parameters and returns already name the placeholder.
			$typed = isset($names[$last]) || (str_starts_with($last, '$') && isset($names[$before]));
			$result .= ($typed ? '' : '/** ' . $names[$match[1]] . ' */') . str_repeat("\n", substr_count($text, "\n"));
			continue;
		}
		$result .= $id === T_STRING ? ($names[$text] ?? $text) : $text;
		if (!in_array($id, [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) { $previous[] = $text; }
	}
	return $result;
}

$fake = ($argv[2] ?? '') === '--fake-storage';
if ($argc < 2 || $argc > 3 || ($argc === 3 && !$fake) || file_exists($argv[1])) {
	fwrite(STDERR, "Usage: php conversion_probe.php NEW_OUTPUT_DIRECTORY [--fake-storage]\n");
	exit(1);
}
$output = $argv[1];
mkdir($output, 0777, true);
file_put_contents($output . '/DO_NOT_BUILD.txt', $fake ? "Fake Storage diagnostic projection. Never build this output.\n" : "Partial conversion diagnostic; no native type checking or build. Use the atomic converter to publish a complete result.\n");
$hashes = [];
foreach (['01_prepare_inputs', '02_tokenize', '03_parse', '04_analyze', '05_llvm', '06_native', 'compile'] as $directory) {
	foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__) . '/' . $directory, FilesystemIterator::SKIP_DOTS)) as $file) {
		if ($file->getExtension() !== 'php') { continue; }
		$relative = substr($file->getPathname(), strlen(dirname(__DIR__)) + 1);
		$source = file_get_contents($file->getPathname());
		$hashes[$relative] = hash('sha256', $source);
		$path = $output . '/source/' . $relative;
		if (!is_dir(dirname($path))) { mkdir(dirname($path), 0777, true); }
		file_put_contents($path, $fake ? project_storage($source) : $source);
	}
}
$map = require $repository . '/tools/php_portability/function_map.php';
$cache = new scpp\portability\Project_Cache(realpath($output . '/source'), null, $map);
$files = $cache->scan();
$index = new scpp\portability\Declaration_Index($files);
$rows = [];
foreach ($files as $relative => $entry) {
	$row = ['path' => $relative, 'original_sha256' => $hashes[$relative], 'projected_sha256' => $entry['hash']];
	try {
		$converter = new scpp\portability\Converter($map);
		$text = $converter->convertTokens($index->expand($relative, $cache->load(...)), $relative);
		$path = $output . '/partial/' . substr($relative, 0, -4) . '.phs';
		if (!is_dir(dirname($path))) { mkdir(dirname($path), 0777, true); }
		file_put_contents($path, "// DIAGNOSTIC ONLY: partial output; DO NOT BUILD.\n" . $text);
		$row['status'] = $fake ? 'accepted_with_fake_storage' : 'converted';
	} catch (Throwable $error) {
		$row['status'] = 'rejected';
		$row['diagnostic'] = $error->getMessage();
	}
	$rows[] = $row;
	echo $relative . ': ' . ($row['diagnostic'] ?? $row['status']) . "\n";
}
file_put_contents($output . '/results.json', json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

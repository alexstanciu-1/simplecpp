<?php
declare(strict_types=1);

use scpp\compiler\{Cpp_Program, entry_target, flavor, Program_Frontend, source_file};

require dirname(__DIR__) . '/boot.php';

/**
 * Emit the PHP example using the same entry target as the catalog.
 * @return array<string,string> Generated files by project path.
 */
function emit_units(string $main, string $lib): array
{
	$sources = ['main' => new source_file('main', $main, flavor::php)];
	if ($lib !== '') $sources['lib'] = new source_file('lib', $lib, flavor::php);
	$program = (new Program_Frontend())->prepare_sources('main', $sources, new entry_target(31));
	$files = Cpp_Program::emit($program)->files;
	foreach ($files as &$unit) {
		// The catalog shows the declarations and definitions inside the default app namespace.
		$unit = preg_replace('/^namespace App \{\n(.*)^\}\n\z/ms', '$1', $unit);
	}
	return $files;
}

function unit_path(string $name, string $extension): string
{
	return 'units/' . hash('sha256', $name) . '.' . $extension;
}

/** @return list<string> Nonblank generated lines without file banners. */
function code_lines(string $text): array
{
	return array_values(array_filter(array_map('trim', explode("\n", $text)),
		static fn(string $line): bool => $line !== '' && !str_starts_with($line, '//')));
}

/**
 * Extract the entry function body, including the harness return.
 * @return list<string>
 */
function entry_lines(string $unit): array
{
	if (!preg_match('/int scpp_entry_body\(\)\n\{\n(.*)\n\}\s*$/s', $unit, $match)) {
		throw new RuntimeException('Missing entry body');
	}
	return code_lines($match[1]);
}

/** @return array<int,string> Unit code shown outside the include column. */
function implementation(string $unit): array
{
	return array_filter(code_lines($unit), static fn(string $line): bool =>
		!str_starts_with($line, '#') && !str_starts_with($line, 'SCPP_REQUIRE_ENTRY_TARGET('));
}

/**
 * Collect the implementation includes using the documented unit-header abbreviation.
 * @return list<string>
 */
function include_lines(string $unit, string $label, bool $entry = false): array
{
	$includes = [];
	foreach (code_lines($unit) as $line) {
		if (!str_starts_with($line, '#include ') || ($entry && $line === '#include <scpp/entry.hpp>')) continue;
		$includes[] = $line === '#include "' . basename(unit_path('lib', 'hpp')) . '"' ? '#include "lib.hpp"' : $line;
	}
	return $includes ? [$label, ...$includes] : [];
}

/**
 * Compare the printed excerpt with fresh compiler output.
 * @param list<string> $shown
 * @param array<int,string> $generated
 */
function check_column(string $label, array $shown, array $generated): void
{
	// Collapse layout whitespace while leaving quoted bytes, escapes and token separation intact.
	$quoted = '~("(?:\\\\.|[^"\\\\])*"|\'(?:\\\\.|[^\'\\\\])*\')(*SKIP)(*F)';
	$normalize = static function (array $lines, bool $omit_attribute) use ($quoted): string {
		$text = implode(' ', $lines);
		if ($omit_attribute) $text = preg_replace($quoted . '|\[\[maybe_unused\]\] ~', '', $text);
		return trim(preg_replace($quoted . '|\s+~', ' ', $text));
	};
	$expected = $normalize($shown, false);
	$actual = $normalize($generated, true);
	if ($actual !== $expected) throw new RuntimeException("$label mismatch\nCatalog: $expected\nEmitted: $actual");
}

/**
 * Compile the source column and reconstruct its implementation and header excerpts.
 * @param array<int,list<string>> $columns
 */
function check_row(array $columns, bool $project): void
{
	[$source, $cpp, $header] = $columns;
	$lib = '';
	if ($source[0] === 'lib') {
		$main = array_search('main', $source, true);
		if ($main === false) throw new RuntimeException('Missing main source label');
		$lib = implode("\n", array_slice($source, 1, $main - 1));
		$source = array_slice($source, $main + 1);
	}
	$setup = [];
	if ($source[0] === 'Given') {
		array_shift($source);
		while ($source && str_starts_with($source[0], '    ')) $setup[] = array_shift($source);
		if (!$setup || !$source) throw new RuntimeException('Expected indented setup followed by an example');
	}
	$has_return = str_starts_with($source[0], 'return ');
	$files = emit_units(implode("\n", [...$setup, ...$source, $has_return ? '' : 'return 8;']), $lib);
	$main_path = unit_path('main', 'cpp');
	$unit = $files[$main_path];
	$body = entry_lines($unit);
	if ($project && !$lib) {
		$body = implementation($unit);
	} else {
		if ($setup) {
			$base = emit_units(implode("\n", [...$setup, 'return 8;']), $lib);
			$prefix = array_slice(entry_lines($base[$main_path]), 0, -1);
			if (array_slice($body, 0, count($prefix)) !== $prefix) throw new RuntimeException('Setup differs in full program');
			$body = array_slice($body, count($prefix));
		}
		if (!$has_return) array_pop($body);
	}
	$includes = include_lines($unit, '.cpp includes', true);
	if ($project && $lib !== '') {
		$library = $files[unit_path('lib', 'cpp')];
		$body = ['main', ...$body, 'lib', ...implementation($library)];
		array_push($includes, ...include_lines($library, 'lib.cpp includes'));
	}
	$headers = array_filter(array_keys($files), static fn(string $file): bool => str_ends_with($file, '.hpp'));
	foreach ($headers as $file) {
		if ($file !== unit_path('lib', 'hpp')) throw new RuntimeException("Unexpected published header $file");
		array_push($includes, 'lib.hpp', ...code_lines($files[$file]));
	}
	if (!$headers) $includes[] = 'No unit header.';
	check_column('Lowered C++ code', $cpp, $body);
	check_column('Lowered C++ header code', $header, $includes);
}

$path = $argv[1] ?? dirname(__DIR__) . '/docs/php_lowering_catalog.md';
$lines = file($path, FILE_IGNORE_NEW_LINES);
$section = '';
$count = 0;
foreach ($lines as $i => $line) {
	if (str_starts_with($line, '## ')) $section = substr($line, 3);
	if ($line !== '```text') continue;
	$border = $lines[$i + 1];
	$widths = explode('+', trim($border, '+'));
	if (count($widths) !== 4) throw new RuntimeException("Malformed grid at line " . ($i + 2));
	$columns = [[], [], []];
	for ($j = $i + 4; isset($lines[$j]) && $lines[$j] !== '```'; ++$j) {
		$line = $lines[$j];
		if ($line === $border) {
			try {
				check_row($columns, $section === 'Functions, namespaces and project units');
			} catch (Throwable $error) {
				throw new RuntimeException("$path:" . ($j + 1) . " ($section): " . $error->getMessage(), 0, $error);
			}
			++$count;
			$columns = [[], [], []];
			continue;
		}
		$offset = 0;
		foreach ($columns as $index => $_) {
			$width = strlen($widths[$index]);
			if ($line[$offset] !== '|' || $line[$offset + $width + 1] !== '|') throw new RuntimeException('Malformed cell at line ' . ($j + 1));
			$cell = rtrim(substr($line, $offset + 2, $width - 2));
			if ($cell !== '') $columns[$index][] = $cell;
			$offset += $width + 1;
		}
	}
	if (!isset($lines[$j])) throw new RuntimeException('Unclosed catalog table');
	if ($columns !== [[], [], []]) throw new RuntimeException('Missing final row border');
}
if (!in_array(true, array_map(static fn(string $line): bool => str_starts_with($line, "All $count examples "), $lines), true) || !$count) {
	throw new RuntimeException("Catalog example count disagrees with $count parsed rows");
}
echo "Verified both C++ columns for $count catalog examples.\n";

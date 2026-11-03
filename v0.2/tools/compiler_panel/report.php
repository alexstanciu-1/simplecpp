<?php

declare(strict_types=1);
require_once __DIR__ . '/../../compiler/boot.php';
require_once __DIR__ . '/../../compiler/host/syntax.php';
require_once __DIR__ . '/../../compiler/host/compilation.php';

use scpp\compiler\{Program_Loader, flavor, Tokenizer, Parser, Symbol_Publication, Syntax_Inspection,
	Compilation_Inspection, Program_Preparation, Native_Target, Cpp_Program, diagnostic_group};

/** Records stage work separately from inspection serialization; failed stages retain prior reports. */
final class Panel_Report
{
	public array $stages /** list<array<string,mixed>> */ = [];
	public function stage(string $name, Closure $work, Closure $inspect): mixed
	{
		$start = hrtime(true);
		$memory = memory_get_usage();
		try {
			$value = $work();
			$row = ['name' => $name, 'status' => 'ok', 'ms' => (hrtime(true) - $start) / 1e6,
				'memory_delta_bytes' => memory_get_usage() - $memory,
				'php_peak_bytes' => memory_get_peak_usage()];
			$row['data'] = $inspect($value);
			$this->stages[] = $row;
			return $value;
		} catch (Throwable $error) {
			$this->stages[] = ['name' => $name, 'status' => 'error', 'ms' => (hrtime(true) - $start) / 1e6,
				'diagnostics' => $error instanceof diagnostic_group
					? array_map(static fn($d): string => $d->getMessage(), $error->diagnostics) : [$error->getMessage()]];
			throw $error;
		}
	}
}

$report = new Panel_Report();
try {
	$request = json_decode(file_get_contents($argv[1]), true, 512, JSON_THROW_ON_ERROR);
	$requests = [];
	foreach ($request['files'] as $input) {
		$requests[$input['name']] = flavor::from($input['flavor']);
	}
	$session = Program_Loader::inventory(dirname($argv[1]) . '/sources', $request['entry'], $requests);
	$publisher = new Symbol_Publication();
	foreach ($session->units() as $name => $unit) {
		$source = $report->stage('Input · ' . $name,
			fn() => Program_Loader::load($unit),
			fn($s): array => ['file' => $name, 'flavor' => $s->flavor->value, 'bytes' => strlen($s->content)]);
		$tokens = $report->stage('Tokenize · ' . $name, fn() => (new Tokenizer($source))->tokenize(),
			static function ($list): array {
				$rows = [];
				foreach ($list->tokens as $i => $token) {
					$rows[] = ['index' => $i, 'kind' => $token->kind->label(), 'offset' => $token->offset,
						'length' => $token->length, 'text' => $list->text_at($i), 'text_hex' => bin2hex($list->text_at($i))];
				}
				return ['count' => count($rows), 'tokens' => $rows];
			});
		$unit->ast = $report->stage('Parse + local collection · ' . $name,
			fn() => (new Parser($tokens, $publisher))->parse(),
			fn($file): array => ['ast' => Syntax_Inspection::file($file), 'collection' => Compilation_Inspection::collection($file)]);
	}
	$files = $session->files();
	$symbols = $report->stage('Global symbol publication', fn() => $publisher->publish($files),
		static function ($symbols): array {
			$rows = [];
			foreach ($symbols->declarations as $head) {
				// Preserve the inspection JSON format; the compiler itself stores only a head reference.
				for ($f = $head; $f !== null; $f = $f->duplicate_element()) {
					$rows[$head->name][] = ['file' => basename($f->source()->path),
						'token_start' => $f->start, 'return_type_name' => $f->return_type_name];
				}
			}
			return $rows;
		});
	$target = $report->stage('Native target facts', fn() => Native_Target::load(__DIR__ . '/../../build/clang/native-layout/facts.json'),
		fn($t): array => ['int_digits' => $t->digits, 'minimum' => (string)$t->minimum, 'maximum' => (string)$t->maximum]);
	$program = $report->stage('Preparation · declarations then bodies',
		fn() => Program_Preparation::prepare($session->entry_key(), $files, $target, $symbols, $session),
		static function ($p): array {
			$result = ['entry' => Compilation_Inspection::program($p->entry), 'functions_by_file' => []];
			foreach ($p->files as $path => $file) {
				foreach ($file->functions() as $function) {
					$result['functions_by_file'][$path][] = ['name' => $function->name,
						'return_type' => $function->return_type_def()->name,
						'body' => Compilation_Inspection::body($function->body())];
				}
			}
			return $result;
		});
	$output = $report->stage('C++ lowering + text emission', fn() => Cpp_Program::emit($program),
		fn($out): array => ['source_units' => $out->source_units, 'files' => $out->files]);
	foreach ($output->files as $path => $text) {
		$destination = dirname($argv[1]) . '/generated/' . $path;
		if (!is_dir(dirname($destination))) { mkdir(dirname($destination), 0700, true); }
		file_put_contents($destination, $text);
	}
	$result = ['ok' => true, 'stages' => $report->stages, 'units' => array_keys($output->files)];
} catch (Throwable $error) {
	$result = ['ok' => false, 'stages' => $report->stages, 'error' => $error->getMessage()];
}
echo json_encode($result, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);

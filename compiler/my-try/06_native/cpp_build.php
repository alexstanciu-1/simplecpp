<?php
namespace scpp\compiler;

/** Disk consumer only. No syntax, preparation scheduling or emitter callbacks. */
final class Native_CPP_Build
{
	private native_cpp_config $config;
	private string $root;
	private string $tool_key;
	private array $flags /** vector<string> */ = [];
	private array $libraries /** vector<string> */ = [];
	private native_cpp_result $report;
	private bool $invoked = false;

	public function __construct(native_cpp_config $config)
	{
		$this->config = $config;
		$this->report = new native_cpp_result();
		$this->root = fs_output_root($config->cache_directory);
		if (!string_byte_starts_with($config->compiler, '/')) { throw new \RuntimeException('Native compiler must be absolute'); }
		if (($config->optimization !== 0) && ($config->optimization !== 2)) { throw new \RuntimeException('Native optimization must be 0 or 2'); }
	}

	public function build(string $published): native_cpp_result
	{
		if ($this->invoked) { throw new \LogicException('Native builder owns one attempt'); }
		$this->invoked = true;
		$source_root = fs_require_realpath($published);
		if ($source_root === $this->root) { throw new \RuntimeException('Native cache must differ from generated source root'); }
		$source_lock = new \scpp\Lock_Reservation();
		$cache_lock = new \scpp\Lock_Reservation();
		try {
			fs_output_read($source_root, '.scpp-output.lock');
			if (!$source_lock->acquire($source_root . '/.scpp-output.lock', true)) { throw new \RuntimeException('Source publication is busy'); }
			fs_output_read($this->root, '.scpp-native.lock');
			if (!$cache_lock->acquire($this->root . '/.scpp-native.lock', false)) { throw new \RuntimeException('Native cache is busy'); }
			$owner = fs_output_read($this->root, '.scpp-native.owner');
			if ($owner !== null) {
				if ($owner !== "scpp-native-cache-v1\n") { throw new \RuntimeException('Unknown native cache owner'); }
			}
			else {
				if ((fs_output_read($this->root, '.scpp-native.status') !== null)
					|| (fs_output_read($this->root, 'runtime-pch.hpp') !== null)
					|| fs_is_dir($this->root . '/actions') || fs_is_dir($this->root . '/artifacts')
					|| fs_is_dir($this->root . '/link-inputs')) { throw new \RuntimeException('Unowned native cache contents'); }
			}
			fs_output_write($this->root, '.scpp-native.owner', "scpp-native-cache-v1\n");
			fs_output_write($this->root, '.scpp-native.status', "pending\n");
			$manifest = Output_Publication::require_ready($source_root);
			$document = json_read($manifest);
			if (!$document->has('runtime_build')) { throw new \RuntimeException('Publication lacks runtime build requirements; republish with the current compiler'); }
			$build_inputs = self::runtime_inputs($document->member('runtime_build'));
			$this->libraries = $build_inputs->libraries;
			$units /** vector<string> */ = [];
			$files = $document->member('files');
			for ($index = 0; $index < $files->size(); $index++) {
				$row = $files->at($index);
				$path = $row->member('path')->text();
				if (fs_output_digest($source_root, $path) !== $row->member('sha256')->text()) { throw new \RuntimeException('Published artifact changed: ' . $path); }
				if ($row->member('kind')->text() === 'translation_unit') { $units[] = $source_root . '/' . $path; }
			}
			if (q_count($units) === 0) { throw new \RuntimeException('No published translation units'); }
			$runtime = fs_require_realpath($this->config->runtime_include);
			$optimization = $this->config->optimization === 2 ? '-O2' : '-O0';
			$flags /** vector<string> */ = ['--driver-mode=g++', '-std=c++23', $optimization,
				'-DSCPP_LANGUAGE_TARGET_PHP=1', '-pthread', '-I', $runtime];
			foreach ($build_inputs->definitions as $definition) { $flags[] = '-D' . $definition; }
			$this->flags = $flags;
			$version_arguments /** vector<string> */ = ['--version'];
			$version = $this->invoke($version_arguments);
			self::require_success($version);
			$this->tool_key = "scpp-native-actions-v1\n" . self::file_digest($this->config->compiler) . self::file_digest('/usr/bin/ld')
				. self::file_digest('/usr/bin/env') . $version->stdout;
			$header = '#include "scpp/int_t.hpp"' . "\n" . '#include "scpp/nullable.hpp"' . "\n"
				. '#include "scpp/cast.hpp"' . "\n" . '#include "lang/php/operators/conditional/condition_truthiness.hpp"' . "\n"
				. '#include "scpp/generated/operators.hpp"' . "\n";
			fs_output_write($this->root, 'runtime-pch.hpp', $header);
			$pch_args /** vector<string> */ = [];
			if ($this->config->pch) {
				$pch_command /** vector<string> */ = $this->flags;
				$pch_command[] = '-x'; $pch_command[] = 'c++-header'; $pch_command[] = $this->root . '/runtime-pch.hpp';
				$pch = $this->compile_action($pch_command, 'pch', false);
				$validation /** vector<string> */ = $this->flags;
				$validation[] = '-include-pch'; $validation[] = $pch->path;
				$validation[] = '-x'; $validation[] = 'c++'; $validation[] = '-fsyntax-only'; $validation[] = '/dev/null';
				$check = $this->invoke($validation);
				if ($check->exit_code !== 0) { $pch = $this->compile_action($pch_command, 'pch', true); }
				if (!$pch->reused) { $this->report->pch_built++; }
				$pch_args = ['-include-pch', $pch->path];
			}
			$objects /** vector<string> */ = [];
			foreach ($build_inputs->sources as $provider) {
				$provider_path = fs_require_realpath($runtime . '/' . $provider);
				if (!string_byte_starts_with($provider_path, $runtime . '/')) { throw new \RuntimeException('Runtime provider source escapes its root'); }
				$runtime_command /** vector<string> */ = $this->flags;
				$runtime_command[] = '-c'; $runtime_command[] = $provider_path;
				$runtime_object = $this->compile_action($runtime_command, 'o', false);
				$this->record_object($runtime_object);
				$objects[] = $runtime_object->path;
			}
			foreach ($units as $unit) {
				$command /** vector<string> */ = $this->flags;
				$command[] = '-I'; $command[] = $source_root;
				foreach ($pch_args as $argument) { $command[] = $argument; }
				$command[] = '-c'; $command[] = $unit;
				$artifact = $this->compile_action($command, 'o', false);
				$this->record_object($artifact);
				$objects[] = $artifact->path;
			}
			$link = $this->link($objects);
			$this->report->executable = $link->path;
			if (!$link->reused) { $this->report->linked++; }
			$ready = '{"format":"scpp-native-ready-v1","source":' . json_quote(content_sha256($manifest))
				. ',"executable":' . json_quote($link->path) . ',"sha256":' . json_quote($link->digest) . "}\n";
			fs_output_write($this->root, '.scpp-native.status', $ready);
			return $this->report;
		}
		finally { $cache_lock->release(); $source_lock->release(); }
	}

	private function record_object(native_cpp_artifact $artifact): void
	{
		if ($artifact->reused) { $this->report->reused++; }
		else { $this->report->compiled++; }
	}

	/** Consume the authenticated publication, never reopen module catalogs during a build. */
	private static function runtime_inputs(\scpp\Json_View $view): runtime_cpp_build_spec
	{
		if (($view->kind() !== 'object') || ($view->size() !== 3)) { throw new \RuntimeException('Invalid runtime build publication'); }
		$result = new runtime_cpp_build_spec();
		foreach (['sources', 'libraries', 'definitions'] as $name) {
			$items = $view->member($name);
			if ($items->kind() !== 'array') { throw new \RuntimeException('Runtime build entries require arrays'); }
			$values /** vector<string> */ = [];
			for ($index = 0; $index < $items->size(); $index++) { $values[] = $items->at($index)->text(); }
			if ($name === 'sources') { $result->sources = $values; }
			elseif ($name === 'libraries') { $result->libraries = $values; }
			else { $result->definitions = $values; }
		}
		Runtime_Build_Contract::validate($result);
		return $result;
	}

	/** Scan fresh dependencies (including system headers) so newly shadowing headers cannot be missed. */
	private function compile_action(array $command /** vector<string> */, string $suffix, bool $force): native_cpp_artifact
	{
		$scan /** vector<string> */ = [];
		$pch_inputs /** vector<string> */ = [];
		$skip = false;
		foreach ($command as $argument) {
			if ($skip) { $pch_inputs[] = $argument; $skip = false; continue; }
			if ($argument === '-include-pch') {
				$scan[] = '-include'; $scan[] = $this->root . '/runtime-pch.hpp'; $skip = true; continue;
			}
			if ($argument !== '-c') { $scan[] = $argument; }
		}
		$scan[] = '-M'; $scan[] = '-MT'; $scan[] = 'output';
		$observed = $this->invoke($scan);
		self::require_success($observed);
		$paths /** vector<string> */ = Native_Depfiles::paths($observed->stdout, $this->root);
		foreach ($pch_inputs as $path) { $paths[] = $path; }
		$key = content_sha256($this->tool_key . self::arguments_key($command) . self::inputs_key($paths));
		return $this->action($command, $key, $suffix, $force, false);
	}

	/** Link state records actual linker dependencies in addition to exact ordered object membership. */
	private function link(array $objects /** vector<string> */): native_cpp_artifact
	{
		$command /** vector<string> */ = $this->flags;
		$command[] = '-fuse-ld=/usr/bin/ld';
		foreach ($objects as $object_path) { $command[] = $object_path; }
		foreach ($this->libraries as $library) { $command[] = '-l' . $library; }
		$base = content_sha256($this->tool_key . self::arguments_key($command) . self::inputs_key($objects));
		$dependencies_path = 'link-inputs/' . $base . '.d';
		$previous = fs_output_read($this->root, $dependencies_path);
		$dependencies /** vector<string> */ = [];
		if ($previous !== null) { $dependencies = Native_Depfiles::paths($previous, $this->root); }
		$key = content_sha256($base . self::inputs_key($dependencies));
		if ($previous !== null) {
			$cached = $this->cached($key, 'bin');
			if ($cached !== null) { return $cached; }
		}
		$dep_stage = new \scpp\Output_Stage($this->root);
		try {
			$command[] = '-Xlinker'; $command[] = '--dependency-file';
			$command[] = '-Xlinker'; $command[] = $dep_stage->path();
			// The first result is provisional until linker inputs are known; never expose it as ready.
			$artifact = $this->action($command, $key, 'bin', true, true);
			$dep_text = fs_read_text($dep_stage->path());
			$dependencies = Native_Depfiles::paths($dep_text, $this->root);
			$final_key = content_sha256($base . self::inputs_key($dependencies));
			// Record a small alias to the completed artifact, without copying the executable.
			$this->save_ready($final_key, $artifact->path, $artifact->digest);
			fs_output_write($this->root, $dependencies_path, $dep_text);
			return $artifact;
		} finally { $dep_stage->close(); }
	}

	private function cached(string $key, string $suffix): ?native_cpp_artifact
	{
		$state = fs_output_read($this->root, 'actions/' . $key . '.json');
		if ($state === null) { return null; }
		$record = json_read($state);
		if ($record->member('format')->text() !== 'scpp-native-action-v1') { throw new \RuntimeException('Unknown native action metadata'); }
		if ($record->member('state')->text() === 'pending') { return null; }
		if ($record->member('state')->text() !== 'ready') { throw new \RuntimeException('Unknown native action state'); }
		$path = $record->member('path')->text();
		if (!string_byte_starts_with($path, $this->root . '/artifacts/') || !string_byte_ends_with($path, '.' . $suffix)) { throw new \RuntimeException('Invalid cached artifact path'); }
		$digest = $record->member('sha256')->text();
		if (string_byte_len($digest) !== 64) { throw new \RuntimeException('Invalid cached digest'); }
		$relative = string_byte_slice($path, string_byte_len($this->root) + 1, string_byte_len($path));
		if (fs_output_digest($this->root, $relative) !== $digest) { return null; }
		$mode = $suffix === 'bin' ? 448 : 384; // Owner-only 0700 / 0600.
		if (fs_output_permissions($this->root, $relative) !== $mode) { return null; }
		return new native_cpp_artifact($path, $digest, true);
	}

	private function action(array $command /** vector<string> */, string $key, string $suffix, bool $force, bool $executable): native_cpp_artifact
	{
		if (!$force) {
			$cached = $this->cached($key, $suffix);
			if ($cached !== null) { return $cached; }
		}
		$relative = 'artifacts/' . $key . '.' . $suffix;
		$expected = fs_output_digest($this->root, $relative);
		$record = fs_output_read($this->root, 'actions/' . $key . '.json');
		if ($record === null) {
			if ($expected !== '') { throw new \RuntimeException('Unowned native output collision'); }
		}
		else {
			$previous = json_read($record);
			if ($previous->member('format')->text() !== 'scpp-native-action-v1') { throw new \RuntimeException('Unknown native action owner'); }
		}
		fs_output_write($this->root, 'actions/' . $key . '.json', '{"format":"scpp-native-action-v1","state":"pending"}');
		$stage = new \scpp\Output_Stage($this->root);
		try {
			$execution_command /** vector<string> */ = $command;
			$execution_command[] = '-o'; $execution_command[] = $stage->path();
			$execution = $this->invoke($execution_command);
			self::require_success($execution);
			$stage->promote($relative, $expected, $executable);
			$digest = fs_output_digest($this->root, $relative);
			$this->save_ready($key, $this->root . '/' . $relative, $digest);
			return new native_cpp_artifact($this->root . '/' . $relative, $digest, false);
		} finally { $stage->close(); }
	}

	private function save_ready(string $key, string $path, string $digest): void
	{
		fs_output_write($this->root, 'actions/' . $key . '.json', '{"format":"scpp-native-action-v1","state":"ready","path":'
			. json_quote($path) . ',"sha256":' . json_quote($digest) . '}');
	}

	private function invoke(array $arguments /** vector<string> */): native_process_result
	{
		$args /** vector<string> */ = ['-i', 'PATH=/usr/bin:/bin', 'LC_ALL=C', 'TZ=UTC', 'SOURCE_DATE_EPOCH=0', $this->config->compiler];
		foreach ($arguments as $argument) { $args[] = $argument; }
		$handle = process_spawn('/usr/bin/env', $args, '', 120000, $this->root);
		try {
			while (!process_poll($handle)) { dt_sleep_ms(1); }
			$output = process_output($handle);
			$result = new native_process_result();
			$result->exit_code = $output->exit_code;
			$result->stdout = $output->stdout_text;
			$result->stderr = $output->stderr_text;
			if ($output->timed_out || $output->stopped || ($output->signal !== 0)) { throw new \RuntimeException('Native process interrupted: ' . $result->stderr); }
			return $result;
		} finally { process_close($handle); }
	}

	private static function require_success(native_process_result $result): void
	{
		if ($result->exit_code !== 0) { throw new \RuntimeException('Native compilation failed: ' . $result->stderr); }
	}
	private static function arguments_key(array $arguments /** vector<string> */): string
	{
		$text = '';
		foreach ($arguments as $argument) { $text .= json_quote($argument) . "\n"; }
		return $text;
	}
	private static function file_digest(string $path): string
	{
		$resolved = fs_require_realpath($path);
		$digest = fs_file_digest($resolved);
		if ($digest === '') { throw new \RuntimeException('Missing native dependency: ' . $path); }
		return $digest;
	}
	private static function inputs_key(array $paths /** vector<string> */): string
	{
		$text = '';
		foreach ($paths as $path) { $text .= json_quote($path) . ':' . self::file_digest($path) . "\n"; }
		return $text;
	}
}

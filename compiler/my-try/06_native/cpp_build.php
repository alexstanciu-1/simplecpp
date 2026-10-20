<?php
namespace scpp\compiler;

/** Disk consumer only. No syntax, preparation scheduling or emitter callbacks. */
final class Native_CPP_Build
{
	private native_cpp_config $config;
	private string $root;
	private string $tool_key;
	private string $linker_key;
	private array $flags /** vector<string> */ = [];
	private array $libraries /** vector<string> */ = [];
	private native_cpp_result $report;
	private bool $invoked = false;
	private ?Native_Dependency_Inputs $dependency_inputs = null;

	public function __construct(native_cpp_config $config)
	{
		$this->config = $config;
		$this->report = new native_cpp_result();
		$this->root = fs_output_root($config->cache_directory);
		if (!string_byte_starts_with($config->compiler, '/')) { throw new \RuntimeException('Native compiler must be absolute'); }
		$policy = new cpp_lowering_policy($config->optimization);
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
					|| fs_is_dir($this->root . '/link-inputs') || fs_is_dir($this->root . '/dependencies')) { throw new \RuntimeException('Unowned native cache contents'); }
			}
			fs_output_write($this->root, '.scpp-native.owner', "scpp-native-cache-v1\n");
			fs_output_write($this->root, '.scpp-native.status', "pending\n");
			$manifest = Output_Publication::require_ready($source_root);
			$document = json_read($manifest);
			if (!$document->has('application_templates')) {
				throw new \RuntimeException('Publication lacks application template policy; republish with the current compiler');
			}
			$policy = $document->member('application_templates')->text();
			if (($policy !== 'allowed') && ($policy !== 'forbidden')) {
				throw new \RuntimeException('Unknown application template policy');
			}
			$template_free = $policy === 'forbidden';
			if ($template_free && $this->config->pch) {
				throw new \RuntimeException('prelower_templates: runtime PCH is incompatible with template-free application input; select --no-pch');
			}
			Native_CPP_Publication::require_policy($document, $this->config->optimization);
			if (!$document->has('runtime_build')) { throw new \RuntimeException('Publication lacks runtime build requirements; republish with the current compiler'); }
			$build_inputs = Native_CPP_Publication::runtime_inputs($document->member('runtime_build'));
			$this->libraries = $build_inputs->libraries;
			$units /** vector<string> */ = [];
			$checked_units /** hash<bool> */ = [];
			$layout_probes /** vector<string> */ = [];
			$files = $document->member('files');
			for ($index = 0; $index < $files->size(); $index++) {
				$row = $files->at($index);
				$path = $row->member('path')->text();
				if (fs_output_digest($source_root, $path) !== $row->member('sha256')->text()) { throw new \RuntimeException('Published artifact changed: ' . $path); }
				$kind = $row->member('kind')->text();
				if ($kind === 'translation_unit')
				{
					$units[] = $source_root . '/' . $path;
					if ($template_free && !string_byte_starts_with($path, 'specializations/')) {
						if (($path !== 'entry.cpp') && !string_byte_starts_with($path, 'units/')) {
							throw new \RuntimeException('prelower_templates: unclassified application translation unit: ' . $path);
						}
						$checked_units[$source_root . '/' . $path] = true;
					}
				}
				elseif ($kind === 'layout_probe') { $layout_probes[] = $source_root . '/' . $path; }
				elseif ($kind !== 'header') { throw new \RuntimeException('Unknown native artifact kind: ' . $kind); }
			}
			if (q_count($units) === 0) { throw new \RuntimeException('No published translation units'); }
			$entry_path = $source_root . '/entry.cpp';
			if ($template_free && !isset($checked_units[$entry_path])) {
				throw new \RuntimeException('prelower_templates: missing application entry translation unit');
			}
			$runtime = fs_require_realpath($this->config->runtime_include);
			$this->flags = Native_CPP_Commands::flags($this->config, $runtime, $build_inputs->definitions);
			$version_arguments /** vector<string> */ = ['--version'];
			$version = $this->invoke($version_arguments);
			self::require_success($version);
			$this->tool_key = "scpp-native-actions-v2\n" . self::file_digest($this->config->compiler)
				. self::file_digest('/usr/bin/env') . $version->stdout;
			// Linker replacement must not invalidate compilation or dependency discovery.
			$this->linker_key = fs_require_realpath($this->config->linker) . ':' . self::file_digest($this->config->linker);
			$layout_headers /** vector<string> */ = [];
			foreach ($layout_probes as $probe) {
				$layout = $this->layout($probe, $source_root, $layout_headers);
				$layout_headers[] = $layout->path;
				if ($layout->reused) { $this->report->layouts_reused++; }
				else { $this->report->layouts_built++; }
			}
			fs_output_write($this->root, 'runtime-pch.hpp', Native_CPP_Commands::pch_header());
			if ($this->config->runtime_snapshot) {
				$inputs = new Native_Dependency_Inputs($this->root, $runtime, $source_root, $files);
				if ($inputs->complete) {
					$search /** vector<string> */ = $this->flags;
					foreach (['-E', '-v', '-x', 'c++', '/dev/null'] as $argument) { $search[] = $argument; }
					$search_result = $this->invoke($search);
					self::require_success($search_result);
					$inputs->observe_search($search_result->stderr);
					$this->dependency_inputs = $inputs;
				}
			}
			$pch_path = '';
			if ($this->config->pch) {
				$pch_command = Native_CPP_Commands::pch($this->flags, $this->root . '/runtime-pch.hpp');
				$pch = $this->compile_action($pch_command, 'pch', false);
				$validation /** vector<string> */ = $this->flags;
				$validation[] = '-include-pch'; $validation[] = $pch->path;
				$validation[] = '-x'; $validation[] = 'c++'; $validation[] = '-fsyntax-only'; $validation[] = '/dev/null';
				$check = $this->invoke($validation);
				if ($check->exit_code !== 0) { $pch = $this->compile_action($pch_command, 'pch', true); }
				if (!$pch->reused) { $this->report->pch_built++; }
				$pch_path = $pch->path;
			}
			$objects /** vector<string> */ = [];
			foreach ($build_inputs->sources as $provider) {
				$provider_path = fs_require_realpath($runtime . '/' . $provider);
				if (!string_byte_starts_with($provider_path, $runtime . '/')) { throw new \RuntimeException('Runtime provider source escapes its root'); }
				$runtime_command = Native_CPP_Commands::provider($this->flags, $provider_path);
				$runtime_object = $this->compile_action($runtime_command, 'o', false);
				$this->record_object($runtime_object);
				$objects[] = $runtime_object->path;
			}
			foreach ($units as $unit) {
				$command = Native_CPP_Commands::implementation($this->flags, $source_root, $pch_path, $unit);
				foreach ($layout_headers as $layout_header) { $command[] = '-include'; $command[] = $layout_header; }
				$artifact = $this->compile_action($command, 'o', false, isset($checked_units[$unit]));
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

	/** Reuse described dependency discovery, otherwise scan through Clang. */
	private function compile_action(array $command /** vector<string> */, string $suffix, bool $force, bool $template_free = false): native_cpp_artifact
	{
		$inputs /** nullable<Native_Dependency_Inputs> */ = $this->dependency_inputs;
		// Strict application validation always observes fresh transitive dependencies.
		if ($template_free) {
			$inputs = null;
		}
		$discovery = '';
		if ($inputs !== null) {
			$source = $command[q_count($command) - 1];
			$discovery = $inputs->discovery_key($this->tool_key . self::arguments_key($command), $source);
			$previous = $inputs->previous($discovery);
			if ($previous !== '') {
				$key = content_sha256($this->tool_key . self::arguments_key($command) . $inputs->inputs_key($previous));
				return $this->action($command, $key, $suffix, $force, false);
			}
		}
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
		$input_key = '';
		if ($inputs !== null) { $input_key = $inputs->inputs_key($inputs->remember($discovery, $paths)); }
		else { $input_key = self::inputs_key($paths); }
		$key = content_sha256($this->tool_key . self::arguments_key($command) . $input_key);
		if ($template_free) {
			$key = content_sha256("scpp-template-free-tokens-v1\n" . $key);
		}
		return $this->action($command, $key, $suffix, $force, false, $template_free);
	}

	/** Ask Clang for active tokens, including macro expansion and transitive provider headers. */
	private function require_template_free(array $command /** vector<string> */): void
	{
		$tokens /** vector<string> */ = [];
		foreach ($command as $argument) {
			if ($argument !== '-c') {
				$tokens[] = $argument;
			}
		}
		foreach (['-fsyntax-only', '-Xclang', '-dump-tokens'] as $argument) {
			$tokens[] = $argument;
		}
		$result = $this->invoke($tokens);
		self::require_success($result);
		$text = "\n" . $result->stderr;
		if (q_strpos($text, "\ntemplate 'template'\t") !== false) {
			throw new \RuntimeException('prelower_templates: template token in application input (including provider headers): '
				. $command[q_count($command) - 1]);
		}
		if (q_strpos($text, "\neof ''\t") === false) {
			throw new \RuntimeException('prelower_templates: Clang did not provide a complete application token stream');
		}
	}

	/** Link state records actual linker dependencies in addition to exact ordered object membership. */
	private function link(array $objects /** vector<string> */): native_cpp_artifact
	{
		$command = Native_CPP_Commands::link($this->config, $this->flags, $objects, $this->libraries);
		$base = content_sha256($this->tool_key . $this->linker_key . self::arguments_key($command) . self::inputs_key($objects));
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

	private function action(array $command /** vector<string> */, string $key, string $suffix, bool $force, bool $executable, bool $template_free = false): native_cpp_artifact
	{
		if (!$force) {
			$cached = $this->cached($key, $suffix);
			if ($cached !== null) { return $cached; }
		}
		$relative = 'artifacts/' . $key . '.' . $suffix;
		$expected = $this->begin_action($key, $suffix);
		if ($template_free) {
			$this->require_template_free($command);
		}
		$stage = new \scpp\Output_Stage($this->root);
		try {
			$execution_command /** vector<string> */ = $command;
			$execution_command[] = '-o'; $execution_command[] = $stage->path();
			$execution = $this->invoke($execution_command);
			self::require_success($execution);
			$this->report->diagnostics .= $execution->stderr;
			$stage->promote($relative, $expected, $executable);
			$digest = fs_output_digest($this->root, $relative);
			$this->save_ready($key, $this->root . '/' . $relative, $digest);
			return new native_cpp_artifact($this->root . '/' . $relative, $digest, false);
		} finally { $stage->close(); }
	}

	/** Shared ownership and pending-state transition for compiled and generated cache artifacts. */
	private function begin_action(string $key, string $suffix): string
	{
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
		return $expected;
	}

	/** Resolve layouts inside the native owner; never mutate the published source bundle. */
	private function layout(string $probe, string $source_root, array $prerequisites /** vector<string> */): native_cpp_artifact
	{
		$command /** vector<string> */ = $this->flags;
		$command[] = '-I'; $command[] = $source_root;
		foreach ($prerequisites as $header) { $command[] = '-include'; $command[] = $header; }
		$command[] = '-c'; $command[] = $probe;
		$object_file = $this->compile_action($command, 'o', false);
		$objects /** vector<string> */ = [$object_file->path];
		$executable = $this->link($objects);
		$key = content_sha256("scpp-native-layout-v1\n" . $this->tool_key
			. self::arguments_key($this->flags) . $executable->digest);
		$cached = $this->cached($key, 'hpp');
		if ($cached !== null) { return $cached; }
		$this->begin_action($key, 'hpp');
		$arguments /** vector<string> */ = [];
		$result = $this->invoke_program($executable->path, $arguments);
		self::require_success($result);
		if (!string_byte_starts_with($result->stdout, "// scpp-native-layout-v1\n")) {
			throw new \RuntimeException('Invalid native layout probe output');
		}
		$relative = 'artifacts/' . $key . '.hpp';
		fs_output_write($this->root, $relative, $result->stdout);
		$digest = fs_output_digest($this->root, $relative);
		$this->save_ready($key, $this->root . '/' . $relative, $digest);
		return new native_cpp_artifact($this->root . '/' . $relative, $digest, false);
	}

	private function save_ready(string $key, string $path, string $digest): void
	{
		fs_output_write($this->root, 'actions/' . $key . '.json', '{"format":"scpp-native-action-v1","state":"ready","path":'
			. json_quote($path) . ',"sha256":' . json_quote($digest) . '}');
	}

	private function invoke(array $arguments /** vector<string> */): native_process_result
	{
		return $this->invoke_program($this->config->compiler, $arguments);
	}

	private function invoke_program(string $executable, array $arguments /** vector<string> */): native_process_result
	{
		$args /** vector<string> */ = Native_CPP_Commands::environment($executable);
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

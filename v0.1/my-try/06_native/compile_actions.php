<?php
namespace scpp\compiler;

/** Native_CPP_Build's cooperative process scheduler. Cache decisions stay on the caller. */
trait Native_Compile_Actions
{
	private function compile_action(array $command /** vector<string> */, string $suffix, bool $force): native_cpp_artifact
	{
		$requests /** Storage<native_compile_request> */ = new Storage();
		$request = new native_compile_request($command, $suffix, $force);
		$requests->append($request);
		$this->compile_actions($requests, 1);
		return $request->artifact;
	}

	/** A slot follows one request through all phases; finished slots refill immediately. */
	private function compile_actions(Storage $requests /** Storage<native_compile_request> */, int $jobs): void
	{
		$leaders /** Keyed_Storage<native_compile_request> */ = new Keyed_Storage();
		$active /** hash<native_compile_request,int> */ = [];
		$next = 0;
		$slot = 0;
		$operation = 'admit';
		try {
			while (($next < $requests->count()) || (q_count($active) !== 0)) {
				while (($next < $requests->count()) && (q_count($active) < $jobs)) {
					$slot = $next;
					$operation = 'admit';
					$request = $requests[$next];
					$this->start_discovery($request, $leaders);
					if ($request->process !== null) { $active[$next] = $request; }
					$next++;
				}
				$progress = false;
				$finished /** vector<int> */ = [];
				foreach ($active as $index => $request) {
					$slot = $index;
					$operation = 'poll';
					$handle /** \scpp\Process_Handle */ = $request->process;
					if (!process_poll($handle)) { continue; }
					$result = new native_process_result();
					$operation = 'read result';
					try { $result = self::completed_process($handle); }
					finally { process_close($handle); $request->process = null; }
					self::require_success($result);
					$operation = 'advance';
					if ($request->phase === native_compile_phase::discovery) { $operation = 'read dependencies'; }
					elseif ($request->phase === native_compile_phase::validation) { $operation = 'validate tokens'; }
					elseif ($request->phase === native_compile_phase::compilation) { $operation = 'publish object'; }
					$this->advance_compile($request, $result, $leaders);
					if ($request->process === null) { $finished[] = $index; }
					$progress = true;
				}
				foreach ($finished as $index) { unset($active[$index]); }
				if (!$progress && (q_count($active) !== 0)) { dt_sleep_ms(1); }
			}
			$operation = 'collect';
			$slot = 0;
			foreach ($requests as $request) {
				if ($request->leader !== null) {
					$leader /** native_compile_request */ = $request->leader;
					$artifact /** native_cpp_artifact */ = $leader->artifact;
					$request->artifact = new native_cpp_artifact($artifact->path, $artifact->digest, true);
				}
				$this->report->diagnostics .= $request->diagnostics;
				$slot++;
			}
		}
		catch (\Throwable $error) {
			throw new \RuntimeException('Native compile request ' . $slot . ' (' . $operation . '): ' . $error->getMessage());
		}
		finally {
			// Close joins/kills children before deleting their staged output or releasing build locks.
			foreach ($requests as $request) {
				if ($request->process !== null) {
					$handle /** \scpp\Process_Handle */ = $request->process;
					process_close($handle);
					$request->process = null;
				}
			}
			foreach ($requests as $request) {
				if ($request->stage !== null) {
					$stage /** \scpp\Output_Stage */ = $request->stage;
					$stage->close();
					$request->stage = null;
				}
			}
		}
	}

	private function start_discovery(native_compile_request $request, Keyed_Storage $leaders /** Keyed_Storage<native_compile_request> */): void
	{
		$command /** vector<string> */ = $request->command;
		$inputs /** nullable<Native_Dependency_Inputs> */ = $this->dependency_inputs;
		if ($request->template_free) { $inputs = null; }
		$request->inputs = $inputs;
		if ($inputs !== null) {
			$source = $command[q_count($command) - 1];
			$request->discovery = $inputs->discovery_key($this->tool_key . self::arguments_key($command), $source);
			$previous = $inputs->previous($request->discovery);
			if ($previous !== '') {
				$request->key = content_sha256($this->tool_key . self::arguments_key($command) . $inputs->inputs_key($previous));
				$this->resolve_compile($request, $leaders);
				return;
			}
		}
		$scan /** vector<string> */ = [];
		$skip = false;
		foreach ($command as $argument) {
			if ($skip) { $request->pch_inputs[] = $argument; $skip = false; continue; }
			if ($argument === '-include-pch') {
				$scan[] = '-include'; $scan[] = $this->root . '/runtime-pch.hpp'; $skip = true; continue;
			}
			if ($argument !== '-c') { $scan[] = $argument; }
		}
		$scan[] = '-M'; $scan[] = '-MT'; $scan[] = 'output';
		$request->process = $this->start_program($this->config->compiler, $scan);
	}

	private function resolve_compile(native_compile_request $request, Keyed_Storage $leaders /** Keyed_Storage<native_compile_request> */): void
	{
		if (!$request->force) {
			$cached = $this->cached($request->key, $request->suffix);
			if ($cached !== null) {
				$request->artifact = $cached;
				$request->phase = native_compile_phase::finished;
				return;
			}
		}
		$identity = $request->key . '.' . $request->suffix;
		if (isset($leaders[$identity])) {
			$request->leader = $leaders[$identity];
			$request->phase = native_compile_phase::finished;
			return;
		}
		$leaders->add($identity, $request);
		$request->expected = $this->begin_action($request->key, $request->suffix);
		if ($request->template_free) {
			$tokens /** vector<string> */ = [];
			foreach ($request->command as $argument) { if ($argument !== '-c') { $tokens[] = $argument; } }
			$tokens[] = '-fsyntax-only'; $tokens[] = '-Xclang'; $tokens[] = '-dump-tokens';
			$request->phase = native_compile_phase::validation;
			$request->process = $this->start_program($this->config->compiler, $tokens);
		}
		else { $this->start_compilation($request); }
	}

	private function start_compilation(native_compile_request $request): void
	{
		$stage = new \scpp\Output_Stage($this->root);
		$request->stage = $stage;
		$command /** vector<string> */ = $request->command;
		$command[] = '-o'; $command[] = $stage->path();
		$request->phase = native_compile_phase::compilation;
		$request->process = $this->start_program($this->config->compiler, $command);
	}

	private function advance_compile(native_compile_request $request, native_process_result $result,
		Keyed_Storage $leaders /** Keyed_Storage<native_compile_request> */): void
	{
		if ($request->phase === native_compile_phase::discovery) {
			$paths /** vector<string> */ = Native_Depfiles::paths($result->stdout, $this->root);
			foreach ($request->pch_inputs as $path) { $paths[] = $path; }
			$input_key = '';
			$inputs /** nullable<Native_Dependency_Inputs> */ = $request->inputs;
			if ($inputs !== null) { $input_key = $inputs->inputs_key($inputs->remember($request->discovery, $paths)); }
			else { $input_key = self::inputs_key($paths); }
			$request->key = content_sha256($this->tool_key . self::arguments_key($request->command) . $input_key);
			if ($request->template_free) { $request->key = content_sha256("scpp-template-free-tokens-v1\n" . $request->key); }
			$this->resolve_compile($request, $leaders);
		}
		elseif ($request->phase === native_compile_phase::validation) {
			$text = "\n" . $result->stderr;
			// Clang's token protocol uses ASCII byte markers, not Unicode text coordinates.
			if (string_byte_find($text, "\ntemplate 'template'\t") !== false) {
				$command /** vector<string> */ = $request->command;
				throw new \RuntimeException('prelower_templates: template token in application input (including provider headers): '
					. $command[q_count($command) - 1]);
			}
			if (string_byte_find($text, "\neof ''\t") === false) { throw new \RuntimeException('prelower_templates: Clang did not provide a complete application token stream'); }
			$this->start_compilation($request);
		}
		elseif ($request->phase === native_compile_phase::compilation) {
			$relative = 'artifacts/' . $request->key . '.' . $request->suffix;
			$stage /** \scpp\Output_Stage */ = $request->stage;
			$stage->promote($relative, $request->expected, false);
			$digest = fs_output_digest($this->root, $relative);
			$this->save_ready($request->key, $this->root . '/' . $relative, $digest);
			$request->artifact = new native_cpp_artifact($this->root . '/' . $relative, $digest, false);
			$request->diagnostics = $result->stderr;
			$stage->close();
			$request->stage = null;
			$request->phase = native_compile_phase::finished;
		}
		else { throw new \LogicException('Completed process has no native compile phase'); }
	}
}

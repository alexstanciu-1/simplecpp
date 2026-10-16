<?php
namespace scpp\compiler;

/** Disk publication consumer. Ninja owns native dependency scheduling and freshness. */
final class Native_Ninja_Build
{
	private native_ninja_config $config;
	private string $root;
	private bool $invoked = false;

	public function __construct(native_ninja_config $config)
	{
		$this->config = $config;
		$this->root = fs_output_root($config->build_directory);
		if (!string_byte_starts_with($config->ninja, '/') || ($config->jobs < 0)) {
			throw new \RuntimeException('Invalid Ninja configuration');
		}
	}

	/** Verify and reserve one publication, generate its graph, then publish readiness after Ninja succeeds. */
	public function build(string $published): native_ninja_result
	{
		if ($this->invoked) {
			throw new \LogicException('Ninja builder owns one attempt');
		}
		$this->invoked = true;
		$source = fs_require_realpath($published);
		// Bound the paths accepted by Clang/Ninja depfile parsing as well as graph syntax.
		self::path($source);
		self::path($this->root);
		if ($source === $this->root) {
			throw new \RuntimeException('Ninja directory must differ from publication');
		}

		$source_lock = new \scpp\Lock_Reservation();
		$build_lock = new \scpp\Lock_Reservation();
		try
		{
			fs_output_read($source, '.scpp-output.lock');
			if (!$source_lock->acquire($source . '/.scpp-output.lock', true)) {
				throw new \RuntimeException('Source publication is busy');
			}
			fs_output_read($this->root, '.scpp-ninja.lock');
			if (!$build_lock->acquire($this->root . '/.scpp-ninja.lock', false)) {
				throw new \RuntimeException('Ninja directory is busy');
			}
			$this->admit($source);
			fs_output_write($this->root, '.scpp-ninja.status', "pending\n");

			$manifest = Output_Publication::require_ready($source);
			$document = json_read($manifest);
			if (!$document->has('runtime_build')) {
				throw new \RuntimeException('Publication lacks runtime build requirements; republish');
			}
			$requirements = Native_CPP_Publication::runtime_inputs($document->member('runtime_build'));
			$units /** vector<string> */ = [];
			$files = $document->member('files');
			for ($index = 0; $index < $files->size(); $index++)
			{
				$row = $files->at($index);
				$path = $row->member('path')->text();
				if (fs_output_digest($source, $path) !== $row->member('sha256')->text()) {
					throw new \RuntimeException('Published artifact changed: ' . $path);
				}
				if ($row->member('kind')->text() === 'translation_unit') {
					$units[] = $path;
				}
			}
			if (q_count($units) === 0) {
				throw new \RuntimeException('No published translation units');
			}

			$runtime = fs_require_realpath($this->config->runtime_include);
			self::path($runtime);
			$flags = Native_CPP_Commands::flags($this->config, $runtime, $requirements->definitions);
			// Separate identities: replacing a linker must not dirty compile commands.
			$this->write_changed('compiler.identity', self::identity($this->config->compiler) . self::identity('/usr/bin/env'));
			$this->write_changed('linker.identity', self::identity('/usr/bin/ld'));

			$graph = "# Generated from a ready C++ publication. Do not edit.\nninja_required_version = 1.3\n\n"
				. "rule compile\n  command = \$command\n  depfile = \$depfile\n  deps = gcc\n  description = CXX \$out\n\n"
				. "rule link\n  command = \$command\n  depfile = \$depfile\n  description = LINK \$out\n\n";
			$pch = '';
			if ($this->config->pch) {
				$this->write_changed('runtime-pch.hpp', Native_CPP_Commands::pch_header());
				$header = $this->root . '/runtime-pch.hpp';
				$pch = $this->root . '/runtime.pch';
				$graph .= $this->compile_edge(Native_CPP_Commands::pch($flags, $header), $pch, [$header], '');
			}

			$objects /** vector<string> */ = [];
			foreach ($requirements->sources as $provider)
			{
				$provider_path = fs_require_realpath($runtime . '/' . $provider);
				if (!string_byte_starts_with($provider_path, $runtime . '/')) {
					throw new \RuntimeException('Runtime provider source escapes its root');
				}
				$object = 'objects/runtime-' . content_sha256($provider) . '.o';
				$graph .= $this->compile_edge(Native_CPP_Commands::provider($flags, $provider_path), $object, [$provider_path], '');
				$objects[] = $object;
			}
			foreach ($units as $unit) {
				$unit_path = $source . '/' . $unit;
				$object = 'objects/generated-' . content_sha256($unit) . '.o';
				$graph .= $this->compile_edge(Native_CPP_Commands::implementation($flags, $source, $pch, $unit_path), $object, [$unit_path], $pch);
				$objects[] = $object;
			}

			$executable = 'app';
			$link = Native_CPP_Commands::link($flags, $objects, $requirements->libraries);
			$link[] = '-Xlinker'; $link[] = '--dependency-file'; $link[] = '-Xlinker'; $link[] = $executable . '.d';
			$link[] = '-o'; $link[] = $executable;
			$graph .= $this->edge('link', $link, $executable, $objects, [$this->root . '/compiler.identity', $this->root . '/linker.identity']);
			$graph .= 'default ' . self::path($executable) . "\n";
			$this->write_changed('build.ninja', $graph);

			$args = Native_CPP_Commands::environment($this->config->ninja);
			$args[] = '-f'; $args[] = 'build.ninja';
			if ($this->config->jobs > 0) {
				$args[] = '-j'; $args[] = '' . $this->config->jobs;
			}
			$handle = process_spawn('/usr/bin/env', $args, '', 3600000, $this->root);
			$result = new native_ninja_result();
			try
			{
				while (!process_poll($handle)) {
					dt_sleep_ms(1);
				}
				$output = process_output($handle);
				if (($output->timed_out) || ($output->stopped) || ($output->signal !== 0) || ($output->exit_code !== 0)) {
					throw new \RuntimeException('Ninja build failed: ' . $output->stdout_text . $output->stderr_text);
				}
				$result->output = $output->stdout_text . $output->stderr_text;
			}
			finally {
				process_close($handle);
			}

			$digest = fs_output_digest($this->root, 'app');
			if ($digest === '') {
				throw new \RuntimeException('Ninja produced no executable');
			}
			$result->executable = $this->root . '/' . $executable;
			fs_output_write($this->root, '.scpp-ninja.status', '{"format":"scpp-ninja-ready-v1","source":'
				. json_quote(content_sha256($manifest)) . ',"executable":' . json_quote($result->executable) . ',"sha256":' . json_quote($digest) . "}\n");
			return $result;
		}
		finally {
			$build_lock->release(); $source_lock->release();
		}
	}

	/** Reserve a dedicated directory and make retained reuse an explicit caller choice. */
	private function admit(string $source): void
	{
		$expected = "scpp-ninja-build-v1\n" . $source . "\n";
		$owner = fs_output_read($this->root, '.scpp-ninja.owner');
		if ($owner !== null)
		{
			if ($owner !== $expected) {
				throw new \RuntimeException('Ninja directory belongs to another publication or owner');
			}
			if (!$this->config->reuse) {
				throw new \RuntimeException('Use a fresh Ninja directory or explicitly enable --reuse');
			}
			return;
		}
		$scan = fs_scan($this->root);
		if ($scan === false) {
			throw new \RuntimeException('Cannot inspect Ninja directory');
		}
		$entries /** vector<string> */ = $scan;
		foreach ($entries as $entry) {
			if (($entry !== '.') && ($entry !== '..') && ($entry !== '.scpp-ninja.lock')) {
				throw new \RuntimeException('Ninja requires an empty, dedicated build directory');
			}
		}
		fs_output_write($this->root, '.scpp-ninja.owner', $expected);
	}

	/** Collect full header dependencies during compilation, with a real PCH prerequisite. */
	private function compile_edge(array $command /** vector<string> */, string $output, array $inputs /** vector<string> */, string $pch): string
	{
		$arguments /** vector<string> */ = $command;
		foreach (['-MD', '-MF', $output . '.d', '-MQ', $output, '-o', $output] as $argument) {
			$arguments[] = $argument;
		}
		$dependencies /** vector<string> */ = [$this->root . '/compiler.identity'];
		if ($pch !== '') {
			$dependencies[] = $pch;
		}
		return $this->edge('compile', $arguments, $output, $inputs, $dependencies);
	}

	/** Serialize literal argv separately from Ninja dependency paths. */
	private function edge(string $rule, array $arguments /** vector<string> */, string $output, array $inputs /** vector<string> */, array $dependencies /** vector<string> */): string
	{
		$text = 'build ' . self::path($output) . ': ' . $rule;
		foreach ($inputs as $input) {
			$text .= ' ' . self::path($input);
		}
		$text .= ' |';
		foreach ($dependencies as $dependency) {
			$text .= ' ' . self::path($dependency);
		}
		$command = self::shell('/usr/bin/env');
		foreach (Native_CPP_Commands::environment($this->config->compiler) as $argument) {
			$command .= ' ' . self::shell($argument);
		}
		foreach ($arguments as $argument) {
			$command .= ' ' . self::shell($argument);
		}
		return $text . "\n  command = " . self::literal($command) . "\n  depfile = " . self::literal($output . '.d') . "\n\n";
	}

	private function write_changed(string $path, string $bytes): void
	{
		if (fs_output_read($this->root, $path) !== $bytes) {
			fs_output_write($this->root, $path, $bytes);
		}
	}

	/** Observe tool replacement without coupling linker identity to object freshness. */
	private static function identity(string $path): string
	{
		$real = fs_require_realpath($path);
		$digest = fs_file_digest($real);
		if ($digest === '') {
			throw new \RuntimeException('Missing native tool: ' . $path);
		}
		return $real . ':' . $digest . "\n";
	}

	/** Ninja paths and shell arguments have distinct escaping rules. */
	private static function path(string $value): string
	{
		$result = '';
		for ($index = 0; $index < string_byte_len($value); $index++)
		{
			$byte_text = string_byte_slice($value, $index, 1);
			if (($byte_text === '|') || ($byte_text === "'") || ($byte_text === '"') || ($byte_text === '\\') || ($byte_text === "\t") || ($byte_text === "\r") || ($byte_text === "\n")) {
				throw new \RuntimeException('Unsupported Ninja path character');
			}
			if (($byte_text === '$') || ($byte_text === ' ') || ($byte_text === ':')) {
				$result .= '$';
			}
			$result .= $byte_text;
		}
		return $result;
	}

	/** Escape Ninja variable expansion without changing shell spelling. */
	private static function literal(string $value): string
	{
		$result = '';
		for ($index = 0; $index < string_byte_len($value); $index++)
		{
			$byte_text = string_byte_slice($value, $index, 1);
			if (($byte_text === "\n") || ($byte_text === "\r")) {
				throw new \RuntimeException('Multiline Ninja value');
			}
			if ($byte_text === '$') {
				$result .= '$';
			}
			$result .= $byte_text;
		}
		return $result;
	}

	/** Quote exactly one POSIX shell argument, including literal dollar signs. */
	private static function shell(string $value): string
	{
		$result = "'";
		for ($index = 0; $index < string_byte_len($value); $index++) {
			$byte_text = string_byte_slice($value, $index, 1);
			$result .= $byte_text === "'" ? "'\"'\"'" : $byte_text;
		}
		return $result . "'";
	}
}

<?php
namespace scpp\compiler;

/** Explicit Linux/Clang build configuration, independent of semantic preparation. */
abstract class native_cpp_toolchain
{
	public string $compiler;
	public string $runtime_include;
	public bool $pch = true;
	public int $optimization = 0;
	public function __construct(string $compiler, string $runtime_include)
	{
		$this->compiler = $compiler;
		$this->runtime_include = $runtime_include;
	}
}

/** Existing content-addressed executor settings. */
final class native_cpp_config extends native_cpp_toolchain
{
	public string $cache_directory;
	/** Explicit opt-in: runtime changes require a fresh cache directory. */
	public bool $runtime_snapshot = false;
	public function __construct(string $compiler, string $runtime_include, string $cache_directory)
	{
		parent::__construct($compiler, $runtime_include);
		$this->cache_directory = $cache_directory;
	}
}

/** Ninja alone owns freshness in this directory; no native action cache beneath it. */
final class native_ninja_config extends native_cpp_toolchain
{
	public string $build_directory;
	public string $ninja = '/usr/bin/ninja';
	public bool $reuse = false;
	public int $jobs = 0;
	public function __construct(string $compiler, string $runtime_include, string $build_directory)
	{
		parent::__construct($compiler, $runtime_include);
		$this->build_directory = $build_directory;
	}
}

final class native_ninja_result {
	public string $executable = '';
	public string $output = '';
}

final class native_cpp_result {
	public string $executable = '';
	public int $compiled = 0;
	public int $reused = 0;
	public int $pch_built = 0;
	public int $linked = 0;
}

/** Invocation-local action result, not a retained semantic record. */
final class native_cpp_artifact
{
	public string $path;
	public string $digest;
	public bool $reused;
	public function __construct(string $path, string $digest, bool $reused)
	{
		$this->path = $path;
		$this->digest = $digest;
		$this->reused = $reused;
	}
}

/** Published generated input, not an AST or semantic dependency. */
final class native_cpp_input {
	public string $digest = '';
	public array $includes /** vector<string> */ = [];
}

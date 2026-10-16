<?php
namespace scpp\compiler;

/** Ordered Clang recipes only: no filesystem writes, execution or freshness decisions. */
final class Native_CPP_Commands
{
	/** Preserve the existing compiler driver flags and definition order. */
	public static function flags(native_cpp_toolchain $config, string $runtime, array $definitions /** vector<string> */): array /** vector<string> */
	{
		if (!string_byte_starts_with($config->compiler, '/')) {
			throw new \RuntimeException('Native compiler must be absolute');
		}
		if (($config->optimization !== 0) && ($config->optimization !== 2)) {
			throw new \RuntimeException('Native optimization must be 0 or 2');
		}
		if (!string_byte_starts_with($config->linker, '/') || ($config->linker_threads < 0)) {
			throw new \RuntimeException('Native linker must be absolute and linker threads nonnegative');
		}
		$optimization = $config->optimization === 2 ? '-O2' : '-O0';
		$result /** vector<string> */ = ['--driver-mode=g++', '-std=c++23', $optimization,
			'-DSCPP_LANGUAGE_TARGET_PHP=1', '-pthread', '-I', $runtime];
		foreach ($definitions as $definition) {
			$result[] = '-D' . $definition;
		}
		return $result;
	}

	public static function pch_header(): string
	{
		return '#include "scpp/int_t.hpp"' . "\n" . '#include "scpp/nullable.hpp"' . "\n"
			. '#include "scpp/cast.hpp"' . "\n" . '#include "lang/php/operators/conditional/condition_truthiness.hpp"' . "\n"
			. '#include "scpp/generated/operators.hpp"' . "\n";
	}

	public static function pch(array $flags /** vector<string> */, string $header): array /** vector<string> */
	{
		$result /** vector<string> */ = $flags;
		$result[] = '-fpch-instantiate-templates';
		$result[] = '-x'; $result[] = 'c++-header'; $result[] = $header;
		return $result;
	}

	public static function provider(array $flags /** vector<string> */, string $source): array /** vector<string> */
	{
		$result /** vector<string> */ = $flags;
		$result[] = '-c'; $result[] = $source;
		return $result;
	}

	/** Generated units alone receive the publication include path and optional runtime PCH. */
	public static function implementation(array $flags /** vector<string> */, string $published, string $pch, string $source): array /** vector<string> */
	{
		$result /** vector<string> */ = $flags;
		$result[] = '-I'; $result[] = $published;
		if ($pch !== '') {
			$result[] = '-include-pch'; $result[] = $pch;
		}
		$result[] = '-c'; $result[] = $source;
		return $result;
	}

	/** Link-only configuration: preserve object/library order and compile/PCH recipes. */
	public static function link(native_cpp_toolchain $config, array $flags /** vector<string> */, array $objects /** vector<string> */, array $libraries /** vector<string> */): array /** vector<string> */
	{
		$result /** vector<string> */ = $flags;
		$result[] = '-fuse-ld=' . $config->linker;
		if ($config->linker_threads > 0) {
			$result[] = '-Xlinker'; $result[] = '--threads=' . $config->linker_threads;
		}
		foreach ($objects as $path) {
			$result[] = $path;
		}
		foreach ($libraries as $library) {
			$result[] = '-l' . $library;
		}
		return $result;
	}

	/** Arguments for /usr/bin/env, shared by both execution paths. */
	public static function environment(string $program): array /** vector<string> */
	{
		return ['-i', 'PATH=/usr/bin:/bin', 'LC_ALL=C', 'TZ=UTC', 'SOURCE_DATE_EPOCH=0', $program];
	}
}

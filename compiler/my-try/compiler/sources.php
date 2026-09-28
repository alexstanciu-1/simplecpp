<?php

/* Resolve source identity at intake; publication uses direct record references. */
namespace scpp\compiler;

final class Source_Registry
{
	/** Normalize lexical components even when a deletion notification names a missing path. */
	public static function normalize(string $path): string
	{
		if (!string_byte_starts_with($path, '/')) {
			$path = fs_require_realpath('.') . '/' . $path;
		}
		$result = '/';
		$part = '';
		$limit = string_byte_len($path) + 1;
		for ($index = 0; $index < $limit; $index++)
		{
			$byte = $index === string_byte_len($path) ? '/' : string_byte_slice($path, $index, 1);
			if ($byte !== '/') {
				$part .= $byte;
				continue;
			}
			if ($part === '..') {
				$result = fs_dirname($result);
			}
			elseif (($part !== '') && ($part !== '.')) {
				$result = ($result === '/' ? '' : $result) . '/' . $part;
			}
			$part = '';
		}
		// Resolve existing parent aliases without requiring the notified file to exist.
		$ancestor = fs_dirname($result);
		while (!fs_is_dir($ancestor)) {
			$parent = fs_dirname($ancestor);
			if ($parent === $ancestor) {
				return $result;
			}
			$ancestor = $parent;
		}
		$canonical = fs_require_realpath($ancestor);
		$prefix = $ancestor === '/' ? 0 : string_byte_len($ancestor);
		$suffix = string_byte_slice($result, $prefix, string_byte_len($result) - $prefix);
		return ($canonical === '/' ? '' : $canonical) . $suffix;
	}

	/** Join only at IO/notification boundaries; records retain relative paths. */
	public static function full_path(module $owner, string $relative): string
	{
		return ($owner->resolved_path === '/' ? '' : $owner->resolved_path) . '/' . $relative;
	}

	/** The caller normalizes an external path before identifying its module. */
	public static function relative_path(module $owner, string $path): string
	{
		if (!Module_Loader::contains_path($owner, $path)) {
			throw new \LogicException('Source is outside its module: ' . $path);
		}
		$prefix = $owner->resolved_path === '/' ? 1 : string_byte_len($owner->resolved_path) + 1;
		return string_byte_slice($path, $prefix, string_byte_len($path) - $prefix);
	}

	/** Register a normalized module-relative path, including in-memory source inputs. */
	public static function add(module $owner, file $snapshot): source_record
	{
		$members /** Keyed_Storage<source_record> */ = $owner->sources;
		if (isset($members[$snapshot->path])) {
			throw new \LogicException('Source path is already registered');
		}
		$source = new source_record($owner, $snapshot);
		$members->add($source->path, $source);
		return $source;
	}

	/** External lookup first selects a module, then its relative-path index. */
	public static function find(string $path): ?source_record
	{
		$path = self::normalize($path);
		foreach (Model::$modules as $owner)
		{
			if ($owner->changes === change_state::deleted) {
				continue;
			}
			if (Module_Loader::contains_path($owner, $path))
			{
				$members /** Keyed_Storage<source_record> */ = $owner->sources;
				$relative = self::relative_path($owner, $path);
				if (isset($members[$relative])) {
					return $members[$relative];
				}
				return null;
			}
		}
		return null;
	}

	/** Notifications use the same module-local index as recursive scanning. */
	public static function resolve(string $path): source_record
	{
		foreach (Model::$modules as $owner)
		{
			if ($owner->changes === change_state::deleted) {
				continue;
			}
			if (Module_Loader::contains_path($owner, $path))
			{
				$members /** Keyed_Storage<source_record> */ = $owner->sources;
				$relative = self::relative_path($owner, $path);
				if (isset($members[$relative])) {
					return $members[$relative];
				}
				$snapshot = new file();
				$snapshot->path = $relative;
				$snapshot->disk_source = true;
				return self::add($owner, $snapshot);
			}
		}
		throw new \LogicException('Module membership changed: call init with the complete module list, then exec');
	}
}

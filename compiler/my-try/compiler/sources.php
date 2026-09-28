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

	/** Register explicit membership once, including in-memory source inputs. */
	public static function add(module $owner, file $snapshot): source_record
	{
		$index /** Keyed_Storage<source_record> */ = Model::$sources_by_path;
		$snapshot->path = self::normalize($snapshot->path);
		if (isset($index[$snapshot->path])) {
			throw new \LogicException('Source path is already registered');
		}
		$source = new source_record($owner, $snapshot);
		$members /** Storage<source_record> */ = $owner->sources;
		$members->append($source);
		$index->add($source->path, $source);
		return $source;
	}

	public static function find(string $path): ?source_record
	{
		$index /** Keyed_Storage<source_record> */ = Model::$sources_by_path;
		if (isset($index[$path])) {
			return $index[$path];
		}
		return null;
	}

	/** New notifications establish membership once; existing paths use the unique index. */
	public static function resolve(string $path): source_record
	{
		$known = self::find($path);
		if ($known !== null) {
			return $known;
		}
		foreach (Model::modules() as $owner)
		{
			if (Module_Loader::contains_path($owner, $path)) {
				$snapshot = new file();
				$snapshot->path = $path;
				$snapshot->disk_source = true;
				return self::add($owner, $snapshot);
			}
		}
		throw new \LogicException('Module membership changed: call init with the complete module list, then exec');
	}
}

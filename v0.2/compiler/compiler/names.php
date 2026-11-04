<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Exact source identity rules; e.g. Sub\ping in App resolves to App\Sub\ping, with no fallback search. */
final class Source_Names
{
	/** Canonical paths retain a leading backslash only on rooted references, never on declaration identities. */
	public static function resolve(string $path, ?string $namespace): string
	{
		if (str_starts_with($path, '\\')) {
			return substr($path, 1);
		}
		return $namespace === null ? $path : $namespace . '\\' . $path;
	}
}

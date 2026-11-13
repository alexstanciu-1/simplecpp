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

/** C++ spelling shared by the manifest and application-defined struct and field names. */
final class Cpp_Identifier
{
	private const string KEYWORDS = ' alignas alignof and and_eq asm auto bitand bitor bool break case catch char char8_t char16_t char32_t'
			. ' class compl concept const consteval constexpr constinit const_cast continue co_await co_return co_yield'
			. ' decltype default delete do double dynamic_cast else enum explicit export extern false float for friend goto'
			. ' if inline int long mutable namespace new noexcept not not_eq nullptr operator or or_eq private protected'
			. ' public register reinterpret_cast requires return short signed sizeof static static_assert static_cast struct'
			. ' switch template this thread_local throw true try typedef typeid typename union unsigned using virtual void'
			. ' volatile wchar_t while xor xor_eq ';

	public static function portable(string $name, bool $allow_local_underscore = false): bool
	{
		$pattern = $allow_local_underscore ? '/^(?:[A-Za-z]|_(?![A-Z]))[A-Za-z0-9_]*$/D' : '/^[A-Za-z][A-Za-z0-9_]*$/D';
		return preg_match($pattern, $name) === 1 && !str_contains($name, '__')
			&& !str_contains(self::KEYWORDS, ' ' . $name . ' ');
	}

	/** Preserve source spelling unless C++ reserves it or it could collide with an escape or runtime macro. */
	public static function source(string $name): string
	{
		return self::portable($name, true) && !str_starts_with($name, 'scpp_escape_') && !str_starts_with($name, 'SCPP_')
			&& $name !== 'NULL' ? $name : 'scpp_escape_' . bin2hex($name);
	}
}

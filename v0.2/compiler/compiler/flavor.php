<?php

declare(strict_types=1);
namespace scpp\compiler;

/**
 * Syntax selection belongs to each source, independently of project policy.
 *
 * Backing: Native backing width undecided; preserve cpp/php/js labels.
 */
enum flavor: string
{
	case cpp = 'cpp';
	case php = 'php';
	case js = 'js';

	/** Classify bounded lexical keywords; this does not establish semantic support. */
	public function word_kind(string $word): token_kind
	{
		$words = match ($this) {
			self::cpp => 'global use as function echo auto class struct if else elseif return const true false null void for while do break continue switch case default namespace template typename new public private protected static try catch throw',
				self::js => 'global use as namespace export echo let function void class if else elseif return const true false null for while do break continue switch case default new try catch finally throw async await',
				self::php => 'global abstract and as async await break case catch class clean const continue data default do echo else elseif empty enum extends false final finally fn for foreach function if implements instanceof interface isset lock match move namespace new null object_cast or private protected public readonly return set static struct switch take template throw trait true try typename union unset use void weak while xor',
		};
		return str_contains(' ' . $words . ' ', ' ' . $word . ' ') ? token_kind::keyword : token_kind::identifier;
	}
}

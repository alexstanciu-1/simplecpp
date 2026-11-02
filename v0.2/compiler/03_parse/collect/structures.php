<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Discovery is a notification, not permission to consume an unfinished body. */
interface declaration_sink
{
	public function discovered(ast_function_declaration $function): void;
}

/** Published declaration candidates keyed by canonical handles, e.g. func:ping. No mutable slot records. */
final readonly class global_symbols
{
	public array $declarations /** array<string,list<ast_function_declaration>>; canonical key -> candidates */;

	public function __construct(array $declarations /** array<string,list<ast_function_declaration>> */)
	{
		$this->declarations = $declarations;
	}

	/** Resolve against this publication; absence is null, duplicate candidates are ambiguous. */
	public function resolve(string $key): ?ast_function_declaration
	{
		$candidates = $this->declarations[$key] ?? [];
		if (count($candidates) > 1) {
			throw new \RuntimeException('Ambiguous symbol: ' . $key);
		}
		return $candidates[0] ?? null;
	}
}

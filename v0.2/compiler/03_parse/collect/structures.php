<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Discovery is a notification, not permission to consume an unfinished body. */
interface declaration_sink
{
	public function discovered(ast_function_declaration $function): void;
}

/** Completed frontend publication; candidates remain distinct until declaration validation. */
final readonly class global_symbols
{
	public array $functions /** array<string,list<ast_function_declaration>> */;

	public function __construct(array $functions /** array<string,list<ast_function_declaration>> */)
	{
		$this->functions = $functions;
	}
}

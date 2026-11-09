<?php

declare(strict_types=1);
namespace scpp\compiler;

/**
 * Plans per-unit headers from completed watch relations, without inspecting executable syntax.
 * Example: a function watched only in its own unit stays declared in that unit's .cpp.
 */
final class Cpp_Headers
{
	/** @var string[] Canonical symbol key to owning header basename; no AST copies. */
	private array $declarations /** array<string,string> */ = [];
	/** @var string[] Relative artifact path to generated header text. */
	private array $files /** array<string,string> */ = [];

	/** Plan native declarations from completed watchers; header text is independent of source iteration order. */
	public function __construct(prepared_program $program)
	{
		$emitter = new Cpp_Emitter();
		foreach ($program->files as $key => $file) {
			$source_key = (string) $key;
			$header = hash('sha256', (string) $key) . '.hpp';
			$lines = [];
			$includes = [];
			foreach ($file->published_declarations() as $node) {
				if (!($node instanceof ast_function_declaration) && !($node instanceof ast_global_declaration)) {
					continue;
				}
				$symbol = $file->scope->parent->entry($node->symbol_key());
				if (!$program->dependencies->has_external_observers($symbol, $source_key)) {
					continue;
				}
				$lines[$node->symbol_key()] = $emitter->declaration($node, $includes);
				$this->declarations[$node->symbol_key()] = $header;
			}
			if ($lines === []) {
				continue;
			}
			ksort($includes, \SORT_STRING);
			ksort($lines, \SORT_STRING);
			$text = "// Generated cross-unit declarations.\n#pragma once\n";
			foreach ($includes as $include => $_) {
				$text .= '#include <' . $include . ">\n";
			}
			$this->files['units/' . $header] = $text . Cpp_Emitter::namespace_body(
				$program->session?->cpp_namespace ?? 'App', implode("\n", $lines) . "\n");
		}
		ksort($this->files, \SORT_STRING);
	}

	public function declaration_header(ast_declaration_interface $node): ?string
	{
		return $this->declarations[$node->symbol_key()] ?? null;
	}

	/** @return string[] */
	public function files(): array /** array<string,string> */
	{
		return $this->files;
	}
}

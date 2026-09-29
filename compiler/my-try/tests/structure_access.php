<?php

namespace scpp\compiler;

require_once dirname(__DIR__) . '/boot.php';
\define('dbg', false);

final class Structure_Access_Test
{
	private static function check(bool $condition, string $message): void
	{
		if (!$condition) {
			throw new \LogicException($message);
		}
	}

	/** Named access, generic traversal and snapshots must observe the same published tree. */
	public static function run(): void
	{
		$source = new file();
		$source->path = 'access.phs';
		$source->content = 'function add(int $a, int $b): int { return $a; } $x int = add(1, 2);';
		$parsed = (new Parser((new Tokenizer($source))->tokenize()))->parse();
		$root = $parsed->root;
		$function = $root->declarations[0];
		$binding = $root->body->statements[0];
		$call = $binding->initializer;
		self::check($parsed->source_file() === $source, 'Source identity changed');
		self::check($parsed->root_scope() === $root->file_scope(), 'File scope changed');
		self::check($root->body->local_scope() !== $root->file_scope(), 'File and executable scopes collapsed');
		self::check($function->signature_scope()->parent_scope() === $root->file_scope(), 'Signature scope ancestry changed');
		$children = iterator_to_array($function->children());
		self::check($children === [$function->parameters[0], $function->parameters[1], $function->return_type, $function->body], 'Function grammar order changed');
		self::check(iterator_to_array($call->children()) === [$call->arguments[0], $call->arguments[1]], 'Call order changed');
		self::check(iterator_to_array($binding->children()) === [$binding->type_syntax, $call], 'Declaration order changed');
		self::check($call->name === 'add' && $function->name === 'add' && $binding->name === 'x', 'Saved names missing');
		self::check($binding->preparation() === null, 'Parsing prepared facts');
		$one = $function->children();
		$two = $function->children();
		$one->next();
		self::check($one->current() === $function->parameters[1] && $two->current() === $function->parameters[0], 'Iterator state is shared');
		self::check($parsed->collection->token_snapshot() === $parsed->tokens, 'Token ownership changed');
		foreach ($parsed->collection->entries as $entry) {
			self::check($entry->source_file() === $source, 'Occurrence source access lost identity');
			self::check($entry->token_snapshot() === $parsed->tokens, 'Occurrence token access lost identity');
			self::check($entry->collection === $parsed->collection, 'Occurrence collection identity changed');
		}
	}
}

Structure_Access_Test::run();
echo "Structure access: named children, lazy traversal, independent cursors and source identity passed\n";

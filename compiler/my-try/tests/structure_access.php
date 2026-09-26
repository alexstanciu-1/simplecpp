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
		$function_node /** ast_node */ = $root->first_child();
		$binding_node /** ast_node */ = $function_node->next();
		$function = Syntax_Nodes::function_data($function_node);
		$binding = Syntax_Nodes::binding_data($binding_node);
		$call_node /** ast_node */ = $binding->value;
		$call = Syntax_Nodes::call_data($call_node);
		$arguments /** Storage<ast_node> */ = $call->arguments;
		$parameters /** Storage<ast_node> */ = $function->parameters;
		self::check($parsed->source_file() === $source, 'Source access lost identity');
		self::check($parsed->root_scope() === Syntax_Nodes::block_data($root)->lexical_scope(), 'Root scope access disagrees');
		self::check($parsed->collection->token_snapshot() === $parsed->tokens, 'Token snapshot access lost identity');
		self::check($parameters[0] === $function_node->first_child(), 'Named parameters differ from child traversal');
		self::check($parameters[1]->next() === $function->return_type, 'Return type has wrong child order');
		self::check($function->return_type->next() === $function->body, 'Body has wrong child order');
		$type_arguments /** Storage<ast_node> */ = $call->template_arguments;
		self::check($type_arguments->is_empty(), 'Unexpected type arguments');
		self::check(($arguments[0] === $call_node->first_child()) && ($arguments[0]->next() === $arguments[1]), 'Argument traversal disagrees');
		self::check($parsed->tokens->text_at($call->name_token_index) === 'add', 'Call spelling access disagrees');
		self::check($binding->syntax_kind === binding_kind::declaration, 'Parsed binding classification changed');
		self::check($binding->type_syntax === $binding_node->first_child(), 'Declared type access disagrees');
		self::check($binding->preparation() === null, 'Parsing manufactured preparation facts');
		self::check(Syntax_Nodes::binding_data($binding_node) === $binding, 'Specialization access lost identity');

		$snapshot /** Storage<ast_node> */ = $root->children_snapshot();
		$snapshot->remove(0);
		self::check($root->first_child() === $function_node, 'Snapshot mutation edited the AST');
		self::check($function_node->next() === $binding_node, 'Snapshot mutation edited sibling links');
		self::check(q_count($root->children_snapshot()) === 2, 'Snapshot mutation changed tree membership');
		foreach ($parsed->collection->entries as $entry) {
			self::check($entry->source_file() === $source, 'Occurrence source access lost identity');
			self::check($entry->token_snapshot() === $parsed->tokens, 'Occurrence token access lost identity');
			self::check($entry->collection === $parsed->collection, 'Occurrence collection identity changed');
		}
	}
}

Structure_Access_Test::run();
echo "Structure access: named children, linked traversal, snapshot isolation and source identity passed\n";

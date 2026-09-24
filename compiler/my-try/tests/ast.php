<?php

namespace scpp\compiler;

require_once dirname(__DIR__) . '/boot.php';
\define('dbg', false);

final class AST_Test
{
	private const PAYLOADS = [
		'blocks' => block_specialization::class,
		'functions' => function_specialization::class,
		'parameters' => parameter_specialization::class,
		'calls' => call_specialization::class,
		'binaries' => binary_specialization::class,
		'expression_statements' => expression_statement_specialization::class,
		'returns' => return_specialization::class,
		'bindings' => binding_specialization::class,
		'array_types' => array_type_specialization::class,
		'array_literals' => array_literal_specialization::class,
		'indexes' => index_specialization::class,
		'structs' => struct_specialization::class,
		'fields' => field_specialization::class,
		'field_accesses' => field_access_specialization::class,
	];

	private static function check(bool $condition): void
	{
		if (!$condition) { throw new \RuntimeException('AST ownership assertion failed'); }
	}

	private static function tokens(string $content): token_list
	{
		$file = new file();
		$file->path = 'ast.phs';
		$file->content = $content;
		$scanner = new Tokenizer();
		$scanner->init($file);
		return $scanner->tokenize();
	}

	/** Traverse syntax edges only, not collector/scope backlinks or owner stores. */
	private static function visit(ast_node $node, parsed_file $syntax, \SplObjectStorage $nodes, \SplObjectStorage $payloads): void
	{
		self::check(!$nodes->contains($node)); // This fixture's AST is a tree logically.
		$nodes->attach($node);
		self::check($node->token_index >= 0 && $node->end_token_index <= count($syntax->tokens->tokens));
		self::check($node->token_index <= $node->end_token_index);
		$payload = $node->specialization;
		Syntax_Nodes::validate_payload($node->kind, $payload);
		if ($payload === null) { return; }
		self::check(!$payloads->contains($payload));
		$payloads->attach($payload);
		foreach (get_object_vars($payload) as $value) {
			if ($value instanceof ast_node) {
				self::visit($value, $syntax, $nodes, $payloads);
			} elseif ($value instanceof Storage) {
				$start = -1;
				foreach ($value as $position => $child) {
					self::check($child->token_index >= $start);
					$start = $child->token_index;
					self::visit($child, $syntax, $nodes, $payloads);
				}

			}
		}
	}

	private static function verify(parsed_file $syntax): \SplObjectStorage
	{
		self::check($syntax->collection->root === $syntax->root);
		self::check($syntax->collection->source === $syntax->tokens);
		$nodes = new \SplObjectStorage();
		$payloads = new \SplObjectStorage();
		self::visit($syntax->root, $syntax, $nodes, $payloads);
		foreach ($payloads as $payload) {
			if ($payload instanceof block_specialization && $payload->scope !== $syntax->root->specialization->scope) {
				$found = false;
				foreach ($syntax->scopes as $owned) { if ($owned === $payload->scope) { $found = true; } }
				self::check($found);
			}
		}
		foreach ($syntax->collection->entries as $position => $entry) {
			self::check($entry->local_index === $position && $nodes->contains($entry->node));
		}
		return $nodes;
	}

	public static function run(): void
	{
		$source = <<<'PHS'
struct Pair { int $first; int $second; }
template<T> function identity(T $value): T { return $value; }
function pick(int &$first, int $second): int { return $second; }
$values int[2] = [2, 8];
$pair Pair;
$pair->first = $values[0];
identity<int>($pair->first);
return pick($values[0], identity<int>(8));
PHS;
		$parser = new Parser();
		$parser->init(self::tokens($source));
		$first = $parser->parse();
		$first_nodes = self::verify($first);
		$types = [];
		foreach ($first_nodes as $node) {
			if ($node->specialization !== null) { $types[get_class($node->specialization)] = true; }
		}
		foreach (self::PAYLOADS as $field => $type) {
			self::check($field === 'binaries' ? !isset($types[$type]) : isset($types[$type]));
		}
		$before = serialize($first);
		$parser->init(self::tokens($source));
		$second = $parser->parse();
		$second_nodes = self::verify($second);
		foreach ($second_nodes as $node) { self::check(!$first_nodes->contains($node)); }
		self::check(serialize($first) === $before);

		$parser->init(self::tokens('function broken(): int { return 1;'));
		$failed = false;
		try { $parser->parse(); } catch (\RuntimeException $expected) { $failed = true; }
		self::check($failed && serialize($first) === $before);
		$parser->init(self::tokens(''));
		$empty = $parser->parse();
		self::check(count(self::verify($empty)) === 1);
		self::check($empty->root->specialization->children->is_empty());
		echo "AST: object graph, child lists, payload coverage, order, collection identity and parser reuse passed\n";
	}
}

// Reusing a standalone parser must create a new owned root scope.
$file = new file();
$file->path = 'repeat.phs';
$file->content = 'function main(): int { return 1; }';
$scanner = new Tokenizer();
$scanner->init($file);
$parser = new Parser();
$parser->init($scanner->tokenize());
$first = $parser->parse();
$second = $parser->parse();
if (count($first->scopes) !== 2 || count($second->scopes) !== 2 || $first->scopes[0] === $second->scopes[0]) {
	throw new \LogicException('Standalone parser scope ownership failed');
}
try {
	Syntax_Nodes::validate_payload(node_kind::integer_literal, new binary_specialization());
	throw new \RuntimeException('Invalid payload accepted');
} catch (\LogicException $expected) { }
try {
	Syntax_Nodes::validate_payload(node_kind::block, null);
	throw new \RuntimeException('Missing payload accepted');
} catch (\LogicException $expected) { }
AST_Test::run();

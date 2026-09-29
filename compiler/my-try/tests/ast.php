<?php

namespace scpp\compiler;

require_once dirname(__DIR__) . '/boot.php';
\define('dbg', false);

final class AST_Test
{

	private static function check(bool $condition): void
	{
		if (!$condition) {
			throw new \RuntimeException('AST ownership assertion failed');
		}
	}

	private static function tokens(string $content): token_list
	{
		$file = new file();
		$file->path = 'ast.phs';
		$file->content = $content;
		$scanner = new Tokenizer($file);
		return $scanner->tokenize();
	}

	/** get_object_vars omits uninitialized fields, so check declarations explicitly. */
	private static function initialized(object $record): void
	{
		foreach ((new \ReflectionClass($record))->getProperties() as $property) {
			if (!$property->isInitialized($record)) {
				throw new \LogicException('Uninitialized published field: ' . get_class($record) . '::$' . $property->getName());
			}
		}
	}

	/** Traverse syntax edges only, not collector/scope backlinks or owner stores. */
	private static function visit(ast_node $node, parsed_file $syntax, \SplObjectStorage $nodes, \SplObjectStorage $payloads): void
	{
		self::initialized($node);
		self::check(!$nodes->contains($node)); // This fixture's AST is a tree logically.
		$nodes->attach($node);
		self::check((new \ReflectionClass($node))->isFinal());
		self::check($node->start_token() >= 0 && $node->end_token() <= count($syntax->tokens->tokens));
		self::check($node->start_token() <= $node->end_token());
		$payloads->attach($node);
		foreach ($node->children() as $child) {
			self::visit($child, $syntax, $nodes, $payloads);
		}
	}

	/** Verify initialized ownership and shared references throughout one completed syntax graph. */
	private static function verify(parsed_file $syntax): \SplObjectStorage
	{
		self::initialized($syntax);
		self::initialized($syntax->collection);
		foreach ($syntax->scopes as $scope) {
			self::initialized($scope);
		}
		self::initialized($syntax->root->file_scope());
		self::check($syntax->collection->root === $syntax->root);
		self::check($syntax->collection->token_snapshot() === $syntax->tokens);
		$nodes = new \SplObjectStorage();
		$payloads = new \SplObjectStorage();
		self::visit($syntax->root, $syntax, $nodes, $payloads);
		foreach ($payloads as $payload)
		{
			if ($payload instanceof function_body_node && $payload->local_scope() !== $syntax->root->file_scope())
			{
				$found = false;
				foreach ($syntax->scopes as $owned) {
					if ($owned === $payload->local_scope()) {
						$found = true;
					}
				}
				self::check($found);
			}
		}
		foreach ($syntax->collection->entries as $position => $entry) {
			self::initialized($entry);
			self::check($entry->syntax()->occurrence() === $entry);
			self::check($entry->local_index === $position && $nodes->contains($entry->syntax()));
		}
		return $nodes;
	}

	/** Distinguish meaningful absent syntax from incomplete required fields. */
	private static function optional_syntax(): void
	{
		$parser = new Parser(self::tokens('function f(int $a, int &$b): void { return; } $x int; $y int = 1; $x = 2; $items int[1] = []; $items[0] = 3;'));
		$result = $parser->parse();
		self::verify($result);
		$function = $result->root->declarations[0];
		self::check($function->parameters[0]->mode === passing_mode::value);
		self::check($function->parameters[1]->mode === passing_mode::reference);
		self::check($function->body->statements[0]->expression === null);
		$statements = $result->root->body->statements;
		self::check($statements[0] instanceof variable_declaration_node && $statements[0]->initializer === null);
		self::check($statements[1]->initializer instanceof integer_literal_node);
		self::check($statements[2]->expression instanceof assignment_expression_node);
		self::check($statements[2]->expression->target instanceof variable_reference_node);
		self::check($statements[3]->initializer->elements->is_empty());
		self::check($statements[4]->expression->target instanceof index_node);
	}

	/** Failed parsing keeps recognized declarations but marks its mutable result incomplete. */
	private static function external_scope_reuse(): void
	{
		$scope = new scope();
		$parser = new Parser(self::tokens('function kept(): void { return; }'), $scope);
		$first = $parser->parse();
		self::verify($first);
		$before = serialize($scope);
		$parser->init(self::tokens('struct Pending { int $x; } function broken(int $a): int { return $a;'), $scope);
		$failed = false;
		try {
			$parser->parse();
		}
		catch (\RuntimeException $expected) {
			$failed = true;
		}
		self::check($failed && !$parser->result()->complete && count($scope->types_named('Pending')) === 1);
		$parser->init(self::tokens('function next(): void { return; }'), $scope);
		$next = $parser->parse();
		self::verify($next);
		self::check($next->root->file_scope() === $scope);
		self::check($next->root->body->local_scope()->parent_scope() === $scope);
		self::check(count($scope->functions_named('kept')) === 1 && count($scope->functions_named('next')) === 1);
		self::check((count($scope->functions_named('broken')) === 1) && (count($scope->types_named('Pending')) === 1));
		// Omitting the target on re-init must clear the previous external scope.
		$parser->init(self::tokens(''));
		$standalone = $parser->parse();
		self::verify($standalone);
		self::check($standalone->root->file_scope() !== $scope);
		self::check($standalone->scopes[0]->parent_scope() === null);
	}

	/** Finalization publishes once; rejected later operations must not duplicate indexes. */
	private static function collector_finalization(): void
	{
		$tokens = self::tokens('$x int;');
		$parser = new Parser($tokens);
		$syntax = $parser->parse();
		$node = new variable_declaration_node();
		$node->name = 'canonical_name';
		$node->type_syntax = new named_type_node();
		$node->type_syntax->name = 'int';
		$node->type_syntax->set_span(1, 2);
		$node->set_span(0, 3);
		$scope = new scope();
		$collector = new Symbol_Collector(new collected_file($tokens), $tokens, $scope, null, 1);
		$position = $collector->record($node, 0, collected_name_kind::variable_declaration, $scope, 'canonical_name');
		self::check($scope->has_variables());
		self::check($node->occurrence()->syntax() === $node);
		try {
			$collector->record($node, 0, collected_name_kind::variable_declaration, $scope, 'duplicate');
			throw new \RuntimeException('Expected duplicate occurrence rejection');
		}
		catch (\LogicException $expected) {
			self::check($node->occurrence()->name === 'canonical_name');
		}
		// Canonical names come from the frontend, independently of the source token '$x'.
		$result = $collector->finish($syntax->root);
		self::initialized($result);
		self::initialized($result->entries[$position]);
		self::check($scope->variables_named('canonical_name')[0] === $result->entries[$position]);
		$before = serialize($result);
		$rejections = 0;
		try {
			$collector->finish($syntax->root);
		}
		catch (\LogicException $expected) {
			++$rejections;
		}
		try {
			$collector->record($node, 0, collected_name_kind::variable_declaration, $scope, 'canonical_name');
		}
		catch (\LogicException $expected) {
			++$rejections;
		}
		self::check($rejections === 1 && serialize($result) === $before && count($scope->variables_named('canonical_name')) === 1);
	}

	/** Exercise payload coverage, retained syntax identity, and parser reuse across failures. */
	public static function run(): void
	{
		self::collector_finalization();
		self::optional_syntax();
		self::external_scope_reuse();
		$source = <<<'PHS'
struct Pair { int $first; int $second; }
template<T> function identity(T $value): T { return $value; }
function pick(int &$first, int $second): int { return $second; }
$values int[2] = [2, 8];
$flag bool = false;
$fraction float = .5e2;
$pair Pair;
$pair->first = $values[0];
identity<int>($pair->first);
return pick($values[0], identity<int>(8));
PHS;
		$parser = new Parser(self::tokens($source));
		$first = $parser->parse();
		$first_nodes = self::verify($first);
		$types = [];
		foreach ($first_nodes as $node) {
			$types[get_class($node)] = true;
		}
		foreach ([file_node::class, function_body_node::class, function_node::class, parameter_node::class, named_type_node::class, call_node::class, integer_literal_node::class, variable_reference_node::class, variable_declaration_node::class, assignment_expression_node::class, array_type_node::class, array_literal_node::class, index_node::class, struct_node::class, field_node::class, field_access_node::class] as $type) {
			self::check(isset($types[$type]));
		}
		$before = serialize($first);
		$parser->init(self::tokens($source));
		$second = $parser->parse();
		$second_nodes = self::verify($second);
		foreach ($second_nodes as $node) {
			self::check(!$first_nodes->contains($node));
		}
		self::check(serialize($first) === $before);

		$parser->init(self::tokens('function broken(): int { return 1;'));
		$failed = false;
		try {
			$parser->parse();
		}
		catch (\RuntimeException $expected) {
			$failed = true;
		}
		self::check($failed && serialize($first) === $before);
		$parser->init(self::tokens(''));
		$empty = $parser->parse();
		self::check(count(self::verify($empty)) === 2);
		self::check($empty->root->body->statements->is_empty() && $empty->root->declarations->is_empty());
		echo "AST: typed object graph, lazy children, node coverage, order, collection identity and parser reuse passed\n";
	}
}

// Reusing a standalone parser must create a new owned root scope.
$file = new file();
$file->path = 'repeat.phs';
$file->content = 'function main(): int { return 1; }';
$scanner = new Tokenizer($file);
$parser = new Parser($scanner->tokenize());
$first = $parser->parse();
$second = $parser->parse();
if (count($first->scopes) !== 3 || count($second->scopes) !== 3 || $first->scopes[0] === $second->scopes[0]) {
	throw new \LogicException('Standalone parser scope ownership failed');
}
AST_Test::run();

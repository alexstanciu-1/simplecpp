<?php

namespace scpp\compiler;

require_once dirname(__DIR__) . '/boot.php';
\define('dbg', false);

final class AST_Test
{
	private const PAYLOADS = [
		'blocks' => block_structure::class,
		'functions' => function_structure::class,
		'parameters' => parameter_structure::class,
		'calls' => call_structure::class,
		'binaries' => binary_structure::class,
		'expression_statements' => expression_statement_structure::class,
		'returns' => return_structure::class,
		'bindings' => binding_structure::class,
		'array_types' => array_type_structure::class,
		'array_literals' => array_literal_structure::class,
		'indexes' => index_structure::class,
		'structs' => struct_structure::class,
		'fields' => field_structure::class,
		'field_accesses' => field_access_structure::class,
	];

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
		self::check(get_class($node) === __NAMESPACE__ . '\\' . $node->kind->name . '_node');
		$children = $node->children();
		$expected = Syntax_Nodes::child_nodes($node);
		self::check(count($children) === count($expected));
		self::check($node->has_children() === (count($children) > 0));
		foreach ($children as $position => $child) {
			self::check($child === $expected[$position]);
			self::check($child->parent() === $node && $child->child_position() === $position);
			self::check($child->prev() === ($position === 0 ? null : $children[$position - 1]));
			self::check($child->next() === ($position + 1 === count($children) ? null : $children[$position + 1]));
		}
		self::check($node->first_child() === ($children->is_empty() ? null : $children[0]));

		self::check($node->token_index >= 0 && $node->end_token_index <= count($syntax->tokens->tokens));
		self::check($node->token_index <= $node->end_token_index);
		$payload = $node->structure;
		Syntax_Nodes::validate_payload($node->kind, $payload);
		if ($payload === null) {
			return;
		}
		self::initialized($payload);
		self::check(!$payloads->contains($payload));
		$payloads->attach($payload);
		foreach (get_object_vars($payload) as $value)
		{
			if ($value instanceof ast_node) {
				self::visit($value, $syntax, $nodes, $payloads);
			}
			elseif ($value instanceof Storage)
			{
				$start = -1;
				foreach ($value as $position => $child) {
					self::check($child->token_index >= $start);
					$start = $child->token_index;
					self::visit($child, $syntax, $nodes, $payloads);
				}
			}
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
		self::initialized($syntax->root->structure->scope);
		self::check($syntax->collection->root === $syntax->root);
		self::check($syntax->collection->source === $syntax->tokens);
		$nodes = new \SplObjectStorage();
		$payloads = new \SplObjectStorage();
		self::visit($syntax->root, $syntax, $nodes, $payloads);
		foreach ($payloads as $payload)
		{
			if ($payload instanceof block_structure && $payload->scope !== $syntax->root->structure->scope)
			{
				$found = false;
				foreach ($syntax->scopes as $owned) {
					if ($owned === $payload->scope) {
						$found = true;
					}
				}
				self::check($found);
			}
		}
		foreach ($syntax->collection->entries as $position => $entry) {
			self::initialized($entry);
			self::check($entry->local_index === $position && $nodes->contains($entry->node));
		}
		return $nodes;
	}

	/** Distinguish meaningful absent syntax from incomplete required fields. */
	private static function optional_syntax(): void
	{
		$parser = new Parser(self::tokens('function f(int $a, int &$b): void { return; } $x int; $y int = 1; $x = 2; $items int[1] = []; $items[0] = 3;'));
		$result = $parser->parse();
		self::verify($result);
		$children = $result->root->structure->children;
		$function = $children[0]->structure;
		$value = $function->parameters[0]->structure;
		$reference = $function->parameters[1]->structure;
		self::check($value->mode === passing_mode::value && $value->reference_token_index === null);
		self::check($reference->mode === passing_mode::reference && $reference->reference_token_index !== null);
		self::check($result->tokens->tokens[$reference->reference_token_index]->text() === '&');
		self::check($function->body->structure->children[0]->structure->expression === null);
		$declaration = $children[1]->structure;
		self::check($declaration->type_syntax !== null && $declaration->target === null && $declaration->equals_token_index === null && $declaration->value === null);
		$initialized = $children[2]->structure;
		self::check($initialized->type_syntax !== null && $initialized->target === null && $initialized->equals_token_index !== null && $initialized->value !== null);
		$assignment = $children[3]->structure;
		self::check($assignment->type_syntax === null && $assignment->target === null && $assignment->equals_token_index !== null && $assignment->value !== null);
		self::check($children[4]->structure->value->structure->elements->is_empty());
		$indexed = $children[5]->structure;
		self::check($indexed->type_syntax === null && $indexed->target !== null && $indexed->equals_token_index !== null && $indexed->value !== null);
	}

	/** Failed files must not publish declarations to a caller-provided scope. */
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
		self::check($failed && serialize($scope) === $before);
		$parser->init(self::tokens('function next(): void { return; }'), $scope);
		$next = $parser->parse();
		self::verify($next);
		self::check($next->root->structure->scope === $scope);
		self::check($next->scopes[0]->parent_scope() === $scope);
		self::check(count($scope->functions_named('kept')) === 1 && count($scope->functions_named('next')) === 1);
		self::check((count($scope->functions_named('broken')) === 0) && (count($scope->types_named('Pending')) === 0));
		// Omitting the target on re-init must clear the previous external scope.
		$parser->init(self::tokens(''));
		$standalone = $parser->parse();
		self::verify($standalone);
		self::check($standalone->root->structure->scope !== $scope);
		self::check($standalone->scopes[0]->parent_scope() === null);
	}

	/** Finalization publishes once; rejected later operations must not duplicate indexes. */
	private static function collector_finalization(): void
	{
		$tokens = self::tokens('$x int;');
		$parser = new Parser($tokens);
		$syntax = $parser->parse();
		$node = $syntax->root->structure->children[0];
		$scope = new scope();
		$collector = new Symbol_Collector($tokens);
		$position = $collector->record($node, 0, collected_name_kind::variable_declaration, $scope, 'canonical_name');
		self::check(!$scope->has_variables());
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
		self::check($rejections === 2 && serialize($result) === $before && count($scope->variables_named('canonical_name')) === 1);
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
			if ($node->structure !== null) {
				$types[get_class($node->structure)] = true;
			}
		}
		foreach (self::PAYLOADS as $field => $type) {
			self::check($field === 'binaries' ? !isset($types[$type]) : isset($types[$type]));
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
		self::check(count(self::verify($empty)) === 1);
		self::check($empty->root->structure->children->is_empty());
		echo "AST: object graph, child lists, payload coverage, order, collection identity and parser reuse passed\n";
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
if (count($first->scopes) !== 2 || count($second->scopes) !== 2 || $first->scopes[0] === $second->scopes[0]) {
	throw new \LogicException('Standalone parser scope ownership failed');
}
try {
	Syntax_Nodes::validate_payload(node_kind::integer_literal, new binary_structure());
	throw new \RuntimeException('Invalid payload accepted');
}
catch (\LogicException $expected) {
}
try {
	Syntax_Nodes::validate_payload(node_kind::block, null);
	throw new \RuntimeException('Missing payload accepted');
}
catch (\LogicException $expected) {
}
AST_Test::run();

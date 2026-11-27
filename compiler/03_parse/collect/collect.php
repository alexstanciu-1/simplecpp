<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Collects source-order body indexes; declaration registration/publication is delegated to Scope_Collection. */
final class Body_Collection
{
	/** PROPERTIES */
	/** @var ast_local_declaration[] Keyed by statement position; native non-owning references to body syntax. */
	private array $locals /** array<uint32,ast_local_declaration> */ = [];
	/** @var ast_local_binding[] Prior local heads; native non-owning name index, not another duplicate owner. */
	private array $local_names /** array<string,ast_local_binding> */ = [];
	/** Native owning; body-local collection worker. */
	private readonly Scope_Collection $declarations;
	/** Native non-owning parse-time lexical parent; null on function/entry body. */
	private readonly ?Body_Collection $parent;
	/** Native non-owning ID allocator shared by one function's blocks. */
	private readonly Body_Collection $storage_owner;
	private int $next_id /** uint32 */;
	/** Native owning scope until attached to the completed block. */
	private readonly scope $scope;
	/** @var ast_name_expression[] Native non-owning expression references. */
	private array $references /** list<ast_name_expression> */ = [];

	/** CONSTRUCTOR */
	/**
	 * Seed body scope with signature-owned parameters before local statements.
	 * @param ast_parameter_declaration[] $parameters
	 */
	public function __construct(scope $parent, array $parameters /** list<ast_parameter_declaration> */ = [], ?Body_Collection $parent_collector = null)
	{
		$this->scope = new scope($parent);
		$this->declarations = new Scope_Collection($this->scope);
		$this->parent = $parent_collector;
		$this->storage_owner = $parent_collector?->storage_owner ?? $this;
		$this->next_id = count($parameters);
		foreach ($parameters as $parameter) {
			$this->declarations->declare($parameter);
			$this->local_names[$parameter->declaration_key()] ??= $parameter;
		}
	}

	/** METHODS */
	/** Classify a PHP bare write using only declarations already collected in this body. */
	public function has_local(string $name): bool
	{
		return isset($this->local_names[$name]) || ($this->parent?->has_local($name) ?? false);
	}

	/** Establish a binding in its declaring scope before subsequent header items or statements are parsed. */
	public function declare_local(ast_local_declaration $local): void
	{
		$this->declarations->declare($local);
		$this->local_names[$local->declaration_key()] ??= $local;
		$local->identify($this->storage_owner->next_id++);
	}

	/** Record one completed statement during parsing, without lookup, typing or publication outside its source. */
	public function statement(ast_statement $statement, int $position /** uint32 */): void
	{
		if ($statement instanceof ast_rethrow_statement || $statement instanceof ast_try_statement || $statement instanceof ast_block_statement) return;
		if ($statement instanceof ast_yield_statement) {
			$this->expression($statement->value);
			if ($statement->key !== null) $this->expression($statement->key);
			return;
		}
		if ($statement instanceof ast_property_update || $statement instanceof ast_index_update) {
			$this->expression($statement->target);
			return;
		}
		if ($statement instanceof ast_loop_transfer || $statement instanceof ast_reset_statement || $statement instanceof ast_update_statement) {
			return;
		}
		if ($statement instanceof ast_local_declaration) {
			$this->declare_local($statement);
			$this->locals[$position] = $statement;
			$value = $statement->initializer;
		}
		elseif ($statement instanceof ast_field_assignment) {
			$this->expression($statement->target);
			$value = $statement->value;
		}
		elseif (($statement instanceof ast_append_statement) || ($statement instanceof ast_index_assignment)) {
			$this->expression($statement->receiver);
			if ($statement instanceof ast_index_assignment) {
				$this->expression($statement->index);
			}
			$value = $statement->value;
		}
		elseif ($statement instanceof ast_echo_statement) {
			foreach ($statement->values as $value) {
				$this->expression($value);
			}
			return;
		}
		elseif ($statement instanceof ast_property_compound || $statement instanceof ast_index_compound) {
			$this->expression($statement->target);
			$value = $statement->value;
		}
		elseif (($statement instanceof ast_return_statement) || ($statement instanceof ast_assignment_statement)
			|| ($statement instanceof ast_delay_statement) || ($statement instanceof ast_await_statement) || ($statement instanceof ast_take_statement) || ($statement instanceof ast_call_statement) || ($statement instanceof ast_base_statement)) {
			$value = $statement->value;
		}
		elseif ($statement instanceof ast_throw_statement) {
			$value = $statement->operation;
		}
		elseif ($statement instanceof ast_foreach_statement) {
			$value = $statement->source_value;
		}
		elseif ($statement instanceof ast_loop_statement) {
			if ($statement instanceof ast_for_statement) {
				foreach ($statement->initializers as $item) {
					if ($item instanceof ast_local_declaration) $this->expression($item->initializer);
					else $this->statement($item, 0);
				}
				foreach ($statement->conditions as $test) $this->expression($test);
				foreach ($statement->updates as $item) $this->statement($item, 0);
				return;
			}
			$value = $statement->condition;
		}
		elseif ($statement instanceof ast_branch_statement) {
			if ($statement instanceof ast_switch_statement) $this->expression($statement->subject);
			foreach ($statement->arms as $arm) {
				if ($arm->condition !== null) {
					$this->expression($arm->condition);
				}
			}
			return;
		}
		else {
			throw new \LogicException('Unhandled statement collection');
		}
		if ($value !== null) {
			$this->expression($value);
		}
	}

	/** Collect a produced header name without inventing an initializer or scanning its body. */
	public function produced_binding(string $name, int $start /** uint32 */, bool $declare, bool $writable = false): ast_produced_binding
	{
		$id = $this->storage_owner->next_id++;
		$binding = $writable ? new ast_writable_element($name, $start, $id) : new ast_produced_binding($name, $start, $id);
		if ($declare) {
			$this->declarations->declare($binding);
			$this->local_names[$name] = $binding;
		}
		return $binding;
	}

	public function child(): Body_Collection
	{
		return new Body_Collection($this->scope, [], $this);
	}

	/** Publish a header scope whose syntax is owned by a for node rather than a statement block. */
	public function publish_scope(): scope
	{
		return $this->declarations->publish();
	}

	/** $start/$end: token positions. @param ast_statement[] $statements */
	public function block(int $start /** uint32 */, int $end /** uint32 */, array $statements /** list<ast_statement> */): ast_block
	{
		return new ast_block($start, $end, $statements, $this->locals, $this->references, $this->declarations->publish());
	}

	/** Collect nested argument references without resolving declarations or inspecting foreign bodies. */
	private function expression(ast_expression $value): void
	{
		if ($value instanceof ast_take_expression) {
			$this->expression($value->input);
			foreach ($value->outputs as $output) if ($output instanceof ast_name_expression) $this->expression($output);
			return;
		}
		if ($value instanceof ast_condition_assignment) {
			$this->expression($value->target);
			$this->expression($value->value);
			return;
		}
		if ($value instanceof ast_ternary_expression) {
			$this->expression($value->condition);
			if ($value->when_true !== null) $this->expression($value->when_true);
			$this->expression($value->when_false);
			return;
		}
		if ($value instanceof ast_match_expression) {
			$this->expression($value->subject);
			foreach ($value->arms as $arm) {
				foreach ($arm->labels as $label) $this->expression($label);
				$this->expression($arm->result);
			}
			return;
		}
		if ($value instanceof ast_keyed_collection_expression) {
			foreach ($value->entries as $entry) {
				$this->expression($entry->key);
				$this->expression($entry->value);
			}
			return;
		}
		if ($value instanceof ast_collection_expression) {
			foreach ($value->elements as $element) $this->expression($element);
			return;
		}
		if ($value instanceof ast_interpolated_string) {
			foreach ($value->parts as $part) {
				$this->expression($part);
			}
			return;
		}
		if (($value instanceof ast_await_expression) || ($value instanceof ast_object_access_expression) || ($value instanceof ast_ownership_expression) || ($value instanceof ast_isset_expression) || ($value instanceof ast_not_expression) || ($value instanceof ast_cast_expression)
			|| ($value instanceof ast_unary_expression) || ($value instanceof ast_throw_expression)) {
			$this->expression($value->operand);
		}
		if ($value instanceof ast_field_expression || $value instanceof ast_qualified_data) {
			$this->expression($value->receiver);
		}
		if ($value instanceof ast_index_expression) {
			$this->expression($value->receiver);
			$this->expression($value->index);
		}
		if ($value instanceof ast_comparison_chain) {
			foreach ($value->links as $link) $this->expression($link);
			return;
		}
		if ($value instanceof ast_binary_expression) {
			if (!($value instanceof ast_continued_comparison)) $this->expression($value->left);
			$this->expression($value->right);
		}
		if ($value instanceof ast_call_expression || $value instanceof ast_construction_expression) {
			if ($value instanceof ast_call_expression && $value->receiver !== null) {
				$this->expression($value->receiver);
			}
			foreach ($value->arguments as $argument) {
				$this->expression($argument);
			}
		}
		if ($value instanceof ast_lambda_expression) {
			foreach ($value->captures as $capture) $this->references[] = $capture->initializer;
		}
		if ($value instanceof ast_name_expression) {
			$this->references[] = $value;
		}
	}

	/**
	 * $start: token position.
	 * $end: exclusive token position.
	 *
	 * @param ast_statement[] $statements
	 */
	public function body(int $start /** uint32 */, int $end /** uint32 */,
		array $statements /** list<ast_statement> */, bool $signature_shared = false): ast_function_body
	{
		// The authored scope owns parameter duplicate links. A private body only indexes those parameters.
		$scope = $signature_shared ? $this->declarations->publish_references() : $this->declarations->publish();
		return new ast_function_body($start, $end, $statements, $this->locals, $this->references, $scope);
	}
}

/** Parse-time lexical requirements for one PHP arrow, anchored at each name's first use. */
final class Arrow_Captures
{
	/** @var ast_closure_capture[] First-use order is the environment's field order. */
	private array $captures /** array<string,ast_closure_capture> */ = [];
	/** @var bool[] */
	private array $parameters /** array<string,bool> */ = [];

	/** @param ast_parameter_declaration[] $parameters */
	public function __construct(array $parameters /** list<ast_parameter_declaration> */, private readonly ?self $outer, private readonly ?Body_Collection $lexical)
	{
		foreach ($parameters as $parameter) $this->parameters[$parameter->declaration_key()] = true;
	}

	/** Supply the binding through each enclosing arrow until a parameter or lexical local provides it. */
	public function read(string $name, int $start /** uint32 */, ?int $string_offset /** uint32|null */ = null): bool
	{
		if (isset($this->parameters[$name]) || isset($this->captures[$name])) return true;
		if (!($this->outer?->read($name, $start, $string_offset) ?? $this->lexical?->has_local($name))) return false;
		$capture = new ast_closure_capture($name, $start, $string_offset);
		$capture->identify(count($this->parameters) + count($this->captures));
		$this->captures[$name] = $capture;
		return true;
	}

	/** @return ast_closure_capture[] */
	public function captures(): array /** list<ast_closure_capture> */
	{
		return array_values($this->captures);
	}
}

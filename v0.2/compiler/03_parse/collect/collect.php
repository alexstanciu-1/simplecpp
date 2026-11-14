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

	/** Record one completed statement during parsing, without lookup, typing or publication outside its source. */
	public function statement(ast_statement $statement, int $position /** uint32 */): void
	{
		if ($statement instanceof ast_property_update) {
			$this->expression($statement->target);
			return;
		}
		if (($statement instanceof ast_break_statement) || ($statement instanceof ast_continue_statement) || ($statement instanceof ast_update_statement)) {
			return;
		}
		if ($statement instanceof ast_local_declaration) {
			$this->declarations->declare($statement);
			$this->local_names[$statement->declaration_key()] ??= $statement;
			$statement->identify($this->storage_owner->next_id++);
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
		elseif ($statement instanceof ast_property_compound) {
			$this->expression($statement->target);
			$value = $statement->value;
		}
		elseif (($statement instanceof ast_return_statement) || ($statement instanceof ast_assignment_statement)
			|| ($statement instanceof ast_call_statement) || ($statement instanceof ast_base_statement)) {
			$value = $statement->value;
		}
		elseif ($statement instanceof ast_loop_statement) {
			if ($statement instanceof ast_for_statement) {
				if (!($statement->initializer instanceof ast_update_statement)) {
					$this->expression($statement->initializer instanceof ast_local_declaration
						? $statement->initializer->initializer : $statement->initializer->value);
				}
				$this->expression($statement->condition);
				if ($statement->update instanceof ast_property_update) {
					$this->expression($statement->update->target);
				} elseif ($statement->update instanceof ast_assignment_statement) {
					$this->expression($statement->update->value);
				}
				return;
			}
			$value = $statement->condition;
		}
		elseif ($statement instanceof ast_if_statement) {
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
		if ($value instanceof ast_interpolated_string) {
			foreach ($value->parts as $part) {
				$this->expression($part);
			}
			return;
		}
		if (($value instanceof ast_ownership_expression) || ($value instanceof ast_isset_expression) || ($value instanceof ast_not_expression) || ($value instanceof ast_cast_expression)
			|| ($value instanceof ast_unary_expression)) {
			$this->expression($value->operand);
		}
		if ($value instanceof ast_field_expression) {
			$this->expression($value->receiver);
		}
		if ($value instanceof ast_index_expression) {
			$this->expression($value->receiver);
			$this->expression($value->index);
		}
		if ($value instanceof ast_binary_expression) {
			$this->expression($value->left);
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
		array $statements /** list<ast_statement> */): ast_function_body
	{
		return new ast_function_body($start, $end, $statements, $this->locals, $this->references, $this->declarations->publish());
	}
}

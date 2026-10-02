<?php

/* Typed syntax model; workers own compilation algorithms and traversal. */
namespace scpp\compiler;

/** Typed inspection cursor; current() requires valid(), and keys are traversal positions. */
interface child_iterator_i extends \Iterator
{
	public function current(): ast_node;
	public function key(): int;
	public function next(): void;
	public function rewind(): void;
	public function valid(): bool;
}


/** Shared forward-only cursor protocol; concrete cursors supply only the next child. */
abstract class children_iterator implements child_iterator_i
{
	private bool $started = false;
	private int $position = 0;
	private ?ast_node $current_child = null;

	/** Null means exhausted; concrete cursors skip absent optional fields themselves. */
	protected abstract function read_next(): ?ast_node;

	public function current(): ast_node
	{
		if (!$this->valid()) {
			throw new \LogicException('Child iterator has no current node');
		}
		return $this->current_child;
	}

	public function key(): int
	{
		if (!$this->valid()) {
			throw new \LogicException('Child iterator has no current key');
		}
		return $this->position;
	}

	/** Advance once; repeated advances after exhaustion do nothing. */
	public function next(): void
	{
		if ($this->valid()) {
			$this->current_child = $this->read_next();
			$this->position++;
		}
	}

	/** Foreach initializes the cursor; restarting an advanced cursor is explicit misuse. */
	public function rewind(): void
	{
		if ($this->position !== 0) {
			throw new \LogicException('Create a fresh child iterator to restart traversal');
		}
		$this->valid();
	}

	/** Defer the first source read until iteration actually starts. */
	public function valid(): bool
	{
		if (!$this->started) {
			$this->current_child = $this->read_next();
			$this->started = true;
		}
		return $this->current_child !== null;
	}
}

/** Inspect a conditional through its named owning edges without copying children. */
final class if_children_iterator extends children_iterator
{
	private if_node $source;
	private int $next_field = 0;

	public function __construct(if_node $source)
	{
		$this->source = $source;
	}

	/** An absent condition is valid only for the final else arm. */
	protected function read_next(): ?ast_node
	{
		if ($this->next_field === 0) {
			$this->next_field = 1;
			if ($this->source->condition !== null) {
				return $this->source->condition;
			}
		}
		if ($this->next_field === 1) {
			$this->next_field = 2;
			return $this->source->body;
		}
		if ($this->next_field === 2) {
			$this->next_field = 3;
			return $this->source->next_arm;
		}
		return null;
	}
}

/** Two named loop edges in source order, without a temporary child collection. */
final class loop_pair_children_iterator extends children_iterator
{
	private ast_node $first;
	private ast_node $second;
	private int $position = 0;

	public function __construct(ast_node $first, ast_node $second)
	{
		$this->first = $first;
		$this->second = $second;
	}

	/** Inspect source order without a temporary child collection. */
	protected function read_next(): ?ast_node
	{
		if ($this->position === 0) {
			$this->position = 1;
			return $this->first;
		}
		if ($this->position === 1) {
			$this->position = 2;
			return $this->second;
		}
		return null;
	}
}

/** Independent inspection cursors borrow the three typed for-clause collections. */
final class for_children_iterator extends children_iterator
{
	private for_node $source;
	private Storage_Cursor $initialization /** Storage_Cursor<statement_node> */;
	private Storage_Cursor $conditions /** Storage_Cursor<expression_node> */;
	private Storage_Cursor $updates /** Storage_Cursor<expression_node> */;
	private bool $body_read = false;

	public function __construct(for_node $source)
	{
		$this->source = $source;
		$this->initialization = new Storage_Cursor /** Storage_Cursor<statement_node> */($source->initialization);
		$this->conditions = new Storage_Cursor /** Storage_Cursor<expression_node> */($source->conditions);
		$this->updates = new Storage_Cursor /** Storage_Cursor<expression_node> */($source->updates);
	}

	/** Exhaust each existing collection before moving to the next named edge. */
	protected function read_next(): ?ast_node
	{
		$initialization /** Storage_Cursor<statement_node> */ = $this->initialization;
		if ($initialization->valid()) {
			$statement = $initialization->current();
			$initialization->next();
			return $statement;
		}
		$conditions /** Storage_Cursor<expression_node> */ = $this->conditions;
		if ($conditions->valid()) {
			$condition = $conditions->current();
			$conditions->next();
			return $condition;
		}
		$updates /** Storage_Cursor<expression_node> */ = $this->updates;
		if ($updates->valid()) {
			$update = $updates->current();
			$updates->next();
			return $update;
		}
		if (!$this->body_read) {
			$this->body_read = true;
			return $this->source->body;
		}
		return null;
	}
}

/** Switch syntax exposes its selector before its existing ordered group storage. */
final class switch_children_iterator extends children_iterator
{
	private switch_node $source;
	private Storage_Cursor $groups /** Storage_Cursor<switch_case_group> */;
	private bool $selector_read = false;

	public function __construct(switch_node $source)
	{
		$this->source = $source;
		$this->groups = new Storage_Cursor /** Storage_Cursor<switch_case_group> */($source->groups);
	}

	protected function read_next(): ?ast_node
	{
		if (!$this->selector_read) {
			$this->selector_read = true;
			return $this->source->selector;
		}
		$groups /** Storage_Cursor<switch_case_group> */ = $this->groups;
		if (!$groups->valid()) { return null; }
		$group = $groups->current();
		$groups->next();
		return $group;
	}
}

/** A group's labels precede its one shared statement body. */
final class switch_group_children_iterator extends children_iterator
{
	private switch_case_group $source;
	private Storage_Cursor $labels /** Storage_Cursor<switch_label> */;
	private bool $body_read = false;

	public function __construct(switch_case_group $source)
	{
		$this->source = $source;
		$this->labels = new Storage_Cursor /** Storage_Cursor<switch_label> */($source->labels);
	}

	protected function read_next(): ?ast_node
	{
		$labels /** Storage_Cursor<switch_label> */ = $this->labels;
		if ($labels->valid()) {
			$label = $labels->current();
			$labels->next();
			return $label;
		}
		if ($this->body_read) { return null; }
		$this->body_read = true;
		return $this->source->body;
	}
}

final class switch_label_children_iterator extends children_iterator
{
	private switch_label $source;
	private bool $value_read = false;

	public function __construct(switch_label $source) { $this->source = $source; }

	protected function read_next(): ?ast_node
	{
		if ($this->value_read) { return null; }
		$this->value_read = true;
		return $this->source->value;
	}
}

/** Leaves need no source owner and have no cursor state. */
final class empty_children_iterator implements child_iterator_i
{
	public function current(): ast_node
	{
		throw new \LogicException('Empty child iterator has no current node');
	}

	public function key(): int
	{
		throw new \LogicException('Empty child iterator has no current key');
	}

	public function next(): void
	{
	}

	public function rewind(): void
	{
	}

	public function valid(): bool
	{
		return false;
	}
}

/** Retain a typed Storage cursor; widening applies to yielded handles, never membership. */
final class storage_children_iterator extends children_iterator
{
	private Storage_Cursor $cursor /** Storage_Cursor<ast_node> */;
	private bool $cursor_started = false;

	public function __construct(Storage_Cursor $cursor /** Storage_Cursor<ast_node> */)
	{
		$this->cursor = $cursor;
	}

	/** Advance the existing collection cursor and preserve holes without copying a list. */
	protected function read_next(): ?ast_node
	{
		$cursor /** Storage_Cursor<ast_node> */ = $this->cursor;
		if ($this->cursor_started) {
			$cursor->next();
		}
		else {
			$this->cursor_started = true;
		}
		if (!$cursor->valid()) {
			return null;
		}
		return $cursor->current();
	}
}

/** Retain the source and visit its named fields in grammar order. */
final class parameter_children_iterator extends children_iterator
{
	private parameter_node $source;
	private bool $consumed = false;

	public function __construct(parameter_node $source)
	{
		$this->source = $source;
	}

	/** Read the next named field only when requested. */
	protected function read_next(): ?ast_node
	{
		if ($this->consumed) {
			return null;
		}
		$this->consumed = true;
		return $this->source->type_syntax;
	}
}

/** Retain the source and visit its named fields in grammar order. */
final class binary_expression_children_iterator extends children_iterator
{
	private binary_expression_node $source;
	private int $next_field = 0;

	public function __construct(binary_expression_node $source)
	{
		$this->source = $source;
	}

	/** Read the next named field only when requested. */
	protected function read_next(): ?ast_node
	{
		switch ($this->next_field)
		{
			case 0:
				$this->next_field = 1;
				return $this->source->left;
			case 1:
				$this->next_field = 2;
				return $this->source->right;
		}
		return null;
	}
}

/** Retain explicit target syntax followed by the converted operand. */
final class cast_expression_children_iterator extends children_iterator
{
	private cast_expression_node $source;
	private int $next_field = 0;

	public function __construct(cast_expression_node $source)
	{
		$this->source = $source;
	}

	protected function read_next(): ?ast_node
	{
		switch ($this->next_field)
		{
			case 0:
				$this->next_field = 1;
				return $this->source->target_type;
			case 1:
				$this->next_field = 2;
				return $this->source->operand;
		}
		return null;
	}
}

/** Retain the source and visit its named fields in grammar order. */
final class assignment_expression_children_iterator extends children_iterator
{
	private assignment_expression_node $source;
	private int $next_field = 0;

	public function __construct(assignment_expression_node $source)
	{
		$this->source = $source;
	}

	/** Read the next named field only when requested. */
	protected function read_next(): ?ast_node
	{
		switch ($this->next_field)
		{
			case 0:
				$this->next_field = 1;
				return $this->source->target;
			case 1:
				$this->next_field = 2;
				return $this->source->value;
		}
		return null;
	}
}

/** Retain the source and visit its named fields in grammar order. */
final class expression_statement_children_iterator extends children_iterator
{
	private expression_statement_node $source;
	private bool $consumed = false;

	public function __construct(expression_statement_node $source)
	{
		$this->source = $source;
	}

	/** Read the next named field only when requested. */
	protected function read_next(): ?ast_node
	{
		if ($this->consumed) {
			return null;
		}
		$this->consumed = true;
		return $this->source->expression;
	}
}

/** Retain the source and visit its named fields in grammar order. */
final class return_children_iterator extends children_iterator
{
	private return_node $source;
	private bool $consumed = false;

	public function __construct(return_node $source)
	{
		$this->source = $source;
	}

	/** Read the next named field only when requested. */
	protected function read_next(): ?ast_node
	{
		if ($this->consumed) {
			return null;
		}
		$this->consumed = true;
		return $this->source->expression;
	}
}

/** Retain the source and visit its named fields in grammar order. */
final class variable_declaration_children_iterator extends children_iterator
{
	private variable_declaration_node $source;
	/** Next named-field position; optional fields still occupy their grammar slot. */
	private int $next_field = 0;

	public function __construct(variable_declaration_node $source)
	{
		$this->source = $source;
	}

	/** Skip absent optional children without treating them as the end of traversal. */
	protected function read_next(): ?ast_node
	{
		while ($this->next_field < 2)
		{
			$field = $this->next_field;
			$this->next_field++;
			$child /** nullable<ast_node> */ = null;
			switch ($field)
			{
				case 0:
					$child = $this->source->type_syntax;
					break;
				case 1:
					$child = $this->source->initializer;
					break;
			}
			if ($child !== null) {
				return $child;
			}
		}
		return null;
	}
}

/** Retain the source and visit its named fields in grammar order. */
final class array_type_children_iterator extends children_iterator
{
	private array_type_node $source;
	private int $next_field = 0;

	public function __construct(array_type_node $source)
	{
		$this->source = $source;
	}

	/** Read the next named field only when requested. */
	protected function read_next(): ?ast_node
	{
		switch ($this->next_field)
		{
			case 0:
				$this->next_field = 1;
				return $this->source->element_type;
			case 1:
				$this->next_field = 2;
				return $this->source->count;
		}
		return null;
	}
}

/** Retain the constructor name followed by its ordered type arguments. */
final class template_application_type_children_iterator extends children_iterator
{
	private template_application_type_node $source;
	private int $next_child = 0;

	public function __construct(template_application_type_node $source)
	{
		$this->source = $source;
	}

	protected function read_next(): ?ast_node
	{
		if ($this->next_child === 0) {
			$this->next_child++;
			return $this->source->definition;
		}
		$argument_position = $this->next_child - 1;
		$arguments /** Storage<type_node> */ = $this->source->arguments;
		if ($argument_position >= q_count($arguments)) {
			return null;
		}
		$this->next_child++;
		return $arguments[$argument_position];
	}
}

/** Retain the single type operand owned by a source type-use modifier. */
final class type_use_modifier_children_iterator extends children_iterator
{
	private type_use_modifier_node $source;
	private bool $read = false;

	public function __construct(type_use_modifier_node $source)
	{
		$this->source = $source;
	}

	protected function read_next(): ?ast_node
	{
		if ($this->read) {
			return null;
		}
		$this->read = true;
		return $this->source->operand;
	}
}

/** Retain the source and visit its named fields in grammar order. */
final class index_children_iterator extends children_iterator
{
	private index_node $source;
	private int $next_field = 0;

	public function __construct(index_node $source)
	{
		$this->source = $source;
	}

	/** Read the next named field only when requested. */
	protected function read_next(): ?ast_node
	{
		switch ($this->next_field)
		{
			case 0:
				$this->next_field = 1;
				return $this->source->base;
			case 1:
				$this->next_field = 2;
				return $this->source->index;
		}
		return null;
	}
}

/** Retain the source and visit its named fields in grammar order. */
final class field_children_iterator extends children_iterator
{
	private field_node $source;
	private bool $consumed = false;

	public function __construct(field_node $source)
	{
		$this->source = $source;
	}

	/** Read the next named field only when requested. */
	protected function read_next(): ?ast_node
	{
		if ($this->consumed) {
			return null;
		}
		$this->consumed = true;
		return $this->source->type_syntax;
	}
}

/** Retain the source and visit its named fields in grammar order. */
final class field_access_children_iterator extends children_iterator
{
	private field_access_node $source;
	private bool $consumed = false;

	public function __construct(field_access_node $source)
	{
		$this->source = $source;
	}

	/** Read the next named field only when requested. */
	protected function read_next(): ?ast_node
	{
		if ($this->consumed) {
			return null;
		}
		$this->consumed = true;
		return $this->source->base;
	}
}

enum file_children_stage
{
	case declarations;
	case body;
	case finished;
}

/** Independent source owner plus explicit grammar stage and collection progress. */
final class file_children_iterator extends children_iterator
{
	private file_node $source;
	private file_children_stage $stage = file_children_stage::declarations;
	private ?storage_children_iterator $declarations_cursor = null;

	public function __construct(file_node $source)
	{
		$this->source = $source;
	}

	/** Resume the current collection or named field; exhausted collections move to the next stage. */
	protected function read_next(): ?ast_node
	{
		while ($this->stage !== file_children_stage::finished)
		{
			switch ($this->stage)
			{
				case file_children_stage::declarations:
					if ($this->declarations_cursor === null) {
						$this->declarations_cursor = new storage_children_iterator(new Storage_Cursor /** Storage_Cursor<ast_node> */($this->source->declarations));
					}
					else {
						$this->declarations_cursor->next();
					}
					if ($this->declarations_cursor->valid()) {
						return $this->declarations_cursor->current();
					}
					$this->declarations_cursor = null;
					$this->stage = file_children_stage::body;
					break;
				case file_children_stage::body:
					$this->stage = file_children_stage::finished;
					return $this->source->body;
			}
		}
		return null;
	}
}

enum call_children_stage
{
	case template_arguments;
	case arguments;
	case finished;
}

/** Independent source owner plus explicit grammar stage and collection progress. */
final class call_children_iterator extends children_iterator
{
	private call_node $source;
	private call_children_stage $stage = call_children_stage::template_arguments;
	private ?storage_children_iterator $template_arguments_cursor = null;
	private ?storage_children_iterator $arguments_cursor = null;

	public function __construct(call_node $source)
	{
		$this->source = $source;
	}

	/** Resume the current collection or named field; exhausted collections move to the next stage. */
	protected function read_next(): ?ast_node
	{
		while ($this->stage !== call_children_stage::finished)
		{
			switch ($this->stage)
			{
				case call_children_stage::template_arguments:
					if ($this->template_arguments_cursor === null) {
						$this->template_arguments_cursor = new storage_children_iterator(new Storage_Cursor /** Storage_Cursor<ast_node> */($this->source->template_arguments));
					}
					else {
						$this->template_arguments_cursor->next();
					}
					if ($this->template_arguments_cursor->valid()) {
						return $this->template_arguments_cursor->current();
					}
					$this->template_arguments_cursor = null;
					$this->stage = call_children_stage::arguments;
					break;
				case call_children_stage::arguments:
					if ($this->arguments_cursor === null) {
						$this->arguments_cursor = new storage_children_iterator(new Storage_Cursor /** Storage_Cursor<ast_node> */($this->source->arguments));
					}
					else {
						$this->arguments_cursor->next();
					}
					if ($this->arguments_cursor->valid()) {
						return $this->arguments_cursor->current();
					}
					$this->arguments_cursor = null;
					$this->stage = call_children_stage::finished;
					break;
			}
		}
		return null;
	}
}

enum function_children_stage
{
	case parameters;
	case return_type;
	case body;
	case finished;
}

/** Independent source owner plus explicit grammar stage and collection progress. */
final class function_children_iterator extends children_iterator
{
	private function_node $source;
	private function_children_stage $stage = function_children_stage::parameters;
	private ?storage_children_iterator $parameters_cursor = null;

	public function __construct(function_node $source)
	{
		$this->source = $source;
	}

	/** Resume the current collection or named field; exhausted collections move to the next stage. */
	protected function read_next(): ?ast_node
	{
		while ($this->stage !== function_children_stage::finished)
		{
			switch ($this->stage)
			{
				case function_children_stage::parameters:
					if ($this->parameters_cursor === null) {
						$this->parameters_cursor = new storage_children_iterator(new Storage_Cursor /** Storage_Cursor<ast_node> */($this->source->parameters));
					}
					else {
						$this->parameters_cursor->next();
					}
					if ($this->parameters_cursor->valid()) {
						return $this->parameters_cursor->current();
					}
					$this->parameters_cursor = null;
					$this->stage = function_children_stage::return_type;
					break;
				case function_children_stage::return_type:
					$this->stage = function_children_stage::body;
					return $this->source->return_type;
				case function_children_stage::body:
					$this->stage = function_children_stage::finished;
					return $this->source->body;
			}
		}
		return null;
	}
}

/** Inspect the one owned unary operand lazily. */
final class unary_expression_children_iterator extends children_iterator
{
	private unary_expression_node $source;
	private bool $consumed = false;

	public function __construct(unary_expression_node $source)
	{
		$this->source = $source;
	}

	/** Read the next named field only when requested. */
	protected function read_next(): ?ast_node
	{
		if ($this->consumed) {
			return null;
		}
		$this->consumed = true;
		return $this->source->operand;
	}
}

/** Inspect the one owned mutation target lazily. */
final class mutation_expression_children_iterator extends children_iterator
{
	private mutation_expression_node $source;
	private bool $consumed = false;

	public function __construct(mutation_expression_node $source)
	{
		$this->source = $source;
	}

	/** Read the next named field only when requested. */
	protected function read_next(): ?ast_node
	{
		if ($this->consumed) {
			return null;
		}
		$this->consumed = true;
		return $this->source->target;
	}
}

/** Inspect the compound target and RHS in source order. */
final class compound_assignment_expression_children_iterator extends children_iterator
{
	private compound_assignment_expression_node $source;
	private int $next_field = 0;

	public function __construct(compound_assignment_expression_node $source)
	{
		$this->source = $source;
	}

	/** Read the next named field only when requested. */
	protected function read_next(): ?ast_node
	{
		switch ($this->next_field)
		{
			case 0:
				$this->next_field = 1;
				return $this->source->target;
			case 1:
				$this->next_field = 2;
				return $this->source->value;
		}
		return null;
	}
}

final class interpolation_value_children_iterator extends children_iterator
{
	private interpolation_value_node $source;
	private bool $consumed = false;

	public function __construct(interpolation_value_node $source)
	{
		$this->source = $source;
	}

	/** Read the next named field only when requested. */
	protected function read_next(): ?ast_node
	{
		if ($this->consumed) {
			return null;
		}
		$this->consumed = true;
		return $this->source->expression;
	}
}

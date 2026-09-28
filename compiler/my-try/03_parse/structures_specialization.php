<?php

/*
 * Role: specialized syntax, prepared facts, child access and operation forwarding.
 * Used by: Parser, Syntax_Nodes, File_Preparation, CPP_Generator and experimental LLVM consumers.
 * Flow: ast_node.payload() owns extra data; named children retain aliases of linked nodes.
 */
namespace scpp\compiler;

/** Nodes with no additional syntax fields still have a concrete specialization. */
final class identifier_structure extends unsupported_node_structure {
	use Collected_Occurrence;

	/** Observer of the canonical entry owned by collected_file.entries. */
	private ?collected_name $collected_occurrence /** weak<collected_name> */ = null;
	/** Attachment is permanent even if the native weak observer later expires. */
	private bool $occurrence_attached = false;
}

final class empty_node_structure extends unsupported_node_structure {
}

/** Specialized facts are attached by preparation and cleared locally. */
final class integer_literal_structure extends expression_node_structure
{
	use Preparation_Facts;

	/** @ownership owner */
	private ?prepared_integer_literal $prepared_facts = null;

	public function prepare_expression(ast_node $node, preparation_context $context): prepared_expression
	{
		$facts = File_Preparation::prepare_integer($node, $context);
		$this->set_preparation($facts);
		return $facts;
	}

	public function generate_cpp_expression(ast_node $node, cpp_generation_context $context): string
	{
		return CPP_Generator::generate_integer($this->require_preparation(), $context);
	}

	public function require_preparation(): prepared_integer_literal
	{
		return object_cast($this->prepared_facts, prepared_integer_literal::class);
	}
}

/** Specialized facts are attached by preparation and cleared locally. */
final class float_literal_structure extends expression_node_structure
{
	use Preparation_Facts;

	/** @ownership owner */
	private ?prepared_float_literal $prepared_facts = null;

	public function prepare_expression(ast_node $node, preparation_context $context): prepared_expression
	{
		$facts = File_Preparation::prepare_float($node, $context);
		$this->set_preparation($facts);
		return $facts;
	}

	public function generate_cpp_expression(ast_node $node, cpp_generation_context $context): string
	{
		return CPP_Generator::generate_float($this->require_preparation(), $context);
	}

	public function require_preparation(): prepared_float_literal
	{
		return object_cast($this->prepared_facts, prepared_float_literal::class);
	}
}

/** Specialized facts are attached by preparation and cleared locally. */
final class boolean_literal_structure extends expression_node_structure
{
	use Preparation_Facts;

	/** Canonical value normalized by the source frontend. */
	public bool $value;
	/** @ownership owner */
	private ?prepared_boolean_literal $prepared_facts = null;

	public function __construct(bool $value)
	{
		$this->value = $value;
	}

	public function prepare_expression(ast_node $node, preparation_context $context): prepared_expression
	{
		$facts = File_Preparation::prepare_boolean($this->value, $context);
		$this->set_preparation($facts);
		return $facts;
	}

	public function generate_cpp_expression(ast_node $node, cpp_generation_context $context): string
	{
		return CPP_Generator::generate_boolean($this->require_preparation(), $context);
	}

	public function require_preparation(): prepared_boolean_literal
	{
		return object_cast($this->prepared_facts, prepared_boolean_literal::class);
	}
}

/** Specialized facts are attached by preparation and cleared locally. */
final class variable_reference_structure extends expression_node_structure
{
	use Collected_Occurrence;

	/** Observer of the canonical entry owned by collected_file.entries. */
	private ?collected_name $collected_occurrence /** weak<collected_name> */ = null;
	/** Attachment is permanent even if the native weak observer later expires. */
	private bool $occurrence_attached = false;

	use Preparation_Facts;

	/** @ownership owner */
	private ?prepared_variable_reference $prepared_facts = null;

	public function prepare_expression(ast_node $node, preparation_context $context): prepared_expression
	{
		$facts = File_Preparation::prepare_reference($node, $context);
		$this->set_preparation($facts);
		return $facts;
	}

	public function generate_cpp_expression(ast_node $node, cpp_generation_context $context): string
	{
		return CPP_Generator::generate_reference($this->require_preparation(), $context);
	}

	public function require_preparation(): prepared_variable_reference
	{
		return object_cast($this->prepared_facts, prepared_variable_reference::class);
	}
}

final class call_structure extends expression_node_structure
{
	use Preparation_Facts;

	/** @ownership owner */
	private ?prepared_call $prepared_facts = null;

	use Collected_Occurrence;

	/** Observer of the canonical entry owned by collected_file.entries. */
	private ?collected_name $collected_occurrence /** weak<collected_name> */ = null;
	/** Attachment is permanent even if the native weak observer later expires. */
	private bool $occurrence_attached = false;

	/** @storage.index token_list.tokens */
	public int $name_token_index;
	/** @storage.index token_list.tokens */
	public int $left_parenthesis_token_index;
	/** @storage.index token_list.tokens */
	public int $right_parenthesis_token_index;
	/**
	 * Arguments in source evaluation order. Ordered object list of child nodes.
	 * @storage.owner
	 */
	public Storage $arguments /** Storage<ast_node> */;
	/**
	 * Explicit type arguments. Ordered object list of child nodes.
	 * @storage.owner
	 */
	public Storage $template_arguments /** Storage<ast_node> */;

	public function __construct()
	{
		$this->arguments = new Storage /** Storage<ast_node> */();
		$this->template_arguments = new Storage /** Storage<ast_node> */();
	}

	/** Type arguments precede value arguments in structural traversal, not runtime evaluation. */
	public function append_children(Storage $result /** Storage<ast_node> */): void
	{
		$items /** Storage<ast_node> */ = $this->template_arguments;
		foreach ($items as $child) {
			$result->append($child);
		}

		$items /** Storage<ast_node> */ = $this->arguments;
		foreach ($items as $child) {
			$result->append($child);
		}
	}

	public function prepare_expression(ast_node $node, preparation_context $context): prepared_expression
	{
		$facts = Declaration_Preparation::prepare_call(Syntax_Nodes::call_data($node), $context);
		$this->set_preparation($facts);
		return $facts;
	}

	public function generate_cpp_expression(ast_node $node, cpp_generation_context $context): string
	{
		return CPP_Declarations::generate_call(Syntax_Nodes::call_data($node), $context);
	}

	public function require_preparation(): prepared_call
	{
		return object_cast($this->prepared_facts, prepared_call::class);
	}

	/** Relocate token members when an unchanged body moves in its file. */
	public function shift_tokens(int $delta): void
	{
		$this->name_token_index = $this->name_token_index + $delta;
		$this->left_parenthesis_token_index = $this->left_parenthesis_token_index + $delta;
		$this->right_parenthesis_token_index = $this->right_parenthesis_token_index + $delta;
	}
}

/** The body block references a file-owned local scope and an ordered statement list. */
final class function_structure extends statement_node_structure
{
	public bool $body_changed = true;
	public ?preparation_owner $body_preparation = null;
	/** Signature declarations persist; body locals belong to each replacement body. */
	public scope $signature_scope;
	use Preparation_Facts;

	/** @ownership owner */
	private ?prepared_function $prepared_facts = null;

	use Collected_Occurrence;

	/** Observer of the canonical entry owned by collected_file.entries. */
	private ?collected_name $collected_occurrence /** weak<collected_name> */ = null;
	/** Attachment is permanent even if the native weak observer later expires. */
	private bool $occurrence_attached = false;

	/** Ordered formal names and declaration token indexes. */
	public array $template_parameters /** hash<int> */ = [];
	/** @storage.index token_list.tokens */
	public int $name_token_index;
	/** Syntax child owned through this link.
	 * @ownership owner
	 */
	public ast_node $return_type;
	/** Syntax child owned through this link.
	 * @ownership owner
	 */
	public ast_node $body;
	/**
	 * Parameter declarations in signature order. Ordered object list of child nodes.
	 * @storage.owner
	 */
	public Storage $parameters /** Storage<ast_node> */;

	public function __construct()
	{
		$this->parameters = new Storage /** Storage<ast_node> */();
		$this->signature_scope = new scope();
		$this->signature_scope->mark_function();
	}

	/** Expose signature children before the body; the return type follows the parameters. */
	public function append_children(Storage $result /** Storage<ast_node> */): void
	{
		$items /** Storage<ast_node> */ = $this->parameters;
		foreach ($items as $child) {
			$result->append($child);
		}
		$result->append($this->return_type);
		$result->append($this->body);
	}

	public function prepare_declaration(ast_node $node, preparation_context $context): void
	{
		Declaration_Preparation::prepare_function(Syntax_Nodes::function_data($node), $context);
	}

	public function prepare_statement(ast_node $node, preparation_context $context): void
	{
		Declaration_Preparation::prepare_body(Syntax_Nodes::function_data($node), $context);
	}

	public function generate_cpp_statement(ast_node $node, cpp_generation_context $context): string
	{
		return CPP_Declarations::generate_function(Syntax_Nodes::function_data($node), $context);
	}

	public function require_preparation(): prepared_function
	{
		return object_cast($this->prepared_facts, prepared_function::class);
	}

	/** Relocate token members when an unchanged body moves in its file. */
	public function shift_tokens(int $delta): void
	{
		$this->name_token_index = $this->name_token_index + $delta;
	}
}

final class parameter_structure extends unsupported_node_structure
{
	use Preparation_Facts;

	/** @ownership owner */
	private ?prepared_parameter $prepared_facts = null;

	use Collected_Occurrence;

	/** Observer of the canonical entry owned by collected_file.entries. */
	private ?collected_name $collected_occurrence /** weak<collected_name> */ = null;
	/** Attachment is permanent even if the native weak observer later expires. */
	private bool $occurrence_attached = false;

	public passing_mode $mode = passing_mode::value;
	/** Null for value parameters; present exactly when mode is reference.
	 * @storage.index token_list.tokens
	 */
	public ?int $reference_token_index = null;
	/** @storage.index token_list.tokens */
	public int $name_token_index;
	/** Syntax child owned through this link.
	 * @ownership owner
	 */
	public ast_node $type_syntax;

	public function __construct(ast_node $type_syntax)
	{
		$this->type_syntax = $type_syntax;
	}

	/** Append direct syntax children in grammar order before links are published. */
	public function append_children(Storage $result /** Storage<ast_node> */): void
	{
		$result->append($this->type_syntax);
	}

	public function require_preparation(): prepared_parameter
	{
		return object_cast($this->prepared_facts, prepared_parameter::class);
	}

	/** Relocate token members when an unchanged body moves in its file. */
	public function shift_tokens(int $delta): void
	{
		if ($this->reference_token_index !== null) {
			$this->reference_token_index = $this->reference_token_index + $delta;
		}
		$this->name_token_index = $this->name_token_index + $delta;
	}
}

/** Shared payload for a file body or a block that introduces a scope. */
final class block_structure extends unsupported_node_structure
{
	use Child_List;

	/**
	 * Ordered object list of child nodes.
	 * @storage.owner
	 */
	public Storage $children /** Storage<ast_node> */;
	/**
	 * Non-owning lexical context: global scope or a scope owned by this parsed file.
	 * @reference.source model.global_scope
	 * @storage.reference parsed_file.scopes
	 * @reference.weak
	 */
	private scope $scope_reference /** weak<scope> */;

	public function __construct(scope $lexical_scope)
	{
		$this->scope_reference = $lexical_scope;
		$this->children = new Storage /** Storage<ast_node> */();
	}

	public function lexical_scope(): scope
	{
		return object_cast(weakref_get($this->scope_reference), scope::class);
	}

	/** Access the existing child list in grammar order, without copying membership. */
	public function child_list(): Storage /** Storage<ast_node> */
	{
		return $this->children;
	}
}

/** Binary and assignment expressions share operands; their node kinds retain the distinction. */
final class binary_structure extends unsupported_node_structure
{
	/** Syntax child owned through this link.
	 * @ownership owner
	 */
	public ast_node $left;
	/** @storage.index token_list.tokens */
	public int $operator_token_index;
	/** Syntax child owned through this link.
	 * @ownership owner
	 */
	public ast_node $right;

	public function __construct(ast_node $left, ast_node $right)
	{
		$this->left = $left;
		$this->right = $right;
	}

	/** Append direct syntax children in grammar order before links are published. */
	public function append_children(Storage $result /** Storage<ast_node> */): void
	{
		$result->append($this->left);
		$result->append($this->right);
	}

	/** Relocate token members when an unchanged body moves in its file. */
	public function shift_tokens(int $delta): void
	{
		$this->operator_token_index = $this->operator_token_index + $delta;
	}
}

/** An expression used as a statement owns its terminating semicolon here. */
final class expression_statement_structure extends statement_node_structure
{
	/** Syntax child owned through this link.
	 * @ownership owner
	 */
	public ast_node $expression;
	/** @storage.index token_list.tokens */
	public int $semicolon_token_index;

	public function __construct(ast_node $expression)
	{
		$this->expression = $expression;
	}

	/** Append direct syntax children in grammar order before links are published. */
	public function append_children(Storage $result /** Storage<ast_node> */): void
	{
		$result->append($this->expression);
	}

	public function prepare_statement(ast_node $node, preparation_context $context): void
	{
		File_Preparation::prepare_expression_statement(Syntax_Nodes::statement_data($node), $context);
	}

	public function generate_cpp_statement(ast_node $node, cpp_generation_context $context): string
	{
		return CPP_Generator::generate_expression_statement(Syntax_Nodes::statement_data($node), $context);
	}

	/** Relocate token members when an unchanged body moves in its file. */
	public function shift_tokens(int $delta): void
	{
		$this->semicolon_token_index = $this->semicolon_token_index + $delta;
	}
}

/** expression is null for a bare return; keyword and semicolon remain required. */
final class return_structure extends statement_node_structure
{
	/** @storage.index token_list.tokens */
	public int $keyword_token_index;
	/** Syntax child owned through this link.
	 * @ownership owner
	 */
	public ?ast_node $expression = null;
	/** @storage.index token_list.tokens */
	public int $semicolon_token_index;

	/** Append direct syntax children in grammar order before links are published. */
	public function append_children(Storage $result /** Storage<ast_node> */): void
	{
		if ($this->expression !== null) {
			$expression /** ast_node */ = $this->expression;
			$result->append($expression);
		}
	}

	public function prepare_statement(ast_node $node, preparation_context $context): void
	{
		File_Preparation::prepare_return(Syntax_Nodes::return_data($node), $context);
	}

	public function generate_cpp_statement(ast_node $node, cpp_generation_context $context): string
	{
		return CPP_Generator::generate_return(Syntax_Nodes::return_data($node), $context);
	}

	/** Relocate token members when an unchanged body moves in its file. */
	public function shift_tokens(int $delta): void
	{
		$this->keyword_token_index = $this->keyword_token_index + $delta;
		$this->semicolon_token_index = $this->semicolon_token_index + $delta;
	}
}

/** Preserve ambiguous binding syntax while declaration/assignment classification is refined.
 * type_syntax is absent on untyped writes; target is present only for indexed/field writes.
 * equals_token_index and value are either both present or both absent.
 * A typed declaration may omit its initializer; an untyped write requires a value.
 */
final class binding_structure extends statement_node_structure
{
	use Collected_Occurrence;

	/** Observer of the canonical entry owned by collected_file.entries. */
	private ?collected_name $collected_occurrence /** weak<collected_name> */ = null;
	/** Attachment is permanent even if the native weak observer later expires. */
	private bool $occurrence_attached = false;

	use Preparation_Facts;

	/** Derived facts are absent before preparation and after cleanup. @ownership owner */
	private ?prepared_binding $prepared_facts = null;

	public binding_kind $syntax_kind = binding_kind::unresolved;
	/** @storage.index token_list.tokens */
	public int $name_token_index;
	/** Syntax child owned through this link.
	 * @ownership owner
	 */
	public ?ast_node $type_syntax = null;
	/** Syntax child owned through this link.
	 * @ownership owner
	 */
	public ?ast_node $target = null;
	/** @storage.index token_list.tokens */
	public ?int $equals_token_index = null;
	/** Syntax child owned through this link.
	 * @ownership owner
	 */
	public ?ast_node $value = null;
	/** @storage.index token_list.tokens */
	public int $semicolon_token_index;

	/** Preserve grammar roles while omitting absent type, target and initializer children. */
	public function append_children(Storage $result /** Storage<ast_node> */): void
	{
		if ($this->type_syntax !== null) {
			$type_syntax /** ast_node */ = $this->type_syntax;
			$result->append($type_syntax);
		}
		if ($this->target !== null) {
			$target /** ast_node */ = $this->target;
			$result->append($target);
		}
		if ($this->value !== null) {
			$value /** ast_node */ = $this->value;
			$result->append($value);
		}
	}

	public function prepare_statement(ast_node $node, preparation_context $context): void
	{
		File_Preparation::prepare_binding(Syntax_Nodes::binding_data($node), $context);
	}

	public function generate_cpp_statement(ast_node $node, cpp_generation_context $context): string
	{
		return CPP_Generator::generate_binding(Syntax_Nodes::binding_data($node), $context);
	}

	public function require_preparation(): prepared_binding
	{
		return object_cast($this->prepared_facts, prepared_binding::class);
	}

	/** Relocate token members when an unchanged body moves in its file. */
	public function shift_tokens(int $delta): void
	{
		$this->name_token_index = $this->name_token_index + $delta;
		if ($this->equals_token_index !== null) {
			$this->equals_token_index = $this->equals_token_index + $delta;
		}
		$this->semicolon_token_index = $this->semicolon_token_index + $delta;
	}
}

/** Fixed extent is syntax until preparation checks and normalizes it. */
final class array_type_structure extends unsupported_node_structure
{
	use Collected_Occurrence;

	/** Observer of the canonical entry owned by collected_file.entries. */
	private ?collected_name $collected_occurrence /** weak<collected_name> */ = null;
	/** Attachment is permanent even if the native weak observer later expires. */
	private bool $occurrence_attached = false;

	/** Syntax child owned through this link.
	 * @ownership owner
	 */
	public ast_node $element_type;
	/** Syntax child owned through this link.
	 * @ownership owner
	 */
	public ast_node $count;

	public function __construct(ast_node $element_type, ast_node $count)
	{
		$this->element_type = $element_type;
		$this->count = $count;
	}

	/** Append direct syntax children in grammar order before links are published. */
	public function append_children(Storage $result /** Storage<ast_node> */): void
	{
		$result->append($this->element_type);
		$result->append($this->count);
	}
}

final class array_literal_structure extends unsupported_node_structure
{
	use Child_List;

	/**
	 * Ordered object list of child nodes.
	 * @storage.owner
	 */
	public Storage $elements /** Storage<ast_node> */;

	public function __construct()
	{
		$this->elements = new Storage /** Storage<ast_node> */();
	}

	/** Access the existing child list in grammar order, without copying membership. */
	public function child_list(): Storage /** Storage<ast_node> */
	{
		return $this->elements;
	}
}

final class index_structure extends unsupported_node_structure
{
	/** Syntax child owned through this link.
	 * @ownership owner
	 */
	public ast_node $base;
	/** Syntax child owned through this link.
	 * @ownership owner
	 */
	public ast_node $index;

	public function __construct(ast_node $base, ast_node $index)
	{
		$this->base = $base;
		$this->index = $index;
	}

	/** Append direct syntax children in grammar order before links are published. */
	public function append_children(Storage $result /** Storage<ast_node> */): void
	{
		$result->append($this->base);
		$result->append($this->index);
	}
}

final class struct_structure extends statement_node_structure
{
	/** Retained member index, private to this source worker. */
	public scope $member_scope;
	use Preparation_Facts;

	/** @ownership owner */
	private ?prepared_record $prepared_facts = null;

	use Collected_Occurrence;

	/** Observer of the canonical entry owned by collected_file.entries. */
	private ?collected_name $collected_occurrence /** weak<collected_name> */ = null;
	/** Attachment is permanent even if the native weak observer later expires. */
	private bool $occurrence_attached = false;

	use Child_List;

	/** @storage.index token_list.tokens */
	public int $name_token_index;
	/**
	 * Ordered field declarations. Ordered object list of child nodes.
	 * @storage.owner
	 */
	public Storage $fields /** Storage<ast_node> */;

	public function __construct()
	{
		$this->fields = new Storage /** Storage<ast_node> */();
		$this->member_scope = new scope();
	}

	/** Access the existing child list in grammar order, without copying membership. */
	public function child_list(): Storage /** Storage<ast_node> */
	{
		return $this->fields;
	}

	public function prepare_declaration(ast_node $node, preparation_context $context): void
	{
		Declaration_Preparation::prepare_struct(Syntax_Nodes::struct_data($node), $context);
	}

	public function prepare_statement(ast_node $node, preparation_context $context): void
	{
		return;
	}

	public function generate_cpp_statement(ast_node $node, cpp_generation_context $context): string
	{
		return CPP_Declarations::generate_struct(Syntax_Nodes::struct_data($node), $context);
	}

	public function require_preparation(): prepared_record
	{
		return object_cast($this->prepared_facts, prepared_record::class);
	}

	/** Relocate token members when an unchanged body moves in its file. */
	public function shift_tokens(int $delta): void
	{
		$this->name_token_index = $this->name_token_index + $delta;
	}
}

final class field_structure extends unsupported_node_structure
{
	use Preparation_Facts;

	/** @ownership owner */
	private ?prepared_field $prepared_facts = null;

	use Collected_Occurrence;

	/** Observer of the canonical entry owned by collected_file.entries. */
	private ?collected_name $collected_occurrence /** weak<collected_name> */ = null;
	/** Attachment is permanent even if the native weak observer later expires. */
	private bool $occurrence_attached = false;

	/** @storage.index token_list.tokens */
	public int $name_token_index;
	/** Syntax child owned through this link.
	 * @ownership owner
	 */
	public ast_node $type_syntax;

	public function __construct(ast_node $type_syntax)
	{
		$this->type_syntax = $type_syntax;
	}

	/** Append direct syntax children in grammar order before links are published. */
	public function append_children(Storage $result /** Storage<ast_node> */): void
	{
		$result->append($this->type_syntax);
	}

	public function require_preparation(): prepared_field
	{
		return object_cast($this->prepared_facts, prepared_field::class);
	}

	/** Relocate token members when an unchanged body moves in its file. */
	public function shift_tokens(int $delta): void
	{
		$this->name_token_index = $this->name_token_index + $delta;
	}
}

final class field_access_structure extends expression_node_structure
{
	use Preparation_Facts;

	/** @ownership owner */
	private ?prepared_field_access $prepared_facts = null;

	use Collected_Occurrence;

	/** Observer of the canonical entry owned by collected_file.entries. */
	private ?collected_name $collected_occurrence /** weak<collected_name> */ = null;
	/** Attachment is permanent even if the native weak observer later expires. */
	private bool $occurrence_attached = false;

	/** Syntax child owned through this link.
	 * @ownership owner
	 */
	public ast_node $base;
	/** @storage.index token_list.tokens */
	public int $name_token_index;

	public function __construct(ast_node $base)
	{
		$this->base = $base;
	}

	/** Append direct syntax children in grammar order before links are published. */
	public function append_children(Storage $result /** Storage<ast_node> */): void
	{
		$result->append($this->base);
	}

	public function prepare_expression(ast_node $node, preparation_context $context): prepared_expression
	{
		$facts = Declaration_Preparation::prepare_field_access(Syntax_Nodes::field_access_data($node), $context);
		$this->set_preparation($facts);
		return $facts;
	}

	public function generate_cpp_expression(ast_node $node, cpp_generation_context $context): string
	{
		return CPP_Declarations::generate_field_access(Syntax_Nodes::field_access_data($node), $context);
	}

	public function require_preparation(): prepared_field_access
	{
		return object_cast($this->prepared_facts, prepared_field_access::class);
	}

	/** Relocate token members when an unchanged body moves in its file. */
	public function shift_tokens(int $delta): void
	{
		$this->name_token_index = $this->name_token_index + $delta;
	}
}

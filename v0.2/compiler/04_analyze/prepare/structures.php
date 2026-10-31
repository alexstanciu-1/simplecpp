<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Canonical builtin definitions for the current subset; enum cases are shared object identities. */
enum type_definition: int /** uint8 */ {
	case int64 = 1;
	case void = 2;
}

/**
 * Records whether an entry return is proven to fit native int or needs a runtime range check.
 *
 * Entry return:
 * ```text
 * return 8;
 * ```
 * A constant inside the target range selects proven; an unknown value requires checked conversion.
 */
enum exit_conversion: int /** uint8 */ {
	case proven = 1;
	case checked = 2;
}

/**
 * Signed target exit range, supplied by native target facts rather than the PHP host.
 *
 * Target facts:
 * ```text
 * new entry_target(31)
 * ```
 * This describes a signed native int with 31 value bits, regardless of the PHP host integer width.
 */
final readonly class entry_target
{
	public int $digits /** uint8; signed value bits, 15..63 */;
	public int $minimum /** int64 */;
	public int $maximum /** int64 */;

	/** Bound the target to integer widths representable by this compiler's int64 model. */
	public function __construct(int $digits /** uint8; 15..63 */)
	{
		if (($digits < 15) || ($digits > 63)) {
			throw new \InvalidArgumentException('Unsupported native int width');
		}
		$this->digits = $digits;
		$this->maximum = $digits === 63 ? PHP_INT_MAX : (1 << $digits) - 1;
		$this->minimum = -$this->maximum - 1;
	}

	public function contains(int $value /** int64 */): bool
	{
		return ($value >= $this->minimum) && ($value <= $this->maximum);
	}
}

/**
 * Additional expression facts for lowering: canonical type and optional known integer value, linked to the original AST.
 */
abstract readonly class prepared_value
{
	public ast_expression $syntax;
	public type_definition $type;
	public ?int $constant /** int64 when known */;

	public function __construct(ast_expression $syntax, type_definition $type, ?int $constant /** int64 */)
	{
		$this->syntax = $syntax;
		$this->type = $type;
		$this->constant = $constant;
	}
}

/**
 * A range-checked integer literal normalized to an int64 value for backend emission.
 *
 * Source literal:
 * ```text
 * return 0008;
 * ```
 * The prepared integer value is 8; original spelling remains available through its AST link.
 */
final readonly class prepared_integer extends prepared_value
{
	public int $value /** int64 */;

	public function __construct(ast_integer_expression $syntax, int $value /** int64 */)
	{
		$this->value = $value;
		parent::__construct($syntax, type_definition::int64, $value);
	}
}

/**
 * A resolved local reference with a direct declaration link and the value facts known at preparation time.
 *
 * PHP-flavor reference:
 * ```text
 * $value int = 3;
 * echo $value;
 * ```
 * The echo value links directly to the value declaration and records the known integer constant 3.
 */
final readonly class prepared_reference extends prepared_value
{
	public ast_local_declaration $binding;

	public function __construct(ast_name_expression $syntax, ast_local_declaration $binding)
	{
		$this->binding = $binding;
		parent::__construct($syntax, $binding->type_def(), $binding->constant());
	}
}

/**
 * Base for statement results that need additional semantic data; calls reuse their AST node directly.
 */
abstract readonly class prepared_statement {
}

/**
 * Output-only literal bytes; this does not introduce a general string scalar type.
 *
 * Literal syntax:
 * ```text
 * echo "a\0b";
 * ```
 * The prepared bytes include the embedded NUL; emission uses their length rather than C-string termination.
 */
final readonly class prepared_output_string
{
	public ast_string_expression $syntax;
	public string $bytes;

	public function __construct(ast_string_expression $syntax, string $bytes)
	{
		$this->syntax = $syntax;
		$this->bytes = $bytes;
	}
}

/**
 * Output-ready operand facts, retaining either an integer value or decoded literal bytes.
 *
 * PHP-flavor output:
 * ```text
 * $value int = 3; echo $value;
 * ```
 * The echo retains resolved integer facts; a quoted literal instead retains decoded bytes.
 */
final readonly class prepared_echo extends prepared_statement
{
	public ast_echo_statement $syntax;
	public prepared_value|prepared_output_string $value;

	public function __construct(ast_echo_statement $syntax, prepared_value|prepared_output_string $value)
	{
		$this->syntax = $syntax;
		$this->value = $value;
	}
}

/**
 * A local declaration paired with its prepared initializer; identity and resolved type remain on the AST declaration.
 *
 * PHP-flavor declaration:
 * ```text
 * $value int = 3;
 * ```
 * binding is the AST declaration; initializer holds the checked integer value 3.
 */
final readonly class prepared_declaration extends prepared_statement
{
	public ast_local_declaration $binding;
	public prepared_value $initializer;

	public function __construct(ast_local_declaration $binding, prepared_value $initializer)
	{
		$this->binding = $binding;
		$this->initializer = $initializer;
	}
}

/**
 * An entry return with its prepared value and the selected conversion to the target executable exit type.
 *
 * Entry return:
 * ```text
 * return 8;
 * ```
 * Preparation records the value and proves it fits the target native int range.
 */
final readonly class prepared_return extends prepared_statement
{
	public ast_return_statement $syntax;
	public prepared_value $value;
	public exit_conversion $conversion;

	public function __construct(ast_return_statement $syntax, prepared_value $value, exit_conversion $conversion)
	{
		$this->syntax = $syntax;
		$this->value = $value;
		$this->conversion = $conversion;
	}
}

/** Body-owned facts; signatures are a separate publication and never require a body traversal. */
abstract readonly class prepared_body
{
	public array $statements /** list<prepared_statement|ast_call_statement> */;
	public int $statement_visits /** uint32 */;
	public int $expression_visits /** uint32 */;
	public int $name_lookups /** uint32 */;

	public function __construct(array $statements /** list<prepared_statement|ast_call_statement> */,
		int $statement_visits /** uint32 */, int $expression_visits /** uint32 */, int $name_lookups /** uint32 */)
	{
		$this->statements = $statements;
		$this->statement_visits = $statement_visits;
		$this->expression_visits = $expression_visits;
		$this->name_lookups = $name_lookups;
	}
}

/** Statement facts for an explicit function or the conceptual top-level entry body. */
final readonly class prepared_function_body extends prepared_body
{
}

/**
 * Successful selected entry, with its source-owned function definitions for standalone emission.
 *
 * PHP-flavor entry:
 * ```text
 * $ret int = 8; return $ret;
 * ```
 * The handle carries the completed entry statements, native exit target and source-owned function bodies.
 */
final readonly class prepared_entry extends prepared_body
{
	public ast_file_node $file;
	public entry_target $target;
	public array $functions /** list<prepared_function> */;

	/** Retain completed facts; failed body work never creates an executable entry result. */
	public function __construct(ast_file_node $file, entry_target $target,
		array $statements /** list<prepared_statement|ast_call_statement> */, int $statement_visits /** uint32 */,
		int $expression_visits /** uint32 */, int $name_lookups /** uint32 */,
		array $functions /** list<prepared_function> */ = [])
	{
		parent::__construct($statements, $statement_visits, $expression_visits, $name_lookups);
		$this->file = $file;
		$this->target = $target;
		$this->functions = $functions;
	}
}

/**
 * A validated bare return from a void function, retained for backend control-flow emission.
 *
 * PHP-flavor function:
 * ```text
 * function ping(): void { return; }
 * ```
 * The bare return exits ping; it is not an executable exit-status value.
 */
final readonly class prepared_void_return extends prepared_statement
{
	public ast_return_statement $syntax;

	public function __construct(ast_return_statement $syntax)
	{
		$this->syntax = $syntax;
	}
}

/**
 * Successful body handle; declarations own type facts, and partial preparation cannot reach emission.
 *
 * PHP-flavor function:
 * ```text
 * function ping(): void { echo 8; }
 * ```
 * The handle pairs its declaration with a successfully prepared body for emission.
 */
final readonly class prepared_function
{
	public ast_function_declaration $declaration;
	public prepared_body $body;

	public function __construct(ast_function_declaration $declaration, prepared_body $body)
	{
		$this->declaration = $declaration;
		$this->body = $body;
	}
}

/**
 * Whole-program admission retains all collections and exactly one prepared executable entry.
 *
 * Two source files:
 * ```text
 * main:    ping(); return 8;
 * support: void ping() {}
 * ```
 * The program selects main as entry and retains prepared function bodies grouped by source path.
 */
final readonly class prepared_program
{
	public string $entry_path;
	public array $files /** array<string,ast_file_node> */;
	public prepared_entry $entry;
	public array $functions /** array<string,list<prepared_function>>; source path -> definitions */;

	public function __construct(string $entry_path, array $files /** array<string,ast_file_node> */, prepared_entry $entry,
		array $functions /** array<string,list<prepared_function>> */)
	{
		$this->entry_path = $entry_path;
		$this->files = $files;
		$this->entry = $entry;
		$this->functions = $functions;
	}
}

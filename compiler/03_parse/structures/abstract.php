<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Declared visibility of a static property or constant; native uint8. */
enum member_visibility: int
{
	case public = 0;
	case protected = 1;
	case private = 2;
}

/** Parameter transport intent; native uint8. Reference binds exact stable storage without conversion. */
enum passing_mode: int
{
	case value = 0;
	case reference = 1;
	case readonly_reference = 2;

	/** Spell a callable input with its reference qualification in source or native type syntax. */
	public function qualify(string $type): string
	{
		return match ($this) {
			self::value => $type,
			self::reference => $type . '&',
			self::readonly_reference => 'const ' . $type . '&',
		};
	}
}

/**
 * Syntax ownership only; semantic references never become parent links.
 *
 * Source syntax:
 * ```text
 * echo 8;
 * ```
 * The statement spans all three tokens; its integer child spans only the token 8.
 */
abstract class ast_node
{
	/** PROPERTIES */
	/** Inclusive token position. */
	public readonly int $start /** uint32 */;
	/** Exclusive token position; functions settle at body completion. */
	public readonly int $end /** uint32 */;
	/** Native non-owning; null only on file root. */
	public readonly ?ast_node $parent;

	/** CONSTRUCTOR */
	/**
	 * Validate token boundaries while allowing a function end to settle after its body.
	 *
	 * $start: token position.
	 * $end: exclusive token position.
	 */
	public function __construct(int $start /** uint32 */, int $end /** uint32 */,
		bool $defer_end = false)
	{
		if (($start < 0) || ($end < $start)) {
			throw new \InvalidArgumentException('Invalid AST token range');
		}
		$this->start = $start;
		if (!$defer_end) {
			$this->end = $end;
		}
	}

	/** METHODS */
	/** Attach exactly once; permit bottom-up construction but reject ownership cycles. */
	public function attach(ast_node $parent): void
	{
		if (($this instanceof ast_file_node) || isset($this->parent)) {
			throw new \LogicException('AST parent is already settled');
		}
		$ancestor = $parent;
		while (true)
		{
			if ($ancestor === $this) {
				throw new \LogicException('AST ownership cycle');
			}
			if (!isset($ancestor->parent)) {
				break;
			}
			$ancestor = $ancestor->parent;
		}
		$this->parent = $parent;
	}

	/** Source queries walk ownership; ordinary semantic work uses named node fields. */
	public function root(): ast_file_node
	{
		$node = $this;
		while (!($node instanceof ast_file_node)) {
			$node = $node->parent ?? throw new \LogicException('AST node is not attached to a file');
		}
		return $node;
	}

	/** Token and import owner; a contributed member switches lexical roots at its boundary. */
	public function lexical_root(): ast_file_node
	{
		$node = $this;
		while (!($node instanceof ast_file_node)) {
			if (($node instanceof ast_function_declaration || $node instanceof ast_instance_field || $node instanceof ast_literal_declaration) && $node->trait_origin !== null) {
				return $node->trait_origin->root();
			}
			$node = $node->parent;
		}
		return $node;
	}

	public function source(): source_file
	{
		return $this->lexical_root()->tokens->source;
	}

	/** Return: source byte offset. */
	public function byte_start(): int /** uint32 */
	{
		return $this->lexical_root()->byte_at($this->start);
	}

	/**
	 * Recover the exclusive source-byte end from the final token or an empty-span anchor.
	 *
	 * Return: exclusive source byte offset.
	 */
	public function byte_end(): int /** uint32 */
	{
		$root = $this->lexical_root();
		if ($this->start === $this->end) {
			return $root->byte_at($this->start);
		}
		$token = $root->tokens->tokens[$this->end - 1];
		return $token->offset + $token->length;
	}

	/** Recover exact source bytes with one ownership walk; trivia between tokens is retained. */
	public function source_text(): string
	{
		$root = $this->lexical_root();
		$start = $root->byte_at($this->start);
		if ($this->start === $this->end) {
			return '';
		}
		$last = $root->tokens->tokens[$this->end - 1];
		return substr($root->tokens->source->content, $start, $last->offset + $last->length - $start);
	}
}

/** Declaration families that can share a duplicate chain for the same symbol key. */
enum declaration_duplicate_category: int /** uint8 */ {
	case local_binding = 1;
	case type_declaration = 2;
	case global_declaration = 3;
	case instance_field = 4;
	case enum_case = 5;
	case constant_declaration = 6;
	case function_declaration = 7;
}

/**
 * Supplies a declaration's local lookup key; index owners add category prefixes.
 *
 * Example: function ping(): void {} supplies ping, while its file index uses func:ping.
 * Qualified symbol keys are separate from the short scope-local declaration key.
 */
interface ast_declaration_interface {
	public function name(): string;
	public function declaration_key(): string;
	public function symbol_key(): string;
	public function duplicate_category(): declaration_duplicate_category;
	public function duplicate_element(): ?ast_declaration_interface;
	public function link_duplicate(ast_declaration_interface $duplicate_element): void;
	public function clear_duplicate(): void;
}

/** Lexical name location for diagnostics, including a catch binding used as the first declaration. */
interface ast_declaration_site
{
	public function name(): string;
	public function name_position(): int /** uint32 */;
	public function lexical_root(): ast_file_node;
}

/** Identity of a prepared value name, including runtime constants such as STR_PAD_LEFT. */
interface name_binding
{
	public function symbol_key(): string;
}

/** Shared mutable body storage contract for local declarations and by-value parameters. */
interface ast_local_binding extends ast_declaration_interface, ast_declaration_site, name_binding {
	public function storage_id(): int /** uint32 */;
	public function type_def(): type_definition;
	public function constant(): int|float|bool|null /** int64|float64|bool|null */;
	public function constant_known(): bool;
}

/**
 * Shared declaration identity; visibility and type facts stay with their specific owners.
 *
 * PHP-flavor declarations:
 * ```text
 * $value int = 3;
 * function ping(): void {}
 * ```
 * The trait supplies short/namespace identity and a derived name; local type and function return facts have different owners.
 */
trait ast_declaration
{
	public readonly string $short_name;
	/** Canonical namespace path without leading/trailing separator; null denotes the source root. */
	public readonly ?string $namespace;
	/**
	 * Next erroneous same-key declaration; only the declaring scope publishes this chain.
	 * Native non-owning; declaring scope maintains the link.
	 */
	protected ?ast_declaration_interface $duplicate_element = null;

	public function duplicate_element(): ?ast_declaration_interface
	{
		return $this->duplicate_element;
	}

	/** The declaring scope links unchained declarations after successful collection, with no active readers. */
	public function link_duplicate(ast_declaration_interface $duplicate_element): void
	{
		if (!($this instanceof ast_declaration_interface) || ($this->duplicate_element !== null)
			|| ($duplicate_element === $this) || ($duplicate_element->duplicate_element() !== null)
			|| ($duplicate_element->duplicate_category() !== $this->duplicate_category())
			|| ($duplicate_element->symbol_key() !== $this->symbol_key())) {
			throw new \LogicException('Invalid duplicate declaration link');
		}
		$this->duplicate_element = $duplicate_element;
	}

	/** Publication-only reset; other indexes must never mutate the declaring scope chain. */
	public function clear_duplicate(): void
	{
		$this->duplicate_element = null;
	}

	/** Qualified identity without a root marker; local variables keep a null namespace. */
	public function name(): string
	{
		return $this->namespace === null ? $this->short_name : $this->namespace . '\\' . $this->short_name;
	}

	/** Default local key; neither a declaration category nor a namespace is included. */
	public function declaration_key(): string
	{
		return $this->short_name;
	}
}

/**
 * Base for value-producing source syntax; parsing preserves intent while preparation resolves its meaning.
 *
 * Value syntax:
 * ```text
 * return 8;
 * ```
 * Only 8 is the expression; the enclosing return is a statement.
 */
abstract class ast_expression extends ast_node
{
	/** Prepared fact: checked projection selected for this occurrence as an arrow receiver. */
	public ?carrier_type_definition $member_borrow = null;

	/** Prepared fact: canonical value type; null until preparation succeeds. */
	protected ?type_definition $type_def = null;
	/** Prepared fact: scalar payload; constant_known distinguishes a known null from an unknown value. */
	protected int|float|bool|null $constant /** int64|float64|bool|null */ = null;
	/** Prepared fact: scalar payload is known; false for strings, unknown values, and before preparation or after reset. */
	protected bool $constant_known = false;

	public function type_def(): type_definition
	{
		return $this->type_def ?? throw new \LogicException('Expression type is not settled');
	}

	public function member_type(): type_definition
	{
		return $this->member_borrow?->payload_type ?? $this->type_def();
	}

	public function prepare_member_borrow(): void
	{
		$type = $this->type_def();
		if ($type instanceof carrier_type_definition) $this->member_borrow = $type;
	}

	/** A literal of a type whose plain value can be copied and destroyed without effects. */
	public function is_effect_free_literal(): bool
	{
		return ($this instanceof ast_integer_expression || $this instanceof ast_float_expression
			|| $this instanceof ast_keyword_expression || $this instanceof ast_string_expression)
			&& $this->type_def()->copy_has_no_observable_effects();
	}

	/** Operator/conversion view of one payload layer; the stored expression type remains unchanged. */
	public function value_type(): type_definition
	{
		$type = $this->type_def();
		return $type instanceof carrier_type_definition ? $type->payload_type : $type;
	}

	public function constant(): int|float|bool|null /** int64|float64|bool|null */
	{
		$this->type_def();
		return $this->constant;
	}

	public function constant_known(): bool
	{
		$this->type_def();
		return $this->constant_known;
	}

	/** Set occurrence facts once; $known preserves null as a value independently of readiness. */
	protected function settle_value(type_definition $type, int|float|bool|null|constant_evaluation $constant /** int64|float64|bool|null|constant_evaluation */, bool $known = false): void
	{
		if ($this->type_def !== null) {
			throw new \LogicException('Expression is already prepared');
		}
		$this->type_def = $type;
		$this->constant = $constant === constant_evaluation::unavailable ? null : $constant;
		$this->constant_known = $constant !== constant_evaluation::unavailable && ($known || ($constant !== null));
	}

	public function reset_preparation(): void
	{
		$this->member_borrow = null;
		$this->type_def = null;
		$this->constant = null;
		$this->constant_known = false;
	}
}

/**
 * Retains original flavor spelling intent; PHP first writes are collected as declarations before this node is built.
 *
 * Backing: IDs 1..2.
 */
enum assignment_intent: int /** uint8 */ {
	case existing_binding = 1;
	case declare_or_assign = 2;
}

/** Written import filter; plain imports admit supported symbol kinds independently. Backing: IDs 1..3. */
enum import_kind: int /** uint8 */ {
	case symbol = 1;
	case function_only = 2;
	case constant_only = 3;
}

/**
 * Base for executable syntax owned by a function body, including the implicit entry body.
 *
 * Statement syntax:
 * ```text
 * echo 8;
 * return 8;
 * ```
 * These are two ordered statements in the implicit entry function body.
 */
abstract class ast_statement extends ast_node {
}

/** Shared established-storage write owner; e.g. x = 1, x += 2 or x++; syntax children remain on concrete nodes. */
abstract class ast_storage_write extends ast_statement
{
	/** PROPERTIES */
	public readonly string $name;

	/** PREP FACTS */
	/** Prepared fact: native non-owning local/parameter binding; null before settlement or after reset. */
	protected ?ast_local_binding $binding = null;

	/** CONSTRUCTOR */
	/** $start/$end: token positions for the complete write statement or header clause. */
	public function __construct(int $start /** uint32 */, int $end /** uint32 */, string $name)
	{
		parent::__construct($start, $end);
		$this->name = $name;
	}

	/** METHODS */
	public function local_def(): ast_local_binding
	{
		return $this->binding ?? throw new \LogicException('Write binding is not settled');
	}

	/** Property and indexed writes have a selected place rather than a plain storage binding. */
	public function writes_to(ast_local_binding $local): bool
	{
		return $this->binding === $local;
	}

	/** Publish the resolved storage only after semantic admission succeeds. */
	protected function settle_binding(ast_local_binding $local): void
	{
		if ($this->binding !== null) {
			throw new \LogicException('Write is already prepared');
		}
		$this->binding = $local;
	}

	public function reset_preparation(): void
	{
		$this->binding = null;
	}
}

/** Update direction is independent of prefix/postfix spelling; native uint8. */
enum update_operator: int
{
	case increment = 1;
	case decrement = 2;
}


/** A published nominal application type; struct layout and enum cases keep their distinct owners. */
interface ast_type_declaration extends ast_declaration_interface, ast_declaration_site
{
	public function type_definition(): type_definition;
}

/** A nominal declaration owning methods; instance storage remains specific to the defining type. */
interface ast_method_owner extends ast_type_declaration
{
	public function method(string $name): ?ast_function_declaration;
}

/** One name index of method heads; duplicate occurrences remain in the declaring scope's chain. */
trait ast_methods
{
	/** @var ast_function_declaration[] Native non-owning method index into the source declaration inventory. */
	public readonly array $methods /** array<string,ast_function_declaration> */;

	public function method(string $name): ?ast_function_declaration
	{
		return $this->methods[$name] ?? null;
	}
}

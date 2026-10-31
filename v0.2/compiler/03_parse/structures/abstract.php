<?php

declare(strict_types=1);
namespace scpp\compiler;

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
	public readonly int $start /** uint32; inclusive token position */;
	public readonly int $end /** uint32; exclusive token position; functions settle at body completion */;
	public readonly ?ast_node $parent /** native non-owning; null only on file root */;

	public function __construct(int $start /** uint32; token position */, int $end /** uint32; exclusive token position */,
		bool $defer_end = false)
	{
		if (($start < 0) || ($end < $start)) {
			throw new \InvalidArgumentException('Invalid AST token range');
		}
		$this->start = $start;
		if (!$defer_end) { $this->end = $end; }
	}

	/** Attach exactly once; permit bottom-up construction but reject ownership cycles. */
	public function attach(ast_node $parent): void
	{
		if (($this instanceof ast_file_node) || isset($this->parent)) {
			throw new \LogicException('AST parent is already settled');
		}
		$ancestor = $parent;
		while (true) {
			if ($ancestor === $this) { throw new \LogicException('AST ownership cycle'); }
			if (!isset($ancestor->parent)) { break; }
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

	public function source(): source_file
	{
		return $this->root()->tokens->source;
	}

	public function byte_start(): int /** uint32; source byte offset */
	{
		return $this->root()->byte_at($this->start);
	}

	public function byte_end(): int /** uint32; exclusive source byte offset */
	{
		$root = $this->root();
		if ($this->start === $this->end) { return $root->byte_at($this->start); }
		$token = $root->tokens->tokens[$this->end - 1];
		return $token->offset + $token->length;
	}

	/** Recover exact source bytes with one ownership walk; trivia between tokens is retained. */
	public function source_text(): string
	{
		$root = $this->root();
		$start = $root->byte_at($this->start);
		if ($this->start === $this->end) { return ''; }
		$last = $root->tokens->tokens[$this->end - 1];
		return substr($root->tokens->source->content, $start, $last->offset + $last->length - $start);
	}
}

/**
 * Supplies a declaration's local lookup key; index owners add category prefixes.
 *
 * Example: function ping(): void {} supplies ping, while its file index uses func:ping.
 * Namespace qualification is deliberately outside this contract for now.
 */
interface ast_declaration_interface
{
	public function declaration_key(): string;
	public function next_declaration(): ?ast_declaration_interface;
	public function link_next(ast_declaration_interface $next): void;
}

/**
 * Shared declaration identity; visibility and type facts stay with their specific owners.
 *
 * PHP-flavor declarations:
 * ```text
 * $value int = 3;
 * function ping(): void {}
 * ```
 * The trait supplies their name field and default name-based key; local type and function return facts have different owners.
 */
trait ast_declaration
{
	public readonly string $name;
	/** Same-key declaration in this source scope; not syntax ownership or global-index membership. */
	protected ?ast_declaration_interface $next = null;

	public function next_declaration(): ?ast_declaration_interface
	{
		return $this->next;
	}

	/** Collection links an unchained duplicate once; existing source chains are never repurposed globally. */
	public function link_next(ast_declaration_interface $next): void
	{
		if (($this->next !== null) || ($next === $this) || ($next->next_declaration() !== null)
			|| (get_class($next) !== get_class($this)) || ($next->declaration_key() !== $this->declaration_key())) {
			throw new \LogicException('Invalid duplicate declaration link');
		}
		$this->next = $next;
	}

	/** Default local key; neither a declaration category nor a namespace is included. */
	public function declaration_key(): string
	{
		return $this->name;
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
	/** Prepared fact: canonical scalar type; null until scalar preparation succeeds. */
	protected ?type_definition $type_def = null;
	/** Prepared fact: value at this occurrence; null means unknown once type_def is settled. */
	protected ?int $constant /** int64 */ = null;

	public function type_def(): type_definition
	{
		return $this->type_def ?? throw new \LogicException('Expression type is not settled');
	}
	public function constant(): ?int /** int64 */
	{
		$this->type_def();
		return $this->constant;
	}
	protected function settle_value(type_definition $type, ?int $constant /** int64 */): void
	{
		if ($this->type_def !== null) { throw new \LogicException('Expression is already prepared'); }
		$this->type_def = $type;
		$this->constant = $constant;
	}
	public function reset_preparation(): void
	{
		$this->type_def = null;
		$this->constant = null;
	}
}

/**
 * Retains whether the flavor requires an existing binding or permits declaration-or-assignment syntax; it does not resolve that choice.
 */
enum assignment_intent: int /** uint8; IDs 1..2 */
{
	case existing_binding = 1;
	case declare_or_assign = 2;
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
abstract class ast_statement extends ast_node
{
}

<?php

declare(strict_types=1);
namespace scpp\compiler;

/**
 * Written type syntax, e.g. int in int value = 8; resolved facts remain on the owning declaration/expression.
 * Nested references use first_child and next_sibling in argument order, e.g. vector<int>.
 */
class ast_type_reference extends ast_node
{
	public readonly string $name;
	/** Native owning first argument or compound operand; null for a simple type. */
	public readonly ?ast_type_reference $first_child;
	/** Native owning next type in written order; all siblings attach to the containing type. */
	public readonly ?ast_type_reference $next_sibling;

	/** $start/$end: token boundaries; an empty range anchors an implicit type. Build sibling chains from the end. */
	public function __construct(int $start /** uint32 */, int $end /** uint32 */, string $name,
		?ast_type_reference $first_child = null, ?ast_type_reference $next_sibling = null)
	{
		parent::__construct($start, $end);
		$this->name = $name;
		$this->first_child = $first_child;
		$this->next_sibling = $next_sibling;
		for ($child = $first_child; $child !== null; $child = $child->next_sibling) {
			$child->attach($this);
		}
	}

	/** Derive the full written identity for diagnostics, inspection and incremental contracts; no retained copy. */
	public function spelling(): string
	{
		if ($this->first_child === null) {
			return $this->name;
		}
		$name = $this->name . '<';
		$separator = '';
		for ($child = $this->first_child; $child !== null; $child = $child->next_sibling) {
			$name .= $separator . $child->spelling();
			$separator = ',';
		}
		return $name . '>';
	}

}

/** Infix type syntax such as Base&Readable; the children form one chain in written order. */
final class ast_compound_type_reference extends ast_type_reference
{
	/** Render written operands with their infix separator for diagnostics and signature inspection. */
	public function spelling(): string
	{
		$first = $this->first_child;
		$text = $first->spelling();
		for ($part = $first->next_sibling; $part !== null; $part = $part->next_sibling) {
			$text .= $this->name . $part->spelling();
		}
		return $text;
	}
}

/** Factory target in new static(...), resolved from the called class during body preparation. */
final class ast_called_type_reference extends ast_type_reference
{
}

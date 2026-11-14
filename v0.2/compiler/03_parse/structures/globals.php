<?php

declare(strict_types=1);
namespace scpp\compiler;

/**
 * Explicit namespace-owned storage, published independently of the file's executable body.
 * PHP example: global $count int = 10; publishes var:count, or var:App\count inside App.
 * The initializer owns its checked literal value; reads link through the stable symbol entry.
 */
final class ast_global_declaration extends ast_literal_declaration
{
	public function name(): string
	{
		return ($this->parent ?? null) instanceof ast_record_declaration
			? $this->parent->name() . '::' . $this->short_name : parent::name();
	}

	public function symbol_key(): string
	{
		return 'var:' . $this->name();
	}

	/** Return: token position of the declared name, independent of inferred/explicit type. */
	public function name_position(): int /** uint32 */
	{
		if ($this->parent instanceof ast_record_declaration) {
			return $this->type_reference->start === $this->start + 1 ? $this->type_reference->end : $this->start + 1;
		}
		return $this->start + ($this->source()->flavor === flavor::php ? 1 : 2);
	}
}

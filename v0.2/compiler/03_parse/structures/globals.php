<?php

declare(strict_types=1);
namespace scpp\compiler;

/**
 * Explicit namespace-owned storage, published independently of the file's executable body.
 * PHP example: global $count int = 10; publishes var:count, or var:App\count inside App.
 * The initializer owns its checked literal value; reads link through the stable symbol entry.
 */
final class ast_global_declaration extends ast_node implements ast_declaration_interface
{
	use ast_declaration;

	/** PROPERTIES */
	/** Written type; null requests integer-literal inference in this slice. */
	public readonly ?string $type_name;
	/** Native owning initializer syntax. */
	public readonly ast_integer_expression $initializer;

	/** PREP FACTS */
	/** Prepared fact: canonical storage type; null before preparation or after reset. */
	protected ?type_definition $type_def = null;

	/** CONSTRUCTOR */
	/** $start and $end are token boundaries, with exclusive end. */
	public function __construct(int $start /** uint32 */, int $end /** uint32 */, string $name,
		?string $namespace, ?string $type_name, ast_integer_expression $initializer)
	{
		parent::__construct($start, $end);
		$this->short_name = $name;
		$this->namespace = $namespace;
		$this->type_name = $type_name;
		$this->initializer = $initializer;
		$initializer->attach($this);
	}

	/** METHODS */
	public function symbol_key(): string
	{
		return 'var:' . $this->name();
	}

	public function reset_preparation(): void
	{
		$this->type_def = null;
		$this->initializer->reset_preparation();
	}

	public function type_ready(): bool
	{
		return $this->type_def !== null;
	}

	public function settle_type(type_definition $type): void
	{
		if ($this->type_def !== null) {
			throw new \LogicException('Global type is already settled');
		}
		$this->initializer->integer();
		$this->type_def = $type;
	}

	public function type_def(): type_definition
	{
		return $this->type_def ?? throw new \LogicException('Global type is not settled');
	}

	/** Return: token position of the declared name, independent of inferred/explicit type. */
	public function name_position(): int /** uint32 */
	{
		return $this->start + ($this->source()->flavor === flavor::php ? 1 : 2);
	}
}

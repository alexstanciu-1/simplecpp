<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Shared typed literal initializer and preparation readiness for namespace constants and global storage. */
abstract class ast_literal_declaration extends ast_node implements ast_declaration_interface
{
	use ast_declaration;

	/** PROPERTIES */
	/** Native owning written type; null requests scalar-literal inference in this slice. */
	public readonly ?ast_type_reference $type_reference;
	/** Native owning initializer syntax. */
	public readonly ast_expression $initializer;

	/** PREP FACTS */
	/** Prepared fact: canonical declared type; null before preparation or after reset. */
	protected ?type_definition $type_def = null;
	/** Prepared fact: approved destination conversion; null before settlement or after reset. */
	protected ?int $initialization /** uint16 */ = null;

	/** CONSTRUCTOR */
	/** $start and $end are token boundaries, with exclusive end. */
	public function __construct(int $start /** uint32 */, int $end /** uint32 */, string $name,
		?string $namespace, ?ast_type_reference $type_reference, ast_expression $initializer)
	{
		parent::__construct($start, $end);
		$this->short_name = $name;
		$this->namespace = $namespace;
		$this->type_reference = $type_reference;
		$type_reference?->attach($this);
		$this->initializer = $initializer;
		$initializer->attach($this);
	}

	/** METHODS */
	public function reset_preparation(): void
	{
		$this->type_def = null;
		$this->initialization = null;
		$this->initializer->reset_preparation();
	}

	public function type_ready(): bool
	{
		return $this->type_def !== null;
	}

	/** Attach the approved initialization facts after source preparation and conversion checks succeed. */
	public function settle_type(type_definition $type, int $conversion /** uint16 */ = value_conversion::identity): void
	{
		if ($this->type_def !== null) {
			throw new \LogicException('Literal declaration type is already settled');
		}
		$this->initializer->type_def();
		$this->type_def = $type;
		$this->initialization = $conversion;
	}

	public function initialization(): int /** uint16 */
	{
		return $this->initialization ?? throw new \LogicException('Literal declaration initialization is not prepared');
	}

	public function type_def(): type_definition
	{
		return $this->type_def ?? throw new \LogicException('Literal declaration type is not settled');
	}

}

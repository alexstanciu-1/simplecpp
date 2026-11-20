<?php

declare(strict_types=1);
namespace scpp\compiler;

/** One checked class access, e.g. object_cast($n, Leaf::class) or $n instanceof Leaf. */
final class ast_object_access_expression extends ast_expression
{
	/** PROPERTIES */
	public readonly ast_expression $operand;
	public readonly ast_type_reference $target;
	public readonly bool $test;

	/** PREP FACTS */
	/** Prepared fact: non-owning canonical target; null before successful preparation or after invalidation. */
	private ?shared_record_type_definition $target_type = null;
	/** Prepared fact: native check requirement; null until relationship admission succeeds. */
	private ?bool $dynamic_check = null;
	/** Prepared fact: a guarded argument consumes the adjusted native pointer. */
	public bool $used_as_guard = false;

	/** CONSTRUCTOR */
	/** Own the single evaluated operand and the written target reference. */
	public function __construct(int $start /** uint32 */, int $end /** uint32 */, ast_expression $operand, ast_type_reference $target, bool $test)
	{
		parent::__construct($start, $end);
		$this->operand = $operand;
		$this->target = $target;
		$this->test = $test;
		$operand->attach($this);
		$target->attach($this);
	}

	/** METHODS */
	public function settle_access(shared_record_type_definition $target, bool $dynamic): void
	{
		$this->target_type = $target;
		$this->dynamic_check = $dynamic;
		$this->settle_value($this->test ? type_bool::instance() : $target, null);
	}

	public function target_type(): shared_record_type_definition
	{
		return $this->target_type ?? throw new \LogicException('Object access target is not settled');
	}

	public function dynamic_check(): bool
	{
		return $this->dynamic_check ?? throw new \LogicException('Object access relationship is not settled');
	}

	public function reset_preparation(): void
	{
		parent::reset_preparation();
		$this->target_type = null;
		$this->dynamic_check = null;
		$this->used_as_guard = false;
	}
}

<?php

/* Resolve bare transfers independently of the construct that supplies their target. */
namespace scpp\compiler;

final class Control_Transfer_Preparation
{
	/** Resolve once before generation; breakable constructs and loops are distinct. */
	public static function prepare(control_transfer_node $syntax, preparation_context $context): prepared_control_transfer
	{
		$kind = $syntax->transfer_kind();
		$target /** nullable<breakable_node> */ = $context->break_target;
		if ($kind === control_transfer_kind::continue_loop) {
			$target = $context->continue_target;
		}
		if ($target === null) {
			throw new \RuntimeException('S2S break/continue requires an enclosing legal target');
		}
		$facts = new prepared_control_transfer();
		$facts->transfer_kind = $kind;
		$facts->target = $target;
		return $facts;
	}
}

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
		if ($context->finally_region !== null) {
			$region /** prepared_finally */ = $context->finally_region;
			if (($kind === control_transfer_kind::break_construct) && ($target === $context->finally_break_target)) {
				$region->captures_break = true;
			}
			if (($kind === control_transfer_kind::continue_loop) && ($target === $context->finally_continue_target)) {
				$region->captures_continue = true;
			}
		}
		$facts = new prepared_control_transfer();
		$facts->transfer_kind = $kind;
		$required_target /** breakable_node */ = $target;
		$facts->target = $required_target;
		return $facts;
	}
}

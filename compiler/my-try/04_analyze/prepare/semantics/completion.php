<?php

/* Structured exit composition; no control-flow graph or retained reachability cache. */
namespace scpp\compiler;

final class Completion_Preparation
{
	public static function returned(): statement_completion
	{
		$result = new statement_completion(false);
		$result->can_return = true;
		return $result;
	}

	public static function transfer(control_transfer_kind $kind): statement_completion
	{
		$result = new statement_completion(false);
		$result->can_break = $kind === control_transfer_kind::break_construct;
		$result->can_continue = $kind === control_transfer_kind::continue_loop;
		return $result;
	}

	/** The second statement contributes exits only if the first can reach it. */
	public static function sequence(statement_completion $first, statement_completion $second): statement_completion
	{
		$result = new statement_completion($first->can_fall_through && $second->can_fall_through);
		$result->can_return = $first->can_return || ($first->can_fall_through && $second->can_return);
		$result->can_break = $first->can_break || ($first->can_fall_through && $second->can_break);
		$result->can_continue = $first->can_continue || ($first->can_fall_through && $second->can_continue);
		return $result;
	}

	/** Either branch may execute; preserve each distinct exit possibility. */
	public static function alternatives(statement_completion $left, statement_completion $right): statement_completion
	{
		$result = new statement_completion($left->can_fall_through || $right->can_fall_through);
		$result->can_return = $left->can_return || $right->can_return;
		$result->can_break = $left->can_break || $right->can_break;
		$result->can_continue = $left->can_continue || $right->can_continue;
		return $result;
	}

	/** Selection consumes its breaks, preserving transfers to an enclosing loop. */
	public static function selection(statement_completion $paths, bool $has_default): statement_completion
	{
		$result = new statement_completion(!$has_default || $paths->can_fall_through || $paths->can_break);
		$result->can_return = $paths->can_return;
		$result->can_continue = $paths->can_continue;
		return $result;
	}

	/** Bare transfers reach this loop; nested loops have already consumed their own exits. */
	public static function loop(statement_completion $body, bool $body_first, bool $has_test): statement_completion
	{
		$test_reachable = !$body_first || $body->can_fall_through || $body->can_continue;
		$result = new statement_completion($body->can_break || ($has_test && $test_reachable));
		$result->can_return = $body->can_return;
		return $result;
	}
}

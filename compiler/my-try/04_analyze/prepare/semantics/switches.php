<?php

/* Selection validation, lexical group preparation and nearest break-target scope. */
namespace scpp\compiler;

final class Switch_Preparation
{
	/** Every group is an entry path, including groups reached by preceding fallthrough. */
	public static function prepare(switch_node $syntax, preparation_context $context): statement_completion
	{
		$selector = Expression_Preparation::prepare($syntax->selector, $context);
		if (!$selector->type->matches($context->integer)) {
			throw new \RuntimeException('S2S switch selector requires canonical int');
		}
		$outer_break /** nullable<breakable_node> */ = $context->break_target;
		$context->break_target = $syntax;
		$has_default = false;
		$values /** hash<bool> */ = [];
		$paths = new statement_completion(false);
		try
		{
			$groups /** Storage<switch_case_group> */ = $syntax->groups;
			foreach ($groups as $group)
			{
				$labels /** Storage<switch_label> */ = $group->labels;
				foreach ($labels as $label)
				{
					$label->prepare($context);
					$decimal /** nullable<string> */ = $label->require_preparation()->decimal;
					if ($decimal === null) {
						if ($has_default) { throw new \RuntimeException('S2S switch has duplicate default labels'); }
						$has_default = true;
					}
					else {
						$key = 'integer:' . $decimal;
						if (isset($values[$key])) { throw new \RuntimeException('S2S switch has duplicate case value ' . $decimal); }
						$values[$key] = true;
					}
				}
				$body = $group->body->prepare_completion($context);
				$paths = Completion_Preparation::sequence(
					Completion_Preparation::alternatives($paths, new statement_completion(true)), $body);
			}
			return Completion_Preparation::selection($paths, $has_default);
		}
		finally { $context->break_target = $outer_break; }
	}

	public static function label(switch_label $syntax, preparation_context $context): prepared_switch_label
	{
		$facts = new prepared_switch_label();
		if ($syntax->label_kind === switch_label_kind::fallback) {
			if ($syntax->value !== null) { throw new \LogicException('Default label cannot own a value'); }
			return $facts;
		}
		$value /** expression_node */ = $syntax->value;
		$prepared = Expression_Preparation::prepare($value, $context);
		if (!$prepared->type->matches($context->integer)) {
			throw new \RuntimeException('S2S switch case requires canonical int');
		}
		$facts->decimal = $value->integer_constant();
		return $facts;
	}
}

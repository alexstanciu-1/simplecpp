<?php

/* Selection validation, lexical group preparation and nearest break-target scope. */
namespace scpp\compiler;

final class Switch_Preparation
{
	/** Every group is an entry path, including groups reached by preceding fallthrough. */
	public static function prepare(switch_node $syntax, preparation_context $context): statement_completion
	{
		$selector = Expression_Preparation::prepare($syntax->selector, $context);
		Selection_Preparation::require_selector($selector->type, $context, 'switch');
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
					$label->set_preparation(self::label($label, $selector->type, $context));
					$value /** nullable<prepared_selection_constant> */ = $label->require_preparation()->value;
					if ($value === null) {
						if ($has_default) { throw new \RuntimeException('S2S switch has duplicate default labels'); }
						$has_default = true;
					}
					else {
						$key = $value->value_key();
						if (isset($values[$key])) { throw new \RuntimeException('S2S switch has duplicate case value ' . $key); }
						$values[$key] = true;
					}
				}
				$body = Body_Preparation::prepare_block($group->body, $context);
				$paths = Completion_Preparation::sequence(
					Completion_Preparation::alternatives($paths, new statement_completion(true)), $body);
			}
			return Completion_Preparation::selection($paths, $has_default);
		}
		finally { $context->break_target = $outer_break; }
	}

	public static function label(switch_label $syntax, canonical_type_use $selector, preparation_context $context): prepared_switch_label
	{
		$facts = new prepared_switch_label();
		if ($syntax->label_kind === switch_label_kind::fallback) {
			if ($syntax->value !== null) { throw new \LogicException('Default label cannot own a value'); }
			return $facts;
		}
		$value /** expression_node */ = $syntax->value;
		$facts->value = Selection_Preparation::label($value, $selector, $context, 'switch case');
		return $facts;
	}
}

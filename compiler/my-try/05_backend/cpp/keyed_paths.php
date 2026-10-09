<?php

/* Lazy keys with repeatable prefix resolvers, not retained interior pointers. */
namespace scpp\compiler;

final class CPP_Keyed_Path
{
	public static function generate(prepared_keyed_path $path, cpp_generation_context $context,
		bool $removing, bool $emptiness = false, ?truthiness_decision $presence = null): string
	{
		$context->headers['scpp/keyed_path.hpp'] = true;
		$id = $context->next_temporary;
		$context->next_temporary++;
		$emitter = new CPP_Syntax($context);
		$root = 'keyed_root_' . $id;
		$steps /** Storage<prepared_keyed_step> */ = $path->steps;
		$root_type = CPP_Declarations::type($path->root->require_preparation()->type, $context);
		$mode = $removing ? 'true' : 'false';
		$result_type = $removing ? 'void' : 'scpp::bool_t';
		$stop = 'return;';
		if (!$removing) { $stop = 'return scpp::bool_t{' . ($emptiness ? 'true' : 'false') . '};'; }
		if (!$removing && $emptiness) { $stop = 'scpp::detail::throw_invalid_empty();'; }
		$text = '([&]() -> ' . $result_type . ' { ' . ($removing ? 'auto& ' : 'const auto& ')
			. $root . ' = ' . $emitter->expression($path->root) . '; ';
		$resolver = 'keyed_resolve_' . $id . '_0';
		$text .= 'auto ' . $resolver . ' = [&]() -> const ' . $root_type . '* { return &' . $root . '; }; ';
		$position = 0;
		$last = $steps->count() - 1;
		foreach ($steps as $step)
		{
			$text .= 'if (!scpp::keyed_ready(' . $resolver . '(), ' . $mode . ')) { ' . $stop . ' } ';
			$key = 'keyed_key_' . $id . '_' . $position;
			$key_syntax /** expression_node */ = $step->syntax->index;
			$conversion = $step->key_conversion;
			$text .= CPP_Declarations::type($conversion->target_type, $context) . ' ' . $key . ' = '
				. CPP_Declarations::conversion($emitter->expression($key_syntax), $conversion, $context) . '; ';
			if ($position !== $last)
			{
				$next = 'keyed_resolve_' . $id . '_' . ($position + 1);
				$text .= 'auto ' . $next . ' = [&]() -> const ' . CPP_Declarations::type($step->element_type, $context)
					. '* { return scpp::keyed_child(' . $resolver . '(), ' . $key . ', ' . $mode . '); }; ';
				$resolver = $next;
			}
			elseif (!$removing) {
				if ($presence !== null) {
					$leaf = 'keyed_leaf_' . $id;
					$text .= 'const auto* ' . $leaf . ' = scpp::keyed_lookup(' . $resolver . '(), ' . $key . ', false); ';
					$text .= 'if (!' . $leaf . ') { return scpp::bool_t{false}; } return scpp::bool_t('
						. CPP_Declarations::truthiness('(*' . $leaf . ')', $presence, $context) . '); ';
				}
				else {
					$text .= 'return scpp::keyed_probe(' . $resolver . '(), ' . $key . ', ' . ($emptiness ? 'true' : 'false') . '); ';
				}
			}
			else
			{
				$text .= 'if (!scpp::keyed_lookup(' . $resolver . '(), ' . $key . ', true)) { return; } ';
				$parent = $root;
				for ($prefix = 0; $prefix < $last; $prefix++)
				{
					$child = 'keyed_target_' . $id . '_' . $prefix;
					$text .= 'auto& ' . $child . ' = scpp::keyed_descend(' . $parent . ', keyed_key_' . $id . '_' . $prefix . '); ';
					$parent = $child;
				}
				$text .= 'scpp::keyed_erase(' . $parent . ', ' . $key . '); ';
			}
			$position++;
		}
		return $text . '}())';
	}
}

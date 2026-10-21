<?php

/* Lazy keys with repeatable prefix resolvers, not retained interior pointers. */
namespace scpp\compiler;

final class CPP_Keyed_Path
{
	public static function generate(prepared_keyed_path $path, cpp_generation_context $context,
		bool $removing, bool $emptiness = false, ?truthiness_decision $presence = null,
		?canonical_type_use $result = null): string
	{
		if ($context->specialization_engine !== null && !CPP_Concrete_Index::active($context)) {
			throw new \RuntimeException('prelower_templates: keyed probes/removal require a concrete adapter');
		}
		if (!CPP_Concrete_Index::active($context)) { $context->headers['scpp/keyed_path.hpp'] = true; }
		$id = $context->next_temporary;
		$context->next_temporary++;
		$emitter = new CPP_Syntax($context);
		$root = 'keyed_root_' . $id;
		$steps /** Storage<prepared_keyed_step> */ = $path->steps;
		$root_type = CPP_Declarations::type($path->root->require_preparation()->type, $context);
		$mode = $removing ? 'true' : 'false';
		if (!$removing && $result === null) { throw new \LogicException('Keyed probe requires its prepared result type'); }
		$result_type = $removing ? 'void' : CPP_Declarations::type($result, $context);
		$stop = CPP_Sequence::result('', $context);
		if (!$removing) { $stop = CPP_Sequence::result($result_type . '{' . ($emptiness ? 'true' : 'false') . '}', $context); }
		if (!$removing && $emptiness) { $stop = 'scpp::detail::throw_invalid_empty();'; }
		$binding = CPP_Declarations::bind_storage_receiver($path->root, $context);
		$text = CPP_Sequence::open($result_type, $context) . $binding->statements . ($removing ? 'auto& ' : 'const auto& ')
			. $root . ' = ' . $binding->value . '; ';
		$resolver = 'keyed_resolve_' . $id . '_0';
		$text .= 'auto ' . $resolver . ' = [&]() -> const ' . $root_type . '* { return &' . $root . '; }; ';
		$current_type = $path->root->require_preparation()->type;
		$position = 0;
		$last = $steps->count() - 1;
		foreach ($steps as $step)
		{
			if (CPP_Concrete_Index::active($context)) {
				CPP_Concrete_Index::keyed_step($current_type, $step, $position === $last, $removing, $presence !== null, $context);
			}
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
					$text .= 'if (!' . $leaf . ') { ' . CPP_Sequence::result($result_type . '{false}', $context) . '} ' . CPP_Sequence::result($result_type . '('
						. CPP_Declarations::truthiness('(*' . $leaf . ')', $presence, $context) . ')', $context);
				}
				else {
					$text .= CPP_Sequence::result($result_type . '(scpp::keyed_probe(' . $resolver . '(), ' . $key . ', ' . ($emptiness ? 'true' : 'false') . '))', $context);
				}
			}
			else
			{
				$text .= 'if (!scpp::keyed_lookup(' . $resolver . '(), ' . $key . ', true)) { ' . CPP_Sequence::result('', $context) . '} ';
				$parent = $root;
				for ($prefix = 0; $prefix < $last; $prefix++)
				{
					$child = 'keyed_target_' . $id . '_' . $prefix;
					$text .= 'auto& ' . $child . ' = scpp::keyed_descend(' . $parent . ', keyed_key_' . $id . '_' . $prefix . '); ';
					$parent = $child;
				}
				$text .= 'scpp::keyed_erase(' . $parent . ', ' . $key . '); ';
			}
			$current_type = $step->element_type;
			$position++;
		}
		if ($removing) { $text .= CPP_Sequence::result('', $context); }
		return $text . CPP_Sequence::close($context);
	}
}

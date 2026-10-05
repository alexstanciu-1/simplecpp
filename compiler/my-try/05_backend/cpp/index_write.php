<?php

/* Sequenced wrapper mutation; no interior reference survives key/RHS evaluation. */
namespace scpp\compiler;

final class CPP_Index_Write
{
	/** The same lowering serves every registered family; runtime overloads own storage details. */
	public static function generate(assignment_expression_node $node, prepared_index_assignment $facts,
		cpp_generation_context $context, bool $value_required = true): string
	{
		$target = object_cast($node->target, index_node::class);
		$emitter = new CPP_Syntax($context);
		$context->headers['scpp/index_write.hpp'] = true;
		$id = $context->next_temporary;
		$context->next_temporary++;
		$receiver = 'write_receiver_' . $id;
		$key = 'write_key_' . $id;
		$value = 'write_value_' . $id;
		$type = CPP_Declarations::type($facts->type, $context);
		$text = '{ auto& ' . $receiver . ' = ' . $emitter->expression($facts->root) . '; ';
		$path /** Storage<prepared_index_path_step> */ = $facts->path;
		$position = 0;
		foreach ($path as $step)
		{
			$path_key = 'write_path_key_' . $id . '_' . $position;
			$key_syntax /** expression_node */ = $step->syntax->index;
			$path_conversion = $step->key_conversion;
			$text .= CPP_Declarations::type($path_conversion->target_type, $context) . ' ' . $path_key . ' = '
				. CPP_Declarations::conversion($emitter->expression($key_syntax), $path_conversion, $context) . '; ';
			$text .= 'scpp::require_write_key(' . $path_key . '); ';
			$position++;
		}
		$helper = 'scpp::index_append';
		$arguments = $receiver . ', ';
		if ($facts->decision->operation === operator_operation::index_store)
		{
			$key_node /** expression_node */ = $target->index;
			$conversion = $facts->decision->operands[1];
			$text .= CPP_Declarations::type($conversion->target_type, $context) . ' ' . $key . ' = '
				. CPP_Declarations::conversion($emitter->expression($key_node), $conversion, $context) . '; ';
			$text .= 'scpp::require_write_key(' . $key . '); ';
			$helper = 'scpp::index_store';
			$arguments .= $key . ', ';
		}
		elseif ($facts->decision->operation !== operator_operation::index_append) {
			throw new \LogicException('Unsupported prepared indexed write');
		}
		$text .= $type . ' ' . $value . ' = '
			. CPP_Declarations::conversion($emitter->expression($node->value), $facts->value_conversion, $context) . '; ';
		if (!$path->is_empty())
		{
			$context->headers['scpp/index_write_path.hpp'] = true;
			$probe = $receiver;
			$mutable = $receiver;
			$checks = '{ ';
			$descent = '';
			$position = 0;
			foreach ($path as $step)
			{
				$path_key = 'write_path_key_' . $id . '_' . $position;
				$probe_name = 'write_probe_' . $id . '_' . $position;
				$mutable_name = 'write_target_' . $id . '_' . $position;
				$element = CPP_Declarations::type($step->element_type, $context);
				$checks .= 'const ' . $element . '& ' . $probe_name . ' = scpp::index_path_probe(' . $probe . ', ' . $path_key . '); ';
				$descent .= $element . '& ' . $mutable_name . ' = scpp::index_path_descend(' . $mutable . ', ' . $path_key . '); ';
				$probe = $probe_name;
				$mutable = $mutable_name;
				$position++;
			}
			$check_helper = 'scpp::index_path_check_append';
			$leaf_arguments = $value;
			if ($facts->decision->operation === operator_operation::index_store) {
				$check_helper = 'scpp::index_path_check_store';
				$leaf_arguments = $key . ', ' . $value;
			}
			$text .= $checks . $check_helper . '(' . $probe . ', ' . $leaf_arguments . '); } ' . $descent;
			$arguments = $mutable . ', ';
			if ($facts->decision->operation === operator_operation::index_store) {
				$arguments .= $key . ', ';
			}
		}
		$text .= $helper . '(' . $arguments . $value . '); ';
		if (!$value_required) {
			return $text . '}';
		}
		return '([&]() -> ' . $type . ' ' . $text . 'return ' . $value . '; }())';
	}
}

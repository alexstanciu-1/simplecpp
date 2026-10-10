<?php

/* Sequenced wrapper mutation; no interior reference survives key/RHS evaluation. */
namespace scpp\compiler;

final class CPP_Index_Write
{
	/** Preserve key values and an element copy, never an interior reference across RHS code. */
	public static function generate_compound(compound_assignment_expression_node $node,
		prepared_compound_assignment_expression $facts, cpp_generation_context $context): string
	{
		$destination /** prepared_index_target */ = $facts->indexed_target;
		$target = object_cast($node->target, index_node::class);
		$context->headers['scpp/index_existing.hpp'] = true;
		$id = $context->next_temporary;
		$context->next_temporary++;
		$receiver = 'update_receiver_' . $id;
		$previous = 'update_previous_' . $id;
		$root = CPP_Declarations::bind_storage_receiver($destination->root, $context);
		$text = $root->statements . 'auto& ' . $receiver . ' = ' . $root->value . '; ';
		$keys = '';
		$position = 0;
		$emitter = new CPP_Syntax($context);
		$path /** Storage<prepared_index_path_step> */ = $destination->path;
		foreach ($path as $step) {
			$key = 'update_key_' . $id . '_' . $position;
			$key_syntax /** expression_node */ = $step->syntax->index;
			$text .= 'auto ' . $key . ' = ' . CPP_Declarations::conversion(
				$emitter->expression($key_syntax), $step->key_conversion, $context) . '; scpp::require_write_key(' . $key . '); ';
			$keys .= ', ' . $key;
			$position++;
		}
		$key = 'update_key_' . $id . '_' . $position;
		$key_syntax /** expression_node */ = $target->index;
		$text .= 'auto ' . $key . ' = ' . CPP_Declarations::conversion(
			$emitter->expression($key_syntax), $destination->decision->operands[1], $context)
			. '; scpp::require_write_key(' . $key . '); ';
		$keys .= ', ' . $key;
		$text .= 'auto ' . $previous . ' = scpp::index_existing_read(' . $receiver . $keys . '); ';
		$text .= CPP_Generator::compute_compound($facts, $previous, $emitter->expression($node->value), $context);
		$text .= 'scpp::index_existing_store(' . $receiver . ', ' . $previous . $keys . '); ';
		return '([&]() -> ' . CPP_Declarations::type($facts->type, $context) . ' { '
			. $text . 'return ' . $previous . '; }())';
	}

	/** The same lowering serves every registered family; runtime overloads own storage details. */
	public static function generate(assignment_expression_node $node, prepared_index_assignment $facts,
		cpp_generation_context $context, bool $value_required = true): string
	{
		$target = object_cast($node->target, index_node::class);
		$destination = $facts->target;
		$emitter = new CPP_Syntax($context);
		$context->headers['scpp/index_write.hpp'] = true;
		$id = $context->next_temporary;
		$context->next_temporary++;
		$receiver = 'write_receiver_' . $id;
		$key = 'write_key_' . $id;
		$value = 'write_value_' . $id;
		$type = CPP_Declarations::type($facts->destination_type(), $context);
		$root = CPP_Declarations::bind_storage_receiver($destination->root, $context);
		$text = '{ ' . $root->statements . 'auto& ' . $receiver . ' = ' . $root->value . '; ';
		$path /** Storage<prepared_index_path_step> */ = $destination->path;
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
		if ($destination->decision->operation === operator_operation::index_store)
		{
			$key_node /** expression_node */ = $target->index;
			$conversion = $destination->decision->operands[1];
			$text .= CPP_Declarations::type($conversion->target_type, $context) . ' ' . $key . ' = '
				. CPP_Declarations::conversion($emitter->expression($key_node), $conversion, $context) . '; ';
			$text .= 'scpp::require_write_key(' . $key . '); ';
			$helper = 'scpp::index_store';
			$arguments .= $key . ', ';
		}
		elseif ($destination->decision->operation !== operator_operation::index_append) {
			throw new \LogicException('Unsupported prepared indexed write');
		}
		$text .= $type . ' ' . $value . ' = '
			. CPP_Declarations::conversion($emitter->expression($node->value), $facts->value_conversion, $context) . '; ';
		if (!$path->is_empty())
		{
			$context->headers['scpp/index_write_path.hpp'] = true;
			$probe = $receiver;
			$mutable_receiver = $receiver;
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
				$descent .= $element . '& ' . $mutable_name . ' = scpp::index_path_descend(' . $mutable_receiver . ', ' . $path_key . '); ';
				$probe = $probe_name;
				$mutable_receiver = $mutable_name;
				$position++;
			}
			$check_helper = 'scpp::index_path_check_append';
			$leaf_arguments = $value;
			if ($destination->decision->operation === operator_operation::index_store) {
				$check_helper = 'scpp::index_path_check_store';
				$leaf_arguments = $key . ', ' . $value;
			}
			$text .= $checks . $check_helper . '(' . $probe . ', ' . $leaf_arguments . '); } ' . $descent;
			$arguments = $mutable_receiver . ', ';
			if ($destination->decision->operation === operator_operation::index_store) {
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

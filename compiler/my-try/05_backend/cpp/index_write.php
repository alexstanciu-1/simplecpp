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
		if ($context->specialization_engine !== null) {
			throw new \RuntimeException('prelower_templates: indexed compound assignment is not in the first slice');
		}
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
		if (($context->specialization_engine !== null) && !$context->runtime_abi() && !CPP_Concrete_Index::active($context)) {
			return CPP_Concrete_Operations::write($node, $facts, $context, $value_required);
		}
		if (CPP_Concrete_Index::active($context)) { CPP_Concrete_Index::write($facts, $context); }
		$target = object_cast($node->target, index_node::class);
		$destination = $facts->target;
		$emitter = new CPP_Syntax($context);
		if (!$context->runtime_abi() && !CPP_Concrete_Index::active($context)) { $context->headers['scpp/index_write.hpp'] = true; }
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
		$keys /** vector<string> */ = [];
		foreach ($path as $step)
		{
			$path_key = 'write_path_key_' . $id . '_' . $position;
			$key_syntax /** expression_node */ = $step->syntax->index;
			$path_conversion = $step->key_conversion;
			$text .= CPP_Declarations::type($path_conversion->target_type, $context) . ' ' . $path_key . ' = '
				. CPP_Declarations::conversion($emitter->expression($key_syntax), $path_conversion, $context) . '; ';
			$keys[] = $path_key;
			$text .= self::key_check($path_conversion->target_type, $path_key, $context) . '; ';
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
			$keys[] = $key;
			$text .= self::key_check($conversion->target_type, $key, $context) . '; ';
			$helper = 'scpp::index_store';
			$arguments .= $key . ', ';
		}
		elseif ($destination->decision->operation !== operator_operation::index_append) {
			throw new \LogicException('Unsupported prepared indexed write');
		}
		$text .= $type . ' ' . $value . ' = '
			. CPP_Declarations::conversion($emitter->expression($node->value), $facts->value_conversion, $context) . '; ';
		if ($context->runtime_abi()) {
			$text .= CPP_Runtime_Operations::write($facts, $receiver, $keys, $value, $context) . '; ';
			return $value_required ? '([&]() -> ' . $type . ' ' . $text . 'return ' . $value . '; }())' : $text . '}';
		}
		if (!$path->is_empty()) {
			$elements /** Storage<canonical_type_use> */ = new Storage();
			foreach ($path as $step) { $elements->append($step->element_type); }
			$resolved = self::resolve_path($elements, $keys, $receiver, $key, $value,
				$destination->decision->operation === operator_operation::index_append, $context);
			$text .= $resolved->statements;
			$arguments = $resolved->value . ', ';
			if ($destination->decision->operation === operator_operation::index_store) { $arguments .= $key . ', '; }
		}

		$text .= $helper . '(' . $arguments . $value . '); ';
		if (!$value_required) {
			return $text . '}';
		}
		return '([&]() -> ' . $type . ' ' . $text . 'return ' . $value . '; }())';
	}
	/** Shared prepared-path lowering for ordinary code and native ABI bridges. */
	public static function resolve_path(Storage $elements /** Storage<canonical_type_use> */,
		array $keys /** vector<string> */, string $root, string $key, string $value, bool $append,
		cpp_generation_context $context): cpp_assignment_sequence
	{
		$result = new cpp_assignment_sequence();
		$result->value = $root;
		if ($elements->is_empty()) { return $result; }
		if (!CPP_Concrete_Index::active($context)) { $context->headers['scpp/index_write_path.hpp'] = true; }
		$id = $context->next_temporary++;
		$probe = $root;
		$checks = '';
		$descent = '';
		foreach ($elements as $position => $element) {
			$type = CPP_Declarations::type($element, $context);
			$probe_name = 'write_probe_' . $id . '_' . $position;
			$mutable_name = 'write_target_' . $id . '_' . $position;
			$checks .= 'const ' . $type . '& ' . $probe_name . ' = scpp::index_path_probe(' . $probe . ', ' . $keys[$position] . '); ';
			$descent .= $type . '& ' . $mutable_name . ' = scpp::index_path_descend(' . $result->value . ', ' . $keys[$position] . '); ';
			$probe = $probe_name;
			$result->value = $mutable_name;
		}
		$leaf = $append ? $value : $key . ', ' . $value;
		$result->statements = '{ ' . $checks . 'scpp::index_path_check_' . ($append ? 'append' : 'store')
			. '(' . $probe . ', ' . $leaf . '); } ' . $descent;
		return $result;
	}

	private static function key_check(canonical_type_use $type, string $key, cpp_generation_context $context): string
	{
		if ($context->runtime_abi()) { return CPP_Runtime_Operations::write_key($type, $key, $context); }
		return 'scpp::require_write_key(' . $key . ')';
	}

}

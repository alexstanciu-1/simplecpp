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
		if (($context->specialization_engine !== null) && !CPP_Concrete_Index::active($context)) {
			throw new \RuntimeException('prelower_templates: indexed compound assignment is not in the first slice');
		}
		$target = object_cast($node->target, index_node::class);
		if (!CPP_Concrete_Index::active($context)) {
			$context->headers['scpp/index_existing.hpp'] = true;
		}
		$id = $context->next_temporary;
		$context->next_temporary++;
		$receiver = 'update_receiver_' . $id;
		$previous = 'update_previous_' . $id;
		$root = CPP_Declarations::bind_storage_receiver($destination->root, $context);
		$text = $root->statements . 'auto& ' . $receiver . ' = ' . $root->value . '; ';
		$keys /** vector<string> */ = [];
		$position = 0;
		$emitter = new CPP_Syntax($context);
		$path /** Storage<prepared_index_path_step> */ = $destination->path;
		foreach ($path as $step) {
			$key = 'update_key_' . $id . '_' . $position;
			$key_syntax /** expression_node */ = $step->syntax->index;
			$text .= 'auto ' . $key . ' = ' . CPP_Declarations::conversion(
				$emitter->expression($key_syntax), $step->key_conversion, $context) . '; '
				. self::key_check($step->key_conversion->target_type, $key, $context) . '; ';
			$keys[] = $key;
			$position++;
		}
		$key = 'update_key_' . $id . '_' . $position;
		$key_syntax /** expression_node */ = $target->index;
		$text .= 'auto ' . $key . ' = ' . CPP_Declarations::conversion(
			$emitter->expression($key_syntax), $destination->decision->operands[1], $context)
			. '; ' . self::key_check($destination->decision->operands[1]->target_type, $key, $context) . '; ';
		$keys[] = $key;
		$text .= self::existing_snapshot($destination, $receiver, $keys, $previous, $context);
		$text .= CPP_Generator::compute_compound($facts, $previous, $emitter->expression($node->value), $context);
		$text .= self::existing_store($destination, $receiver, $keys, $previous, $context);
		return '([&]() -> ' . CPP_Declarations::type($facts->type, $context) . ' { '
			. $text . 'return ' . $previous . '; }())';
	}

	/** Append already evaluated keys to a runtime helper call. */
	private static function key_arguments(array $keys /** vector<string> */): string
	{
		$result = '';
		foreach ($keys as $key) {
			$result .= ', ' . $key;
		}
		return $result;
	}

	/** Snapshot within a scope that releases every interior borrow before source RHS evaluation. */
	private static function existing_snapshot(prepared_index_target $destination, string $receiver,
		array $keys /** vector<string> */, string $previous, cpp_generation_context $context): string
	{
		if (!CPP_Concrete_Index::active($context)) {
			return 'auto ' . $previous . ' = scpp::index_existing_read(' . $receiver . self::key_arguments($keys) . '); ';
		}
		$probe = self::existing_probe($destination, $receiver, $keys, $context);
		return 'auto ' . $previous . ' = [&]() { ' . $probe->statements . 'return ' . $probe->value . '; }(); ';
	}

	/** Validate the full path before mutable descent; reuse runtime storage/COW operations. */
	private static function existing_store(prepared_index_target $destination, string $receiver,
		array $keys /** vector<string> */, string $value, cpp_generation_context $context): string
	{
		if (!CPP_Concrete_Index::active($context)) {
			return 'scpp::index_existing_store(' . $receiver . ', ' . $value . self::key_arguments($keys) . '); ';
		}
		$probe = self::existing_probe($destination, $receiver, $keys, $context);
		$text = '{ ' . $probe->statements . '(void)' . $probe->value . '; } ';
		$current = $destination->root->require_preparation()->type;
		foreach ($destination->path as $position => $step)
		{
			$operand_types /** vector<canonical_type_use> */ = [$current, $step->key_conversion->target_type];
			$helper = CPP_Concrete_Index::reference('index_path_descend', $operand_types, $step->element_type, $context);
			$child = 'update_child_' . $context->next_temporary++;
			$text .= 'auto& ' . $child . ' = ' . $helper . '(' . $receiver . ', ' . $keys[$position] . '); ';
			$receiver = $child;
			$current = $step->element_type;
		}
		$operand_types /** vector<canonical_type_use> */ = [$current, $destination->decision->operands[1]->target_type,
			$destination->decision->result_type];
		$helper = CPP_Concrete_Index::reference('index_store', $operand_types, null, $context);
		return $text . $helper . '(' . $receiver . ', ' . $keys[q_count($keys) - 1] . ', ' . $value . '); ';
	}

	/** Existing-only traversal uses settled path types and never creates or detaches storage. */
	private static function existing_probe(prepared_index_target $destination, string $receiver,
		array $keys /** vector<string> */, cpp_generation_context $context): cpp_assignment_sequence
	{
		$result = new cpp_assignment_sequence();
		$current = $destination->root->require_preparation()->type;
		foreach ($destination->path as $position => $step)
		{
			$operand_types /** vector<canonical_type_use> */ = [$current, $step->key_conversion->target_type];
			$helper = CPP_Concrete_Index::reference('index_existing_probe', $operand_types, $step->element_type, $context);
			$child = 'update_probe_' . $context->next_temporary++;
			$result->statements .= 'const auto& ' . $child . ' = ' . $helper . '(' . $receiver . ', ' . $keys[$position] . '); ';
			$receiver = $child;
			$current = $step->element_type;
		}
		$operand_types /** vector<canonical_type_use> */ = [$current, $destination->decision->operands[1]->target_type];
		$helper = CPP_Concrete_Index::reference('index_existing_probe', $operand_types,
			$destination->decision->result_type, $context);
		$result->value = $helper . '(' . $receiver . ', ' . $keys[q_count($keys) - 1] . ')';
		return $result;
	}

	/** The same lowering serves every registered family; runtime overloads own storage details. */
	public static function generate(assignment_expression_node $node, prepared_index_assignment $facts,
		cpp_generation_context $context, bool $value_required = true): string
	{
		if (CPP_Concrete_Index::active($context)) { CPP_Concrete_Index::write($facts, $context); }
		$target = object_cast($node->target, index_node::class);
		$destination = $facts->target;
		$emitter = new CPP_Syntax($context);
		if (!CPP_Concrete_Index::active($context)) { $context->headers['scpp/index_write.hpp'] = true; }
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

	/** Borrow the evaluated key; validation stays before RHS evaluation in both write paths. */
	private static function key_check(canonical_type_use $type, string $key, cpp_generation_context $context): string
	{
		if (CPP_Concrete_Index::active($context)) {
			return CPP_Runtime_Operations::write_key($type, $key, $context);
		}
		return 'scpp::require_write_key(' . $key . ')';
	}

}

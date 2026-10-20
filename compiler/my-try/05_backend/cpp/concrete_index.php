<?php
namespace scpp\compiler;

/** Physical interfaces for existing runtime operations; emitters retain sequencing ownership. */
final class CPP_Concrete_Index
{
	public static function active(cpp_generation_context $context): bool
	{
		return $context->concrete_types();
	}

	public static function reference(string $helper, array $types /** vector<canonical_type_use> */,
		?canonical_type_use $result, cpp_generation_context $context): string
	{
		$receiver = CPP_Types::representation($types[0], $context);
		if (!$receiver->opaque) {
			$context->headers[$helper === 'index_existing_probe' ? 'scpp/index_existing.hpp' : 'scpp/keyed_path.hpp'] = true;
			return 'scpp::' . $helper;
		}
		$request = new cpp_runtime_operation(cpp_runtime_operation_kind::concrete_index);
		$request->spelling = $helper;
		$request->result = $result;
		$inputs /** Storage<canonical_type_use> */ = $request->inputs;
		foreach ($types as $type) { $inputs->append($type); }
		CPP_Runtime_Operations::reference($request, $context);
		return 'scpp::' . $helper;
	}

	/** Demand physical interfaces from settled path facts; source evaluation stays in CPP_Index_Write. */
	public static function write(prepared_index_assignment $facts, cpp_generation_context $context): void
	{
		$destination = $facts->target;
		$current = $destination->root->require_preparation()->type;
		$path /** Storage<prepared_index_path_step> */ = $destination->path;
		foreach ($path as $step) {
			$key = $step->key_conversion->target_type;
			$operand_types /** vector<canonical_type_use> */ = [$current, $key];
			self::reference('index_path_probe', $operand_types, $step->element_type, $context);
			self::reference('index_path_descend', $operand_types, $step->element_type, $context);
			$current = $step->element_type;
		}
		$append = $destination->decision->operation === operator_operation::index_append;
		$inputs /** vector<canonical_type_use> */ = [$current];
		if (!$append) {
			$key = $destination->decision->operands[1]->target_type;
			$inputs[] = $key;
		}
		$inputs[] = $facts->destination_type();
		if (!$path->is_empty()) {
			self::reference($append ? 'index_path_check_append' : 'index_path_check_store', $inputs, null, $context);
		}
		self::reference($append ? 'index_append' : 'index_store', $inputs, null, $context);
	}

	public static function keyed_step(canonical_type_use $receiver, prepared_keyed_step $step, bool $last,
		bool $removing, bool $presence, cpp_generation_context $context): void
	{
		$operand_types /** vector<canonical_type_use> */ = [$receiver];
		self::reference('keyed_ready', $operand_types, null, $context);
		$inputs /** vector<canonical_type_use> */ = [$receiver, $step->key_conversion->target_type];
		self::reference('keyed_lookup', $inputs, $step->element_type, $context);
		if (!$last) {
			self::reference('keyed_child', $inputs, $step->element_type, $context);
			if ($removing) { self::reference('keyed_descend', $inputs, $step->element_type, $context); }
		}
		elseif ($removing) { self::reference('keyed_erase', $inputs, null, $context); }
		elseif (!$presence) { self::reference('keyed_probe', $inputs, null, $context); }
	}

	public static function entry(cpp_runtime_operation $request, CPP_Specializations $engine): cpp_specialization_entry
	{
		$name = CPP_Runtime_Operations::name($request);
		$existing = $engine->find($name);
		if ($existing !== null) { return $existing; }
		$helper = $request->spelling;
		$inputs /** Storage<canonical_type_use> */ = $request->inputs;
		$mixed_read = ($helper === 'null_index_read')
			&& (Type_Queries::canonical($inputs[0])->definition() instanceof mixed_semantics);
		$pointer = ($helper === 'keyed_ready') || ($helper === 'keyed_lookup') || ($helper === 'keyed_child') || ($helper === 'keyed_probe');
		$mutable_receiver = ($helper === 'index_store') || ($helper === 'index_append') || ($helper === 'index_path_descend')
			|| ($helper === 'keyed_descend') || ($helper === 'keyed_erase');
		$context = new cpp_generation_context($engine);
		$parameters = '';
		$arguments = '';
		$separator = '';
		$native_headers /** hash<bool> */ = [];
		foreach ($request->inputs as $position => $type) {
			$mapping = CPP_Types::representation($type, $context);
			CPP_Types::record_dependencies($mapping, $context);
			$qualifier = ($position === 0 && $mutable_receiver) ? '' : 'const ';
			$indirection = ($position === 0 && $pointer) ? '*' : '&';
			$operand = 'a' . $position;
			$parameters .= $separator . $qualifier . $mapping->spelling . $indirection . ' ' . $operand;
			if (($position === 0 || $mixed_read) && $mapping->opaque) {
				$native = CPP_Types::implementation_representation($type, $engine);
				foreach ($native->headers as $header => $used) { $native_headers[$header] = true; }
				$address = $operand . ($pointer ? '->' : '.') . 'address()';
				$unwrapped = 'std::launder(static_cast<' . $qualifier . $native->spelling . '*>' . '(' . $address . '))';
				$operand = $pointer ? '(' . $operand . ' ? ' . $unwrapped . ' : nullptr)' : '*' . $unwrapped;
			}
			$arguments .= $separator . $operand;
			$separator = ', ';
		}
		if ($pointer) { $parameters .= ', bool mode'; $arguments .= ', mode'; }
		$output = 'void';
		if ($helper === 'keyed_ready') { $output = 'bool'; }
		if ($helper === 'keyed_probe') { $output = 'bool'; }
		if ($request->result !== null) {
			$mapping = CPP_Types::representation($request->result, $context);
			CPP_Types::record_dependencies($mapping, $context);
			$output = $mapping->spelling;
			if (($helper === 'index_path_probe') || ($helper === 'index_existing_probe')) { $output = 'const ' . $output . '&'; }
			if ($helper === 'index_path_descend' || $helper === 'keyed_descend') { $output .= '&'; }
			if ($helper === 'keyed_lookup' || $helper === 'keyed_child') { $output = 'const ' . $output . '*'; }
		}
		$signature = $output . ' ' . $helper . '(' . $parameters . ')';
		$declaration = "#pragma once\n" . CPP_Artifacts::includes($context->headers, '') . 'namespace scpp { ' . $signature . "; }\n";
		$operation = 'return scpp::' . $helper . '(' . $arguments . ')'
			. ($helper === 'keyed_probe' ? '.native_value()' : '') . ';';
		if ($mixed_read) {
			$result /** canonical_type_use */ = $request->result;
			$native_result = CPP_Types::implementation_representation($result, $engine);
			foreach ($native_result->headers as $header => $used) { $native_headers[$header] = true; }
			$native_headers['type_traits'] = true;
			$native_headers['new'] = true;
			$native_headers['scpp/index_read.hpp'] = true;
			$operation = CPP_Runtime_Operations::concrete_call_result($output, $native_result->spelling, '',
				'scpp::' . $helper . '(' . $arguments . ')');
		}
		$body = CPP_Artifacts::includes($native_headers, '') . "#include <memory>\n#include <scpp/keyed_path.hpp>\n#include <scpp/index_existing.hpp>\nnamespace scpp { "
			. $signature . ' { ' . $operation . " } }\n";
		$layout_dependencies /** hash<bool> */ = [];
		return new cpp_specialization_entry($name, 'specializations/' . $name . '.hpp', $declaration, $body, $layout_dependencies);
	}
}

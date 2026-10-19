<?php
namespace scpp\compiler;

/** Runtime ABI bridges consume selected contracts; family algorithms remain in the runtime. */
final class CPP_Runtime_Operations
{
	private static function join(array $parts /** vector<string> */): string
	{
		$result = '';
		$separator = '';
		foreach ($parts as $part) { $result .= $separator . $part; $separator = ', '; }
		return $result;
	}

	public static function name(cpp_runtime_operation $request): string
	{
		$key = $request->kind_name() . ':' . $request->spelling . ':' . ($request->append ? 'append' : 'store');
		if ($request->call !== null) { $key .= ':' . CPP_Source_Calls::identity($request->call); }
		if ($request->construction !== null) {
			$key .= ':payload=' . CPP_Opaque_Representation::identity($request->construction->constructed_type)
				. ':storage=' . enum_name($request->construction->storage);
		}
		if ($request->binding !== null) { $key .= ':' . $request->binding->target()->key() . ':' . $request->binding->symbol() . ':' . $request->binding->header(); }
		if ($request->initialization !== null) { $key .= ':' . CPP_Data_Construction::binding_identity($request->initialization); }
		if ($request->decision !== null) { $key .= ':' . $request->decision->operation_name(); }
		if ($request->conversion !== null) { $key .= ':' . $request->conversion->operation_name(); }
		if ($request->result !== null) { $key .= ':result=' . CPP_Opaque_Representation::identity($request->result); }
		foreach ($request->inputs as $input) { $key .= ':input=' . CPP_Opaque_Representation::identity($input); }
		foreach ($request->path as $element) { $key .= ':path=' . CPP_Opaque_Representation::identity($element); }
		return 'runtime_' . $request->kind_name() . '_' . content_sha256($key);
	}

	/** Inputs designate existing objects. Calling code owns sequencing and lifetime. */
	public static function invoke(cpp_runtime_operation $request, array $expressions /** vector<string> */,
		cpp_generation_context $context): string
	{
		$entry = self::reference($request, $context);
		$inputs /** Storage<canonical_type_use> */ = $request->inputs;
		$parts /** vector<string> */ = [];
		foreach ($inputs as $position => $input) {
			$operand = new cpp_runtime_operand($input, $expressions[$position]);
			$parts[] = self::address($operand, $context);
		}
		return 'scpp::internal::' . $entry->name . '(' . self::join($parts) . ')';
	}

	/** Record every helper use with its fragment, including a reused engine entry. */
	public static function reference(cpp_runtime_operation $request, cpp_generation_context $context): cpp_specialization_entry
	{
		$engine /** CPP_Specializations */ = $context->specialization_engine;
		$entry = $engine->entry($engine->request_operation($request));
		$context->runtime_operations[$entry->name] = $request;
		$context->headers[$entry->header] = true;
		return $entry;
	}

	public static function address(cpp_runtime_operand $operand, cpp_generation_context $context): string
	{
		$mapping = CPP_Types::representation($operand->type, $context);
		CPP_Types::record_dependencies($mapping, $context);
		return $mapping->opaque ? '(' . $operand->expression . ').address()' : '&(' . $operand->expression . ')';
	}

	public static function entry(cpp_runtime_operation $request, CPP_Specializations $engine): cpp_specialization_entry
	{
		$name = self::name($request);
		$existing = $engine->find($name);
		if ($existing !== null) { return $existing; }
		if ($request->kind === cpp_runtime_operation_kind::initialize_buffer) {
			return CPP_Initializer_Buffer::entry($request, $engine);
		}
		$native_context = new cpp_generation_context();
		$signature = '';
		$unpack = '';
		$arguments /** vector<string> */ = [];
		$pointers /** vector<string> */ = [];
		$inputs /** Storage<canonical_type_use> */ = $request->inputs;
		foreach ($inputs as $position => $input) {
			CPP_Opaque_Representation::require_native_type($input);
			$native = CPP_Declarations::type($input, $native_context);
			$mutable_input = self::mutable_input($request, $position);
			$qualifier = $mutable_input ? '' : 'const ';
			if ($position !== 0) { $signature .= ', '; }
			$signature .= $qualifier . 'void* p' . $position;
			$unpack .= 'auto& a' . $position . ' = *std::launder(static_cast<' . $qualifier . $native . '*>(p' . $position . ')); ';
			$arguments[] = 'a' . $position;
			$pointers[] = 'p' . $position;
		}
		$expression = self::native_operation($request, $arguments, $native_context);
		$output = 'void';
		$declaration_headers /** hash<bool> */ = [];
		$body = $unpack . $expression . ';';
		if ($request->result !== null) {
			CPP_Opaque_Representation::require_native_type($request->result);
			$native_result = CPP_Declarations::type($request->result, $native_context);
			$mapping = CPP_Types::representation($request->result, new cpp_generation_context($engine));
			$output = $mapping->spelling;
			$declaration_headers = $mapping->headers;
			$body = $unpack . 'return ' . $expression . ';';
			if ($mapping->opaque) {
				// The callback initializes the final result storage; neither side materializes a second value.
				$reload = '';
				foreach ($inputs as $position => $input) {
					$reload .= self::mutable_input($request, $position)
						? 'void* p' . $position . ' = const_cast<void*>(addresses[' . $position . ']); '
						: 'const void* p' . $position . ' = addresses[' . $position . ']; ';
				}
				$body = 'const void* addresses[] = {' . (q_count($pointers) === 0 ? 'nullptr' : self::join($pointers)) . '}; '
					. 'return ' . $output . '(' . $output . '::operation_result{}, +[](void* destination, const void* input) { '
					. 'auto addresses = static_cast<const void* const*>(input); ' . $reload . $unpack
					. '::new(destination) ' . $native_result . '(' . $expression . '); }, addresses);';
			}
		}
		$prototype = $output . ' ' . $name . '(' . $signature . ')';
		$header = 'specializations/' . $name . '.hpp';
		$declaration = "#pragma once\n" . CPP_Artifacts::includes($declaration_headers, '')
			. 'namespace scpp::internal { ' . $prototype . "; }\n";
		$implementation = CPP_Artifacts::includes($native_context->headers, '') . "#include <memory>\n#include <new>\n"
			. 'namespace scpp::internal { ' . $prototype . ' { ' . $body . " } }\n";
		return new cpp_specialization_entry($name, $header, $declaration, $implementation);
	}

	private static function mutable_input(cpp_runtime_operation $request, int $position): bool
	{
		if ($request->call !== null) { return CPP_Source_Calls::mutable_input($request->call, $position, $request->construction === null); }
		return ($position === 0) && ($request->kind === cpp_runtime_operation_kind::index_write);
	}

	private static function native_operation(cpp_runtime_operation $request, array $arguments /** vector<string> */,
		cpp_generation_context $context): string
	{
		if ($request->kind === cpp_runtime_operation_kind::construct_call) { return CPP_Source_Calls::native_construction($request, $arguments, $context); }
		if ($request->kind === cpp_runtime_operation_kind::receiver_check) { return '(void)' . $arguments[0] . '.operator->()'; }
		if ($request->kind === cpp_runtime_operation_kind::method_call) { return CPP_Source_Calls::native_call($request, $arguments, $context); }
		if ($request->kind === cpp_runtime_operation_kind::construct_default) {
			$facts /** prepared_object_construction */ = $request->construction;
			return CPP_Record_Construction::object($facts, new Storage /** Storage<expression_node> */(), $context);
		}
		if ($request->kind === cpp_runtime_operation_kind::initialize_empty) {
			$binding /** initialization_binding */ = $request->initialization;
			$factory = CPP_Data_Construction::reference($binding, $context);
			$parameters /** Storage<callable_parameter> */ = $binding->factory()->signature()->parameters;
			return $factory . '(' . CPP_Declarations::type($parameters[0]->type, $context) . '{})';
		}
		if ($request->kind === cpp_runtime_operation_kind::observation) {
			$binding /** cpp_function_binding */ = $request->binding;
			$context->headers[$binding->header()] = true;
			return $binding->symbol() . '(' . self::join($arguments) . ')';
		}
		if ($request->kind === cpp_runtime_operation_kind::binary) {
			$decision /** operator_decision */ = $request->decision;
			CPP_Generator::record_operator_headers($decision, $context);
			return CPP_Generator::render_eager_binary($decision, $request->spelling, $arguments[0], $arguments[1], $context);
		}
		if ($request->kind === cpp_runtime_operation_kind::unary) {
			$decision /** operator_decision */ = $request->decision;
			CPP_Generator::record_operator_headers($decision, $context);
			$context->headers['scpp/generated/operators.hpp'] = true;
			return '(' . $request->spelling . $arguments[0] . ')';
		}
		if ($request->kind === cpp_runtime_operation_kind::conversion) {
			$conversion /** conversion_decision */ = $request->conversion;
			return CPP_Declarations::conversion($arguments[0], $conversion, $context);
		}
		if ($request->kind === cpp_runtime_operation_kind::index_read) {
			$decision /** operator_decision */ = $request->decision;
			$context->headers['scpp/index_read.hpp'] = true;
			$helper = $decision->operation === operator_operation::index_checked_read ? 'checked_index_read' : 'null_index_read';
			return 'scpp::' . $helper . '(' . self::join($arguments) . ')';
		}
		if ($request->kind === cpp_runtime_operation_kind::write_key) {
			$context->headers['scpp/index_write.hpp'] = true;
			return 'scpp::require_write_key(' . $arguments[0] . ')';
		}
		if ($request->kind === cpp_runtime_operation_kind::index_write) {
			return self::native_write($request, $arguments, $context);
		}
		throw new \LogicException('Unimplemented runtime ABI operation');
	}

	/** Probe before mutable descent; no source code executes while interior references are held. */
	private static function native_write(cpp_runtime_operation $request, array $arguments /** vector<string> */,
		cpp_generation_context $context): string
	{
		$context->headers['scpp/index_write_path.hpp'] = true;
		$keys /** vector<string> */ = [];
		foreach ($request->path as $position => $element) { $keys[] = $arguments[$position + 1]; }
		$leaf_position = q_count($keys) + 1;
		$key = $request->append ? '' : $arguments[$leaf_position];
		$value = $arguments[$leaf_position + ($request->append ? 0 : 1)];
		$resolved = CPP_Index_Write::resolve_path($request->path, $keys, $arguments[0], $key, $value, $request->append, $context);
		$leaf = $request->append ? $value : $key . ', ' . $value;
		return $resolved->statements . 'scpp::index_' . ($request->append ? 'append' : 'store')
			. '(' . $resolved->value . ', ' . $leaf . ')';
	}

	/** Reuse settled storage/allocation facts; no guessed constructor or ownership family. */
	public static function construct_default(prepared_object_construction $facts, cpp_generation_context $context): string
	{
		if ($facts->constructor_call !== null) {
			throw new \LogicException('Selected constructor requires source-call transport');
		}
		$request = new cpp_runtime_operation(cpp_runtime_operation_kind::construct_default);
		$request->construction = $facts;
		$request->result = $facts->type;
		return self::invoke($request, [], $context);
	}

	public static function initialize(data_construction_node $node, initialization_binding $binding,
		cpp_generation_context $context): string
	{
		if (!$node->entries->is_empty()) {
			return CPP_Data_Construction::sequence($node, $binding, $context);
		}
		$request = new cpp_runtime_operation(cpp_runtime_operation_kind::initialize_empty);
		$request->initialization = $binding;
		$request->result = $binding->target();
		return self::invoke($request, [], $context);
	}

	public static function read(operator_decision $decision, canonical_type_use $receiver_type,
		string $receiver, string $key, cpp_generation_context $context): string
	{
		$request = new cpp_runtime_operation(cpp_runtime_operation_kind::index_read);
		$request->decision = $decision;
		$request->result = $decision->result_type;
		$inputs /** Storage<canonical_type_use> */ = $request->inputs;
		$inputs->append($receiver_type);
		$inputs->append($decision->operands[1]->target_type);
		return self::invoke($request, [$receiver, $key], $context);
	}

	public static function write_key(canonical_type_use $type, string $key, cpp_generation_context $context): string
	{
		$request = new cpp_runtime_operation(cpp_runtime_operation_kind::write_key);
		$inputs /** Storage<canonical_type_use> */ = $request->inputs;
		$inputs->append($type);
		return self::invoke($request, [$key], $context);
	}

	public static function write(prepared_index_assignment $facts, string $receiver, array $keys /** vector<string> */,
		string $value, cpp_generation_context $context): string
	{
		$request = new cpp_runtime_operation(cpp_runtime_operation_kind::index_write);
		$request->append = $facts->target->decision->operation === operator_operation::index_append;
		$inputs /** Storage<canonical_type_use> */ = $request->inputs;
		$path /** Storage<canonical_type_use> */ = $request->path;
		$inputs->append($facts->target->root->require_preparation()->type);
		$expressions /** vector<string> */ = [$receiver];
		foreach ($facts->target->path as $position => $step) {
			$inputs->append($step->key_conversion->target_type);
			$path->append($step->element_type);
			$expressions[] = $keys[$position];
		}
		if (!$request->append) {
			$inputs->append($facts->target->decision->operands[1]->target_type);
			$expressions[] = $keys[q_count($keys) - 1];
		}
		$inputs->append($facts->destination_type());
		$expressions[] = $value;
		return self::invoke($request, $expressions, $context);
	}

	public static function binary(operator_decision $decision, string $spelling, string $left, string $right,
		cpp_generation_context $context): string
	{
		$request = new cpp_runtime_operation(cpp_runtime_operation_kind::binary);
		$request->result = $decision->result_type;
		$request->decision = $decision;
		$request->spelling = $spelling;
		$inputs /** Storage<canonical_type_use> */ = $request->inputs;
		foreach ($decision->operands as $conversion) { $inputs->append($conversion->target_type); }
		return self::invoke($request, [$left, $right], $context);
	}

	public static function unary(operator_decision $decision, string $spelling, string $operand,
		cpp_generation_context $context): string
	{
		$request = new cpp_runtime_operation(cpp_runtime_operation_kind::unary);
		$request->result = $decision->result_type;
		$request->decision = $decision;
		$request->spelling = $spelling;
		$inputs /** Storage<canonical_type_use> */ = $request->inputs;
		$inputs->append($decision->operands[0]->target_type);
		return self::single_value($request, $operand, $context);
	}

	public static function convert(string $expression, conversion_decision $decision, cpp_generation_context $context): string
	{
		$request = new cpp_runtime_operation(cpp_runtime_operation_kind::conversion);
		$request->result = $decision->target_type;
		$request->conversion = $decision;
		$inputs /** Storage<canonical_type_use> */ = $request->inputs;
		$inputs->append($decision->source_type);
		return self::single_value($request, $expression, $context);
	}

	private static function single_value(cpp_runtime_operation $request, string $expression, cpp_generation_context $context): string
	{
		$name = 'runtime_input_' . $context->next_temporary++;
		$result /** canonical_type_use */ = $request->result;
		return '([&]() -> ' . CPP_Declarations::type($result, $context) . ' { const auto& ' . $name . ' = '
			. $expression . '; return ' . self::invoke($request, [$name], $context) . '; }())';
	}

	/** The first call transport admits readonly observations and zero-input provider calls. */
	public static function call(prepared_call $facts, Storage $arguments /** Storage<expression_node> */,
		cpp_generation_context $context): string
	{
		if (($facts->signature->reference_return !== null) || ($facts->variadic_parameter !== null)) {
			throw new \RuntimeException('runtime template ABI: reference-result/variadic call transport is not yet implemented');
		}
		$request = new cpp_runtime_operation(cpp_runtime_operation_kind::observation);
		$target = object_cast(weakref_get($facts->target), runtime_function_definition::class);
		$request->binding = Model::$cpp_callable_bindings->function_binding($target);
		if (Type_Queries::canonical($facts->type)->family() !== type_family::no_value) { $request->result = $facts->type; }
		$parameters /** Storage<callable_parameter> */ = $facts->signature->parameters;
		$prepared_arguments /** Storage<prepared_call_argument> */ = $facts->arguments;
		if (q_count($prepared_arguments) > 1) { throw new \RuntimeException('runtime template ABI: multiple observation inputs need a sequencing contract'); }
		if (q_count($parameters) !== q_count($prepared_arguments)) { throw new \RuntimeException('runtime template ABI: default call inputs are not yet implemented'); }
		$prefix = '';
		$names /** vector<string> */ = [];
		$inputs /** Storage<canonical_type_use> */ = $request->inputs;
		$emitter = new CPP_Syntax($context);
		foreach ($prepared_arguments as $position => $argument) {
			if (($argument->boundary !== value_boundary_operation::observe_value) || ($argument->conversion !== null)) { throw new \RuntimeException('runtime template ABI: call requires prepared observation inputs'); }
			$name = 'runtime_input_' . $context->next_temporary++;
			$prefix .= 'const auto& ' . $name . ' = ' . $emitter->expression($arguments[$argument->source_index]) . '; ';
			$inputs->append($parameters[$position]->type);
			$names[] = $name;
		}
		$result_type = CPP_Declarations::type($facts->type, $context);
		return '([&]() -> ' . $result_type . ' { ' . $prefix . 'return ' . self::invoke($request, $names, $context) . '; }())';
	}
}

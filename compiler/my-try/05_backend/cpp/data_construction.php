<?php

/* Backend registration and ordered construction lowering; no semantic discovery. */
namespace scpp\compiler;

final class cpp_initialization_adapter
{
	public function __construct(private type_definition_i $semantic_definition,
		private string $template_name, private string $required_header)
	{
	}

	public function definition(): type_definition_i { return $this->semantic_definition; }
	public function name(): string { return $this->template_name; }
	public function header(): string { return $this->required_header; }
}

final class CPP_Initialization_Registration
{
	/** Backend metadata is installed alongside semantic roots, never by preparation. */
	public static function install(): void
	{
		$contracts = Model::$initialization_contracts;
		Model::$cpp_type_bindings->bind_definition($contracts->sequence(), 'scpp::initializer_list', 'scpp/data_initializer_input.hpp');
		Model::$cpp_type_bindings->bind_definition($contracts->entry(), 'scpp::initializer_entry', 'scpp/data_initializer_input.hpp');
		foreach (['mixed', 'dynamic'] as $name) {
			Model::$cpp_callable_bindings->bind_initialization_definition(Model::$type_catalog->definition($name),
				'scpp::data_initializer', 'scpp/data_initializer_' . $name . '.hpp');
		}
		foreach (Model::$runtime_metadata->modules as $module) {
			$bindings /** Keyed_Storage<runtime_cpp_spec> */ = $module->cpp_initialization;
			foreach ($module->types as $spec) {
				$initializations /** Storage<runtime_initialization_spec> */ = $spec->initialization;
				if ($initializations->is_empty()) { continue; }
				$binding = $bindings[$spec->identity];
				Model::$cpp_callable_bindings->bind_initialization_definition(Model::$type_catalog->definition($spec->name),
					$binding->symbol, $binding->header);
			}
		}
	}
}

/** Allocation is shared by positional new and named data inputs, independent of class/struct spelling. */
final class CPP_Record_Construction
{
	/** The public allocation entry requires a key obtainable only in source-authorized contexts. */
	public static function keyed(collected_method $method): bool
	{
		return ($method->name === '__construct')
			&& ($method->callable_syntax()->require_preparation()->visibility !== member_visibility::public_access);
	}

	public static function key(collected_method $method): string
	{
		if (!self::keyed($method)) { return ''; }
		return CPP_Generator::source_name('record', $method->declaring_type()->qualified_name()) . '::scpp_constructor_key()';
	}

	public static function key_declaration(collected_method $method): string
	{
		if (!self::keyed($method)) { return ''; }
		$name = CPP_Generator::source_name('record', $method->declaring_type()->qualified_name());
		return "public:\n\tclass scpp_construction_key { friend class " . $name
			. "; scpp_construction_key() {} };\n"
			. CPP_Declarations::visibility($method->callable_syntax()->require_preparation()->visibility)
			. "\tstatic scpp_construction_key scpp_constructor_key() { return scpp_construction_key(); }\n";
	}

	/** Storage facts select allocation; source spelling and constructor selection are already settled. */
	public static function allocation(canonical_type_use $type, canonical_type_use $payload_type,
		record_storage_kind $storage, cpp_generation_context $context): string
	{
		if ($storage === record_storage_kind::inline_value) { return CPP_Declarations::type($type, $context); }
		$payload = CPP_Types::payload($payload_type, $context);
		CPP_Types::record_dependencies($payload, $context);
		if ($storage === record_storage_kind::shared) {
			$source_record = Type_Queries::source_record($payload_type);
			if ($source_record !== null) {
				$record /** collected_record */ = $source_record;
				$allocation_name = $record->qualified_name();
				if (isset($context->outlined_shared_allocations[$allocation_name])) {
					return CPP_Shared_Allocations::name($record);
				}
			}
		}
		$context->headers['scpp/memory.hpp'] = true;
		$helper = $storage === record_storage_kind::unique ? 'unique' : 'shared';
		return '::scpp::' . $helper . '<' . $payload->spelling . '>';
	}

	/** Positional calls reuse ordinary ordered argument and default materialization. */
	public static function object(prepared_object_construction $facts, Storage $arguments /** Storage<expression_node> */,
		cpp_generation_context $context): string
	{
		if ($context->concrete_types() && CPP_Types::needs_concrete_storage($facts->type)) {
			if ($facts->constructor_call !== null) { return CPP_Source_Calls::construct($facts, $arguments, $context); }
			return CPP_Runtime_Operations::construct_default($facts, $context);
		}
		$callee = self::allocation($facts->type, $facts->constructed_type, $facts->storage, $context);
		if ($facts->constructor_call !== null) {
			return CPP_Declarations::selected_call($facts->constructor_call, $arguments, $context, $callee, $facts->type, '');
		}
		return $callee . ($facts->storage === record_storage_kind::inline_value ? '{}' : '()');
	}
}

final class CPP_Data_Construction
{
	/** Factory registration keys contain transient type IDs; output names use resolved native bindings. */
	public static function binding_identity(initialization_binding $binding): string
	{
		$context = new cpp_generation_context();
		$symbol = self::reference($binding, $context);
		return $symbol . ':' . CPP_Artifacts::includes($context->headers, '');
	}

	/** Publish one backend binding for the already selected concrete semantic factory. */
	public static function reference(initialization_binding $binding, cpp_generation_context $context,
		?CPP_Specializations $implementation_engine = null): string
	{
		$factory = object_cast($binding->factory(), registered_static_factory::class);
		$catalog = Model::$cpp_callable_bindings;
		if (!$catalog->contains($factory)) {
			$definition = Type_Queries::canonical($binding->target())->definition();
			$adapter = $catalog->initialization_adapter($definition);
			$member = $binding->is_keyed() ? 'from_key_value' : 'from_list';
			$catalog->bind_adapter($factory, $adapter->name(), $member, $adapter->header());
		}
		return CPP_Callables::runtime_reference($factory, $context, $implementation_engine);
	}

	/** Input backing and each entry's snapshots stay inside this synchronous construction call. */
	public static function generate(data_construction_node $node, cpp_generation_context $context): string
	{
		$prepared = $node->require_data_preparation();
		if ($prepared instanceof prepared_record_construction) {
			return self::record($node, object_cast($prepared, prepared_record_construction::class), $context);
		}
		$binding = object_cast($prepared, prepared_sequence_construction::class)->binding;
		if ($context->specialization_engine !== null) {
			return CPP_Runtime_Operations::initialize($node, $binding, $context);
		}
		return self::sequence($node, $binding, $context);
	}

	/** Both representations use the same source sequencing and entry lifetimes. */
	public static function sequence(data_construction_node $node, initialization_binding $binding,
		cpp_generation_context $context): string
	{
		if (CPP_Initializer_Buffer::active($context)) {
			return self::emit_sequence($node, $binding, $context, CPP_Initializer_Buffer::reference($node, $binding, $context), '', '');
		}
		$factory = self::reference($binding, $context);
		$parameters /** Storage<callable_parameter> */ = $binding->factory()->signature()->parameters;
		$input_type = CPP_Declarations::type($parameters[0]->type, $context);
		$entries /** Storage<data_entry_node> */ = $node->entries;
		if ($entries->is_empty()) { return $factory . '(' . $input_type . '{})'; }
		$input = object_cast(Type_Queries::canonical($parameters[0]->type), applied_template_type::class);
		$element = CPP_Declarations::type($input->arguments()[0], $context);
		return self::emit_sequence($node, $binding, $context, 'scpp::initializer_buffer<' . $element . '>', $element, $factory);
	}

	/** Only physical buffer operations vary; source evaluation and lifetime scopes are shared. */
	private static function emit_sequence(data_construction_node $node, initialization_binding $binding,
		cpp_generation_context $context, string $buffer_type, string $element, string $factory): string
	{
		$entries /** Storage<data_entry_node> */ = $node->entries;

		$buffer = 'initializer_' . $context->next_temporary;
		$context->next_temporary++;
		$text = CPP_Sequence::open(CPP_Declarations::type($binding->target(), $context), $context);
		$text .= $buffer_type . ' ' . $buffer . '(' . q_count($entries) . ");\n";
		$emitter = new CPP_Syntax($context);
		foreach ($entries as $entry) {
			$facts = $entry->require_preparation();
			$text .= "{\n";
			$key_name = '';
			if ($entry->key !== null) {
				$key_decision /** conversion_decision */ = $facts->key_conversion;
				$key_name = 'initializer_key_' . $context->next_temporary;
				$context->next_temporary++;
				$key = CPP_Declarations::conversion($emitter->expression($entry->key), $key_decision, $context);
				$text .= CPP_Declarations::type($key_decision->target_type, $context) . ' ' . $key_name . ' = ' . $key . ";\n";
			}
			$value_name = 'initializer_value_' . $context->next_temporary;
			$context->next_temporary++;
			$value = CPP_Declarations::conversion($emitter->expression($entry->value), $facts->value_conversion, $context);
			$text .= CPP_Declarations::type($facts->value_conversion->target_type, $context) . ' ' . $value_name . ' = ' . $value . ";\n";
			if (CPP_Initializer_Buffer::active($context)) {
				$text .= CPP_Initializer_Buffer::append($facts, $buffer, $key_name, $value_name, $context) . ";\n}\n";
				continue;
			}
			$argument = $value_name;
			if ($entry->key !== null) { $argument = $element . '{' . $key_name . ', ' . $value_name . '}'; }
			$text .= $buffer . '.append(' . $argument . ");\n}\n";
		}
		$finish = CPP_Initializer_Buffer::active($context) ? $buffer . '.finish()' : $factory . '(' . $buffer . '.view())';
		return $text . CPP_Sequence::result($finish, $context) . CPP_Sequence::close($context);
	}

	/** Snapshot supplied inputs first, then omitted defaults; invoke the settled typed constructor. */
	private static function record(data_construction_node $node, prepared_record_construction $facts,
		cpp_generation_context $context): string
	{
		$type = CPP_Declarations::type($facts->type, $context);
		$payload = CPP_Types::payload($facts->payload_type, $context);
		CPP_Types::record_dependencies($payload, $context);
		$text = CPP_Sequence::open($type, $context);
		$arguments /** hash<string, int> */ = [];
		$emitter = new CPP_Syntax($context);
		$entries /** Storage<data_entry_node> */ = $node->entries;
		foreach ($entries as $entry) {
			$input = $entry->require_preparation();
			$name = 'record_input_' . $context->next_temporary;
			$context->next_temporary++;
			$value = CPP_Declarations::conversion($emitter->expression($entry->value), $input->value_conversion, $context);
			$text .= CPP_Declarations::type($input->value_conversion->target_type, $context) . ' ' . $name . ' = ' . $value . ";\n";
			$arguments[$input->input_position] = $name;
		}
		$inputs /** vector<record_initialization_input> */ = $facts->contract->inputs_in_order();
		$ordered = '';
		if (weakref_get($facts->contract->constructor) === null) { $ordered = $payload->spelling . '::scpp_data_tag{}'; }
		else { $ordered = CPP_Record_Construction::key(weakref_get($facts->contract->constructor)); }
		foreach ($inputs as $position => $input) {
			$parameter = $input->parameter;
			if (!isset($arguments[$position])) {
				$name = 'record_default_' . $context->next_temporary;
				$context->next_temporary++;
				$field_type = CPP_Declarations::type($parameter->type, $context);
				$default_facts /** prepared_parameter_default */ = $parameter->default_value;
				$value = CPP_Declarations::parameter_default($default_facts, $context);
				$text .= $field_type . ' ' . $name . ' = ' . $value . ";\n";
				$arguments[$position] = $name;
			}
			$ordered .= ($ordered === '' ? '' : ', ') . CPP_Declarations::move($arguments[$position], $parameter->type, $context);
		}
		$callee = CPP_Record_Construction::allocation($facts->type, $facts->payload_type, $facts->storage, $context);
		return $text . CPP_Sequence::result($callee . '(' . $ordered . ')', $context) . CPP_Sequence::close($context);
	}

	/** This overload initializes members directly; supplied inputs never execute member defaults. */
	public static function constructor(string $name, record_initialization_contract $contract,
		cpp_generation_context $context): string
	{
		$parameters = 'scpp_data_tag';
		$initializers = '';
		$base_count = 0;
		$inputs /** vector<record_initialization_input> */ = $contract->inputs_in_order();
		if ($contract->base_contract !== null) {
			$base_count = q_count($contract->base_contract->inputs_in_order());
			$base = CPP_Types::payload($contract->base_type, $context);
			CPP_Types::record_dependencies($base, $context);
			$initializers = ' : ' . $base->spelling . '{' . $base->spelling . '::scpp_data_tag{}';
			for ($index = 0; $index < $base_count; $index++) {
				$initializers .= ', ' . CPP_Declarations::move('input_' . $index, $inputs[$index]->field->type, $context);
			}
			$initializers .= '}';
		}
		foreach ($inputs as $position => $input) {
			$field /** prepared_field */ = $input->field;
			$requirement = Type_Queries::is_nominal_handle($field->type)
				? cpp_type_requirement::declaration : cpp_type_requirement::complete;
			$parameters .= ', ' . CPP_Declarations::type_for($field->type, $context, $requirement) . ' input_' . $position;
			if ($position < $base_count) { continue; }
			$entry = object_cast(weakref_get($field->declaration), collected_name::class);
			$initializers .= ($initializers === '' ? ' : ' : ', ')
				. CPP_Generator::source_name('field', $entry->name) . '(' . CPP_Declarations::move('input_' . $position, $field->type, $context) . ')';
		}
		$text = "\tstruct scpp_data_tag {};\n\t" . $name . "() = default;\n\t" . $name . '(' . $parameters . ')' . $initializers;
		return $text . " {}\n";
	}
}

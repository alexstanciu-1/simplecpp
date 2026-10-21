<?php

/* Role: map canonical type identities to independent C++ representation bindings. */
namespace scpp\compiler;

final class CPP_Types
{
	/** Allocation and ownership wrappers name the payload, not its default stored representation. */
	public static function payload(canonical_type_use $type_use, ?cpp_generation_context $context = null): cpp_type
	{
		return self::payload_for($type_use, cpp_type_requirement::complete, $context);
	}

	/** Explicit completeness follows the same contract as representation_for. */
	public static function payload_for(canonical_type_use $type_use, cpp_type_requirement $requirement,
		?cpp_generation_context $context = null): cpp_type
	{
		return self::unmodified_representation($type_use, $requirement, $context);
	}

	/** Resolve the semantic identity first; backend metadata never establishes type equality. */
	public static function representation(canonical_type_use $type_use, ?cpp_generation_context $context = null): cpp_type
	{
		return self::representation_for($type_use, cpp_type_requirement::complete, $context);
	}

	/** Classify source visibility without assuming incomplete-type support in runtime templates. */
	public static function representation_for(canonical_type_use $type_use,
		cpp_type_requirement $requirement, ?cpp_generation_context $context = null): cpp_type
	{
		if ($context !== null) {
			if ($context->concrete_types() && self::needs_concrete_storage($type_use)) {
				$engine /** CPP_Specializations */ = $context->specialization_engine;
				$result = $engine->representation($type_use, $context);
				$pointee = self::application_pointee($type_use);
				if (($pointee !== null) && ($requirement === cpp_type_requirement::complete)) {
					self::merge_headers($result, self::payload($pointee, $context));
				}
				return $result;
			}

		}
		return self::native_representation_for($type_use, $requirement, $context,
			$context !== null ? $context->implementation_engine : null);
	}

	/** Native outer implementation with concrete physical arguments, using the same registry bindings. */
	public static function implementation_representation(canonical_type_use $type_use, CPP_Specializations $engine): cpp_type
	{
		$requirement = self::pointee($type_use) !== null ? cpp_type_requirement::declaration : cpp_type_requirement::complete;
		return self::native_representation_for($type_use, $requirement, null, $engine);
	}

	private static function native_representation_for(canonical_type_use $type_use, cpp_type_requirement $requirement,
		?cpp_generation_context $context, ?CPP_Specializations $argument_engine): cpp_type
	{
		// Passing/copying the stored handle does not inspect its payload. Other
		// representations retain their complete-type requirements.
		if ($requirement === cpp_type_requirement::stored_value) {
			$requirement = Type_Queries::is_nominal_handle($type_use)
				? cpp_type_requirement::declaration : cpp_type_requirement::complete;
		}
		// Runtime value wrappers may inspect the payload's layout/traits at instantiation.
		$payload_requirement = $type_use->by_value() ? cpp_type_requirement::complete : $requirement;
		$result = self::unmodified_representation($type_use, $payload_requirement, $context, $argument_engine);
		// A selected concrete representation already owns the complete stored type.
		if ($result->opaque) { return $result; }
		if (Type_Queries::is_nominal_handle($type_use)) {
			$result = self::shared_storage($type_use, $result, $context, $argument_engine);
		}
		if ($type_use->by_value()) {
			$modifier = Model::$cpp_type_bindings->modifier(type_use_modifier_kind::by_value);
			$no_arguments /** vector<canonical_type_use> */ = [];
			$no_payloads /** hash<cpp_type,int> */ = [];
			$result = self::native_recipe($modifier->recipe(), $no_arguments, $result, $context, $argument_engine, $no_payloads);
			$result->literal = cpp_literal_kind::none;
		}
		return $result;
	}

	/** The storage policy selects a semantic owner; its runtime binding supplies the wrapper. */
	private static function shared_storage(canonical_type_use $type_use, cpp_type $payload,
		?cpp_generation_context $context, ?CPP_Specializations $engine): cpp_type
	{
		$owner = Model::$type_catalog->definition('shared');
		$recipe = Model::$cpp_type_bindings->definition($owner->definition_id())->recipe();
		$arguments /** vector<canonical_type_use> */ = [$type_use];
		// Keep the payload's existing complete/forward requirement; do not resolve it again.
		$payloads /** hash<cpp_type,int> */ = [0 => $payload];
		$result = self::native_recipe($recipe, $arguments, null, $context, $engine, $payloads);
		$result->literal = cpp_literal_kind::none;
		if (q_strpos($result->spelling, '::') !== 0) { $result->spelling = '::' . $result->spelling; }
		return $result;
	}

	/** One parameter spelling for declarations and erased callable representations. */
	public static function parameter_representation(callable_parameter $parameter, cpp_type_requirement $requirement, ?cpp_generation_context $context = null): cpp_type
	{
		$result = self::signature_representation($parameter->type, $requirement, $context);
		$result->spelling = ($parameter->access === storage_access::read_only ? 'const ' : '')
			. $result->spelling . ($parameter->mode === passing_mode::reference ? '&' : '');
		return $result;
	}

	/** Reference results have identical spelling for source and erased callables. */
	public static function return_representation(callable_signature $signature, cpp_type_requirement $requirement, ?cpp_generation_context $context = null): cpp_type
	{
		$result = self::signature_representation($signature->return_type, $requirement, $context);
		if ($signature->reference_return !== null) { $result->spelling .= '&'; }
		return $result;
	}

	/** Prototypes may name incomplete stored types; fields still need complete handle storage. */
	private static function signature_representation(canonical_type_use $type, cpp_type_requirement $requirement,
		?cpp_generation_context $context): cpp_type
	{
		$result = self::representation_for($type, $requirement, $context);
		if (($result->opaque) && ($requirement === cpp_type_requirement::declaration)) {
			$headers /** hash<bool> */ = [CPP_Opaque_Representation::declaration_header($result->spelling) => true];
			$result->headers = $headers;
		}
		return $result;
	}

	/** Add every representation dependency to one generation context exactly once. */
	public static function record_dependencies(cpp_type $type, cpp_generation_context $context): void
	{
		if ($type->specialization !== null) {
			$context->specializations[$type->specialization->type_id()] = $type->specialization;
		}
		foreach ($type->headers as $header => $used) {
			$context->headers[$header] = true;
		}
		foreach ($type->forward_declarations as $name => $used) {
			$context->forward_declarations[$name] = true;
		}
	}

	/** Resolve the canonical core; recursive arguments contribute their own direct headers. */
	private static function unmodified_representation(canonical_type_use $type_use,
		cpp_type_requirement $requirement, ?cpp_generation_context $context = null,
		?CPP_Specializations $argument_engine = null): cpp_type
	{
		$type = Type_Queries::canonical($type_use);
		$definition = $type->definition();
		if ($context !== null) {
			$engine = $context->specialization_engine;
			if ($engine !== null) {
				$selected = self::has_template_recipe($type_use);
				if (($definition->origin() !== type_definition_origin::source)
					&& !($definition instanceof callable_type_definition) && !($definition instanceof async_task_type_definition)) {
					$recipe = Model::$cpp_type_bindings->definition($definition->definition_id())->recipe();
					$selected = $selected || $recipe->concrete_boundary;
				}
				// Payload queries must not select the complete stored owner again.
				if ($selected && !Type_Queries::is_nominal_handle($type_use)) { return $engine->representation($type_use, $context); }
			}
		}
		$result = new cpp_type();
		if ($definition instanceof async_task_type_definition) {
			$task = object_cast($definition, async_task_type_definition::class);
			$payload_context = $argument_engine !== null ? new cpp_generation_context($argument_engine) : $context;
			$payload = self::representation($task->value_type, $payload_context);
			self::merge_headers($result, $payload);
			$result->spelling = 'scpp::async_core::task<' . $payload->spelling . '>';
			self::add_header($result, 'scpp/async_core.hpp');
			$result->literal = cpp_literal_kind::none;
			return $result;
		}
		if ($definition instanceof callable_type_definition) {
			// Native callable spelling must not bypass the concrete storage contract.
			if ($context !== null) {
				if ($context->concrete_types()) { CPP_Opaque_Representation::require_native_type($type_use); }
			}
			$signature = object_cast($definition, callable_type_definition::class)->signature();
			$signature_context = $argument_engine !== null ? new cpp_generation_context($argument_engine) : $context;
			$return_mapping = self::return_representation($signature, cpp_type_requirement::complete, $signature_context);
			self::merge_headers($result, $return_mapping);
			$parts = '';
			$separator = '';
			$parameters /** Storage<callable_parameter> */ = $signature->parameters;
			foreach ($parameters as $parameter) {
				$mapping = self::parameter_representation($parameter, cpp_type_requirement::complete, $signature_context);
				self::merge_headers($result, $mapping);
				$parts .= $separator . $mapping->spelling;
				$separator = ', ';
			}
			$result->spelling = 'scpp::callable_t<' . $return_mapping->spelling . '(' . $parts . ')>';
			self::add_header($result, 'scpp/callable_t.hpp');
			$result->literal = cpp_literal_kind::none;
			return $result;
		}
		if ($type instanceof applied_template_type) {
			$application = object_cast($type, applied_template_type::class);
			$binding = Model::$cpp_type_bindings->definition($definition->definition_id());
			$no_payloads /** hash<cpp_type,int> */ = [];
			$result = self::native_recipe($binding->recipe(), $application->arguments(), null, $context, $argument_engine, $no_payloads);
			$result->literal = cpp_literal_kind::none;
			return $result;
		}
		if ($definition->origin() === type_definition_origin::source) {
			$entry = Model::$type_catalog->source_declarations()->declaration($definition->definition_id());
			if ($entry instanceof collected_enum) {
				$work = $entry->preparation_work_owner();
				if ($work === null) {
					throw new \RuntimeException('C++ enum representation requires completed preparation');
				}
				if (!Preparation_Status::complete($work)) {
					throw new \RuntimeException('C++ enum representation requires completed preparation');
				}
				$result->spelling = CPP_Generator::source_name('enum', $entry->qualified_name());
				// Exact backing is a prepared fact: include its small header at every type use for now.
				self::add_header($result, CPP_Artifacts::type_header($entry->qualified_name()));
				$result->literal = cpp_literal_kind::none;
				return $result;
			}
			if (!($entry instanceof collected_member_owner)) {
				throw new \LogicException('C++ representation is not implemented for this source type declaration');
			}
			$member_owner = object_cast($entry, collected_member_owner::class);
			$result->spelling = CPP_Generator::source_name('record', $entry->qualified_name());
			if (($requirement === cpp_type_requirement::complete)
				|| ($member_owner->syntax()->declaration_kind === type_declaration_kind::union_type)) {
				self::add_header($result, CPP_Artifacts::type_header($entry->qualified_name()));
			}
			else {
				$result->forward_declarations[$entry->qualified_name()] = true;
			}
			$result->literal = cpp_literal_kind::none;
			return $result;
		}

		$binding = Model::$cpp_type_bindings->definition($definition->definition_id());
		$no_arguments /** vector<canonical_type_use> */ = [];
		$no_payloads /** hash<cpp_type,int> */ = [];
		$result = self::native_recipe($binding->recipe(), $no_arguments, null, $context, $argument_engine, $no_payloads);
		$result->literal = self::literal_kind($type, $definition);
		return $result;
	}

	/** Render structured runtime binding data; argument interpretation never depends on family names. */
	private static function native_recipe(cpp_type_recipe $recipe, array $arguments /** vector<canonical_type_use> */,
		?cpp_type $modified, ?cpp_generation_context $context, ?CPP_Specializations $engine,
		array $payloads /** hash<cpp_type,int> */): cpp_type
	{
		$result = new cpp_type();
		$result->spelling = $recipe->symbol;
		if ($recipe->template_application) {
			$result->spelling .= '<';
			$separator = '';
			foreach ($recipe->arguments as $argument) {
				$mapping = new cpp_type();
				if ($argument->kind === cpp_type_argument_kind::native_type) {
					$mapping->spelling = $argument->native_name;
					self::add_header($mapping, $argument->header);
				}
				elseif ($argument->kind === cpp_type_argument_kind::modified_type) {
					if ($modified === null) { throw new \LogicException('C++ modifier recipe requires its payload'); }
					$mapping = $modified;
				}
				elseif (isset($payloads[$argument->position])) {
					if (!$argument->payload) { throw new \RuntimeException('Implicit storage requires a payload argument recipe'); }
					$mapping = $payloads[$argument->position];
				}
				else {
					$type_argument = $arguments[$argument->position];
					if (($engine !== null) && ($recipe->pointee_position === $argument->position)) {
						$mapping = self::unmodified_representation($type_argument, cpp_type_requirement::declaration, new cpp_generation_context($engine));
					}
					else {
						$mapping = $engine !== null
							? self::implementation_argument($type_argument, $argument->payload, $engine)
							: ($argument->payload ? self::payload($type_argument, $context) : self::representation($type_argument, $context));
					}
				}
				$result->spelling .= $separator . $mapping->spelling;
				$separator = ', ';
				self::merge_headers($result, $mapping);
			}
			$result->spelling .= '>';
		}
		self::add_header($result, $recipe->header);
		return $result;
	}

	/** Resolve the existing storage owner's recipe without introducing a second type registry. */
	public static function stored_recipe(canonical_type_use $type): ?cpp_type_recipe
	{
		if ($type->by_value()) { return Model::$cpp_type_bindings->modifier(type_use_modifier_kind::by_value)->recipe(); }
		$definition = Type_Queries::canonical($type)->definition();
		if (Type_Queries::is_nominal_handle($type)) { $definition = Model::$type_catalog->definition('shared'); }
		elseif (($definition->origin() === type_definition_origin::source) || ($definition instanceof callable_type_definition) || ($definition instanceof async_task_type_definition)) { return null; }
		return Model::$cpp_type_bindings->definition($definition->definition_id())->recipe();
	}

	public static function needs_concrete_storage(canonical_type_use $type): bool
	{
		if ((Type_Queries::canonical($type)->definition() instanceof callable_type_definition)
			|| (Type_Queries::canonical($type)->definition() instanceof async_task_type_definition)) { return true; }
		$recipe = self::stored_recipe($type);
		if ($recipe === null) { return false; }
		return $recipe->template_application || $recipe->concrete_boundary;
	}

	/** A physical pointer interface identifies its payload through the installed recipe. */
	public static function pointee(canonical_type_use $type): ?canonical_type_use
	{
		$recipe = self::stored_recipe($type);
		if ($recipe === null) { return null; }
		if ($recipe->pointee_modified_type) {
			return Model::$type_catalog->registry()->use($type->type_id());
		}
		if ($recipe->pointee_position < 0) { return null; }
		if (Type_Queries::is_nominal_handle($type)) { return $type; }
		$canonical = Type_Queries::canonical($type);
		if (!($canonical instanceof applied_template_type)) { throw new \LogicException('Pointer interface requires an applied template'); }
		return object_cast($canonical, applied_template_type::class)->arguments()[$recipe->pointee_position];
	}

	/** Provider-owned nominal payloads stay behind compiled calls, not application pointer interfaces. */
	public static function application_pointee(canonical_type_use $type): ?canonical_type_use
	{
		$pointee = self::pointee($type);
		if ($pointee === null) { return null; }
		$definition = Type_Queries::canonical($pointee)->definition();
		if (Type_Queries::is_nominal_handle($pointee)
			&& ($definition->origin() === type_definition_origin::runtime)) { return null; }
		return $pointee;
	}

	/** A native template application is a binding fact, independent of source type category. */
	public static function has_template_recipe(canonical_type_use $type_use): bool
	{
		$definition = Type_Queries::canonical($type_use)->definition();
		// Source records and structural callables have their own representation owners.
		if (($definition->origin() === type_definition_origin::source) || ($definition instanceof callable_type_definition) || ($definition instanceof async_task_type_definition)) {
			return false;
		}
		return Model::$cpp_type_bindings->definition($definition->definition_id())->recipe()->template_application;
	}

	/** Physical substitution does not invent semantic types or grant new capabilities. */
	private static function implementation_argument(canonical_type_use $argument, bool $payload, CPP_Specializations $engine): cpp_type
	{
		$context = new cpp_generation_context($engine);
		return $payload ? self::payload($argument, $context) : self::representation($argument, $context);
	}

	private static function add_header(cpp_type $type, string $header): void
	{
		if ($header !== '') {
			$type->headers[$header] = true;
		}
	}

	private static function merge_headers(cpp_type $destination, cpp_type $source): void
	{
		foreach ($source->headers as $header => $used) {
			$destination->headers[$header] = true;
		}
		foreach ($source->forward_declarations as $name => $used) {
			$destination->forward_declarations[$name] = true;
		}
	}

	/** Literal families are semantic; exact target spelling remains in the binding catalog. */
	private static function literal_kind(canonical_type_i $type, type_definition_i $definition): cpp_literal_kind
	{
		if ($type->family() === type_family::integer) {
			return cpp_literal_kind::signed_integer;
		}
		if ($type->family() === type_family::floating) {
			return cpp_literal_kind::floating;
		}
		if ($type->family() === type_family::boolean) {
			return cpp_literal_kind::boolean;
		}
		if (($type->family() === type_family::nominal) && ($definition->name() === 'string')) {
			return cpp_literal_kind::string_value;
		}
		return cpp_literal_kind::none;
	}
}

<?php

/* Role: map canonical type identities to independent C++ representation bindings. */
namespace scpp\compiler;

final class CPP_Types
{
	/** Allocation and ownership wrappers name the payload, not its default stored representation. */
	public static function payload(canonical_type_use $type_use, ?cpp_generation_context $context = null): cpp_type
	{
		return self::unmodified_representation($type_use, cpp_type_requirement::complete, $context);
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
			if ($context->runtime_abi()) {
				$canonical = Type_Queries::canonical($type_use);
				if (($canonical->family() === type_family::integer) || ($canonical instanceof applied_template_type)
					|| $type_use->by_value() || (Type_Queries::source_declaration($type_use) instanceof collected_member_owner)) {
					$engine /** CPP_Specializations */ = $context->specialization_engine;
					return $engine->representation($type_use, $context);
				}
			}
		}
		return self::native_representation_for($type_use, $requirement, $context, null);
	}

	/** Native outer implementation with concrete physical arguments, using the same registry bindings. */
	public static function implementation_representation(canonical_type_use $type_use, CPP_Specializations $engine): cpp_type
	{
		if (Type_Queries::source_declaration($type_use) instanceof collected_member_owner) {
			throw new \RuntimeException('Concrete substitution: source payload field bindings are not integrated yet');
		}
		return self::native_representation_for($type_use, cpp_type_requirement::complete, null, $engine);
	}

	private static function native_representation_for(canonical_type_use $type_use, cpp_type_requirement $requirement,
		?cpp_generation_context $context, ?CPP_Specializations $argument_engine): cpp_type
	{
		// Runtime value wrappers may inspect the payload's layout/traits at instantiation.
		$payload_requirement = $type_use->by_value() ? cpp_type_requirement::complete : $requirement;
		$result = self::unmodified_representation($type_use, $payload_requirement, $context, $argument_engine);
		if (Type_Queries::is_nominal_handle($type_use)) {
			$result->spelling = '::scpp::shared_p<' . $result->spelling . '>';
			self::add_header($result, 'scpp/shared_p.hpp');
		}
		if ($type_use->by_value()) {
			$modifier = Model::$cpp_type_bindings->modifier(type_use_modifier_kind::by_value);
			$result->spelling = $modifier->name() . '<' . $result->spelling . '>';
			self::add_header($result, $modifier->header());
			$result->literal = cpp_literal_kind::none;
		}
		return $result;
	}

	/** One parameter spelling for declarations and erased callable representations. */
	public static function parameter_representation(callable_parameter $parameter, cpp_type_requirement $requirement, ?cpp_generation_context $context = null): cpp_type
	{
		$result = self::representation_for($parameter->type, $requirement, $context);
		$result->spelling = ($parameter->access === storage_access::read_only ? 'const ' : '')
			. $result->spelling . ($parameter->mode === passing_mode::reference ? '&' : '');
		return $result;
	}

	/** Reference results have identical spelling for source and erased callables. */
	public static function return_representation(callable_signature $signature, cpp_type_requirement $requirement, ?cpp_generation_context $context = null): cpp_type
	{
		$result = self::representation_for($signature->return_type, $requirement, $context);
		if ($signature->reference_return !== null) { $result->spelling .= '&'; }
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
			if (($engine !== null) && (($type->family() === type_family::integer) || ($type instanceof applied_template_type))) {
				return $engine->representation($type_use, $context);
			}
		}
		$result = new cpp_type();
		if ($definition instanceof callable_type_definition) {
			$signature = object_cast($definition, callable_type_definition::class)->signature();
			$return_mapping = self::return_representation($signature, cpp_type_requirement::complete, $context);
			self::merge_headers($result, $return_mapping);
			$parts = '';
			$separator = '';
			$parameters /** Storage<callable_parameter> */ = $signature->parameters;
			foreach ($parameters as $parameter) {
				$mapping = self::parameter_representation($parameter, cpp_type_requirement::complete, $context);
				self::merge_headers($result, $mapping);
				$parts .= $separator . $mapping->spelling;
				$separator = ', ';
			}
			$result->spelling = 'std::function<' . $return_mapping->spelling . '(' . $parts . ')>';
			self::add_header($result, 'functional');
			$result->literal = cpp_literal_kind::none;
			return $result;
		}
		if ($type instanceof applied_template_type) {
			$application = object_cast($type, applied_template_type::class);
			$binding = Model::$cpp_type_bindings->definition($definition->definition_id());
			$arguments = '';
			foreach ($application->arguments() as $position => $argument) {
				if ($position !== 0) {
					$arguments .= ', ';
				}
				// Template bindings do not yet promise incomplete-payload support.
				$argument_type = $argument_engine !== null
					? self::implementation_argument($argument, $definition instanceof ownership_semantics, $argument_engine)
					: ($definition instanceof ownership_semantics
						? self::payload($argument, $context) : self::representation($argument, $context));
				$arguments .= $argument_type->spelling;
				self::merge_headers($result, $argument_type);
			}
			$result->spelling = $binding->name() . '<' . $arguments . '>';
			self::add_header($result, $binding->header());
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
			$result->spelling = CPP_Generator::source_name('record', $entry->qualified_name());
			if ($requirement === cpp_type_requirement::complete) {
				self::add_header($result, CPP_Artifacts::type_header($entry->qualified_name()));
			}
			else {
				$result->forward_declarations[$entry->qualified_name()] = true;
			}
			$result->literal = cpp_literal_kind::none;
			return $result;
		}

		$binding = Model::$cpp_type_bindings->definition($definition->definition_id());
		$result->spelling = $binding->name();
		self::add_header($result, $binding->header());
		$result->literal = self::literal_kind($type, $definition);
		return $result;
	}

	/** Physical substitution does not invent semantic types or grant new capabilities. */
	private static function implementation_argument(canonical_type_use $argument, bool $payload, CPP_Specializations $engine): cpp_type
	{
		if (Type_Queries::source_declaration($argument) instanceof collected_member_owner) {
			throw new \RuntimeException('Concrete substitution: source payload field bindings are not integrated yet');
		}
		$canonical = Type_Queries::canonical($argument);
		if (($canonical->family() === type_family::integer) || ($canonical instanceof applied_template_type) || $argument->by_value()) {
			return CPP_Opaque_Representation::representation($argument, $engine, true);
		}
		return $payload ? self::payload($argument) : self::representation($argument);
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
		foreach ($source->layout_dependencies as $name => $used) { $destination->layout_dependencies[$name] = true; }
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

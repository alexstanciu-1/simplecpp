<?php
namespace scpp\compiler;

/** Resolve runtime-owned representation recipes onto existing semantic identities. */
final class CPP_Runtime_Type_Bindings
{
	public static function install(runtime_catalog_input $input, registered_type_catalog $types,
		CPP_Type_Bindings $bindings): void
	{
		$ordinary /** hash<bool> */ = [];
		foreach ($input->modules as $module) {
			foreach ($module->types as $declaration) { $ordinary[$declaration->name] = true; }
		}
		$identities /** hash<bool> */ = [];
		foreach ($input->modules as $module) {
			$specs /** Keyed_Storage<runtime_cpp_type_spec> */ = $module->cpp_types;
			$declaration_count = 0;
			foreach ($specs as $spec) {
				if (($spec->type_name === '') && ($spec->modifier_name === '')) { $declaration_count++; }
			}
			if ($declaration_count !== q_count($module->types)) {
				throw new \RuntimeException('Runtime type binding count mismatch: ' . $module->file);
			}
			$targets /** hash<type_definition_i> */ = [];
			foreach ($module->types as $declaration) {
				if (!isset($specs[$declaration->identity])) {
					throw new \RuntimeException('Missing runtime type binding: ' . $declaration->identity);
				}
				$targets[$declaration->identity] = $types->definition($declaration->name);
			}
			foreach ($specs as $spec) {
				if (isset($identities[$spec->identity])) { throw new \RuntimeException('Duplicate C++ type binding identity'); }
				$identities[$spec->identity] = true;
				CPP_Runtime_Catalog::spelling($spec->symbol, false);
				CPP_Runtime_Catalog::spelling($spec->header, true);
				if (($spec->modifier_name !== '') || ($spec->type_name !== '')) {
					if (isset($targets[$spec->identity])) { throw new \RuntimeException('Runtime declaration binding cannot redirect its target'); }
				}
				if ($spec->modifier_name !== '') {
					$modifier = $types->source()->modifier($spec->modifier_name);
					$bindings->bind_modifier($modifier->kind(), self::recipe($spec, null, true));
					continue;
				}
				if ($spec->type_name !== '') {
					if (isset($ordinary[$spec->type_name])) { throw new \RuntimeException('Ordinary runtime type binding must use its declaration id'); }
					$targets[$spec->identity] = $types->definition($spec->type_name);
				}
				if (!isset($targets[$spec->identity])) { throw new \RuntimeException('Orphan runtime type binding'); }
				$definition = $targets[$spec->identity];
				$bindings->bind_recipe($definition, self::recipe($spec, $definition, false));
			}
		}
	}

	private static function recipe(runtime_cpp_type_spec $spec, ?type_definition_i $definition,
		bool $modifier): cpp_type_recipe
	{
		$recipe = new cpp_type_recipe($spec->symbol, $spec->header);
		$parameters /** hash<int> */ = [];
		if ($definition instanceof abstract_template_type_definition) {
			$template_definition = object_cast($definition, abstract_template_type_definition::class);
			foreach ($template_definition->parameters() as $position => $parameter) { $parameters[$parameter->name()] = $position; }
		}
		$recipe->concrete_boundary = $spec->concrete_boundary;
		if ($spec->pointee_parameter !== '') {
			if ($modifier || !isset($parameters[$spec->pointee_parameter])) {
				throw new \RuntimeException('C++ pointee must name a declared type parameter');
			}
			$recipe->pointee_position = $parameters[$spec->pointee_parameter];
			if ($spec->arguments === null) { throw new \RuntimeException('C++ pointee requires explicit payload arguments'); }
			$found = false;
			foreach ($spec->arguments as $argument_spec) {
				if ($argument_spec->parameter === $spec->pointee_parameter) {
					if (!$argument_spec->payload) { throw new \RuntimeException('C++ pointee requires payload representation'); }
					$found = true;
				}
			}
			if (!$found) { throw new \RuntimeException('C++ pointee must occur in native arguments'); }
		}
		if ($spec->arguments === null) {
			if ($modifier) { throw new \RuntimeException('C++ modifier binding requires explicit arguments'); }
			$recipe->template_application = $definition instanceof abstract_template_type_definition;
			foreach ($parameters as $name => $position) {
				$argument = new cpp_type_argument_binding();
				$argument->kind = cpp_type_argument_kind::parameter;
				$argument->position = $position;
				$recipe->arguments[] = $argument;
			}
			return $recipe;
		}
		$recipe->template_application = true;
		$arguments /** Storage<runtime_cpp_argument_spec> */ = $spec->arguments;
		foreach ($arguments as $argument_spec) {
			$argument = new cpp_type_argument_binding();
			$argument->payload = $argument_spec->payload;
			if ($argument_spec->native_name !== '') {
				CPP_Runtime_Catalog::spelling($argument_spec->native_name, false);
				if ($argument_spec->header !== '') { CPP_Runtime_Catalog::spelling($argument_spec->header, true); }
				$argument->kind = cpp_type_argument_kind::native_type;
				$argument->native_name = $argument_spec->native_name;
				$argument->header = $argument_spec->header;
			}
			elseif ($argument_spec->modified_type) {
				if (!$modifier) { throw new \RuntimeException('Modified-type argument requires a modifier binding'); }
				if (!$argument_spec->payload) { throw new \RuntimeException('Modified-type argument requires payload representation'); }
				$argument->kind = cpp_type_argument_kind::modified_type;
			}
			else {
				if (!isset($parameters[$argument_spec->parameter])) { throw new \RuntimeException('Unknown C++ type parameter: ' . $argument_spec->parameter); }
				$argument->kind = cpp_type_argument_kind::parameter;
				$argument->position = $parameters[$argument_spec->parameter];
			}
			$recipe->arguments[] = $argument;
		}
		return $recipe;
	}
}

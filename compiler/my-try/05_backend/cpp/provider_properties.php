<?php
namespace scpp\compiler;

/** Compiled provider storage access; shared preparation still owns admission and conversions. */
final class CPP_Provider_Properties
{
	public static function applies(field_access_node $syntax, cpp_generation_context $context): bool
	{
		return $context->concrete_types()
			&& ($syntax->require_field_access_preparation()->field instanceof registered_property);
	}

	private static function request(field_access_node $syntax, bool $write): cpp_runtime_operation
	{
		$facts = $syntax->require_field_access_preparation();
		if ($facts->receiver_storage !== record_storage_kind::shared) {
			throw new \RuntimeException('prelower_templates: inline provider properties require separate storage transport');
		}
		$type = $facts->type;
		$supported = $type->matches(Language_Types::integer(Model::$language_scope))
			|| $type->matches(Language_Types::string_type(Model::$language_scope));
		$canonical = Type_Queries::canonical($type);
		if (($canonical instanceof applied_template_type)
			&& ($canonical->definition() === Model::$type_catalog->definition('vector'))) {
			$application = object_cast($canonical, applied_template_type::class);
			$arguments /** vector<canonical_type_use> */ = $application->arguments();
			$supported = $arguments[0]->matches(Language_Types::string_type(Model::$language_scope));
		}
		if (!$supported) {
			throw new \RuntimeException('prelower_templates: provider property value transport is outside the int/string/vector<string> slice');
		}
		$request = new cpp_runtime_operation($write ? cpp_runtime_operation_kind::property_write : cpp_runtime_operation_kind::property_read);
		$request->property = object_cast($facts->field, registered_property::class);
		$inputs /** Storage<canonical_type_use> */ = $request->inputs;
		$inputs->append($syntax->base->require_preparation()->type);
		if ($write) { $inputs->append($facts->type); }
		else { $request->result = $facts->type; }
		return $request;
	}

	public static function read(field_access_node $syntax, cpp_generation_context $context): string
	{
		$request = self::request($syntax, false);
		$name = 'provider_owner_' . $context->next_temporary++;
		return '([&]() -> ' . CPP_Declarations::type($syntax->require_preparation()->type, $context)
			. ' { auto ' . $name . ' = ' . (new CPP_Syntax($context))->expression($syntax->base)
			. '; return ' . CPP_Runtime_Operations::invoke($request, [$name], $context) . '; }())';
	}

	public static function write(field_access_node $syntax, prepared_property_assignment $facts,
		string $value, cpp_generation_context $context): string
	{
		$request = self::request($syntax, true);
		$target = self::request($syntax, false);
		$target->kind = cpp_runtime_operation_kind::property_target;
		$target->result = null;
		$owner = 'provider_owner_' . $context->next_temporary++;
		$snapshot = 'provider_value_' . $context->next_temporary++;
		$converted = CPP_Declarations::conversion($value, $facts->conversion, $context);
		return '([&]() -> ' . CPP_Declarations::type($facts->destination_type(), $context)
			. ' { auto ' . $owner . ' = ' . (new CPP_Syntax($context))->expression($syntax->base)
			. '; ' . CPP_Runtime_Operations::invoke($target, [$owner], $context)
			. '; auto ' . $snapshot . ' = ' . $converted . '; '
			. CPP_Runtime_Operations::invoke($request, [$owner, $snapshot], $context)
			. '; return ' . $snapshot . '; }())';
	}
}

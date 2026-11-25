<?php
namespace scpp\compiler;

/** Startup scalar snapshots reuse callable defaults; no parsing or preparation worker. */
final class Runtime_Default_Registration
{
	public static function resolve(runtime_default_spec $spec, registered_type_catalog $types): prepared_literal_parameter_default
	{
		$value /** prepared_literal */;
		if ($spec->type_name === 'int') {
			$integer = new prepared_integer_literal();
			$integer->decimal = $spec->text;
			$value = $integer;
		}
		elseif ($spec->type_name === 'float') {
			$floating = new prepared_float_literal();
			$floating->decimal = $spec->text;
			$value = $floating;
		}
		elseif ($spec->type_name === 'bool') {
			$boolean = new prepared_boolean_literal();
			$boolean->value = $spec->text === 'true';
			$value = $boolean;
		}
		elseif ($spec->type_name === 'string') {
			$string_value = new prepared_string_literal();
			$string_value->value = $spec->text;
			$value = $string_value;
		}
		elseif ($spec->type_name === 'null') { $value = new prepared_null_literal(); }
		else { throw new \RuntimeException('Unsupported runtime default scalar tag'); }
		$type = $spec->type_name === 'null' ? $types->null_type()
			: $types->registry()->use($types->canonical($spec->type_name)->type_id());
		$value->type = $type;
		$conversion = new conversion_decision();
		$conversion->source_type = $type;
		$conversion->target_type = $type;
		$conversion->result_type = $type;
		$conversion->context = conversion_context::argument;
		$conversion->operation = conversion_operation::identity;
		$result = new prepared_literal_parameter_default();
		$result->value = $value;
		$result->conversion = $conversion;
		return $result;
	}

	public static function require_type(prepared_parameter_default $default_value, canonical_type_use $type): void
	{
		if (!$default_value->result_type()->matches($type)) {
			throw new \RuntimeException('Runtime default must have the exact parameter type');
		}
	}
}

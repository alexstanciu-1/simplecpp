<?php
namespace scpp\compiler;

/** Observed compile-time queries, independent of runtime provider catalogs. */
final class Layout_Query_Preparation
{
	private static function target(expression_node $syntax, preparation_context $context): canonical_type_use
	{
		if ($syntax instanceof constant_reference_node) {
			$reference = object_cast($syntax, constant_reference_node::class);
			$entry = $reference->occurrence();
			$lexical = object_cast(weakref_get($entry->scope), scope::class);
			return Type_Preparation::resolve_name($lexical, $reference->name, $context);
		}
		if ($syntax instanceof static_member_reference_node) {
			$marker = object_cast($syntax, static_member_reference_node::class);
			if (($marker->qualifier === null) && ($marker->member_name === 'class')) {
				return Type_Preparation::type($marker->require_type_owner(), $context);
			}
		}
		throw new \RuntimeException('Layout query requires a bare type name or Type::class');
	}

	public static function prepare(compiler_function_definition $definition,
		Storage $arguments /** Storage<expression_node> */, preparation_context $context): prepared_call
	{
		$operation = $definition->kind();
		$has_field = ($operation === compiler_function_kind::layout_offsetof)
			|| ($operation === compiler_function_kind::layout_field_sizeof);
		$arity = $has_field ? 2 : 1;
		if ($arguments->count() !== $arity) {
			throw new \RuntimeException('Wrong argument count for ' . $definition->name());
		}
		$type = self::target($arguments[0], $context);
		Type_Preparation::require_value_type($type);
		$facts = new prepared_layout_query($operation);
		$facts->target_type = $type;
		$facts->result_type = $context->integer;
		$record = Type_Queries::source_record($type);
		if ($record !== null) {
			$context->worker->require_declaration($context->owner, $record);
		}
		if ($has_field) {
			if (!($arguments[1] instanceof constant_reference_node) || ($record === null)) {
				throw new \RuntimeException('Layout field query requires a source record and bare field name');
			}
			$name = object_cast($arguments[1], constant_reference_node::class)->name;
			$layout = $record->syntax()->require_preparation();
			$fields /** Key_Storage_List<prepared_field> */ = $layout->fields;
			$matches = $fields->named($name);
			if (q_count($matches) !== 1) {
				throw new \RuntimeException('Layout query requires one direct instance field: ' . $name);
			}
			$field = $matches[0];
			if ($field->visibility !== member_visibility::public_access) {
				throw new \RuntimeException('Layout query requires a public instance field');
			}
			if ($operation === compiler_function_kind::layout_offsetof) {
				$contract = $context->worker->require_type_contract($record, $context);
				if (($layout->base_type !== null) || $contract->polymorphic) {
					throw new \RuntimeException('Layout offset queries for inherited or polymorphic records are deferred');
				}
				foreach ($fields->items() as $member) {
					if ($member->visibility !== member_visibility::public_access) {
						throw new \RuntimeException('Layout offset queries require a public-field layout');
					}
				}
			}
			$facts->field = $field;
		}
		$signature = new callable_signature();
		$signature->return_type = $context->integer;
		$runtime_arguments /** Storage<expression_node> */ = new Storage();
		$call = Call_Preparation::admit($definition, $signature, $runtime_arguments, $context, true);
		$call->specialization = $facts;
		return $call;
	}
}

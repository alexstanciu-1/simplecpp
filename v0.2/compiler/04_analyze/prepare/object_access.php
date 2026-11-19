<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Admit checked shared-object access using settled ancestry; occurrence owner retains all dependency watches. */
final class Object_Access_Preparation
{
	/** Resolve one target and observe both ancestry chains, including the source's inherited polymorphism. */
	public static function prepare(ast_object_access_expression $site, scope $symbols, ast_function_body $owner, ?Dependency_Watches $dependencies): void
	{
		if ($site->target->first_child !== null) self::fail($site, 'Object access target must be a bare class, struct or interface name');
		$target = Type_Resolution::local($site->target, $symbols, $owner, $dependencies);
		if ($target instanceof record_type_definition) $target = $target->shared;
		elseif (!($target instanceof shared_record_type_definition) || !$target->required) {
			self::fail($site, 'Object access target must be a class, struct or interface');
		}
		$source = $site->operand->type_def();
		if (!($source instanceof shared_record_type_definition)) {
			self::fail($site, 'Object access operand must be a shared class, struct or interface');
		}
		foreach ([$source->payload, $target->payload] as $record) {
			for ($type = $record; $type !== null; $type = $type->base()) {
				$dependencies?->watch($symbols->entry($type->declaration->symbol_key()), $owner);
			}
		}
		$upward = $source->payload->derives_from($target->payload);
		// A final class cannot acquire another interface through a future descendant.
		if (($source->payload->declaration->is_final && $target->payload->declaration->kind === record_kind::interface_type && !$upward)
			|| ($target->payload->declaration->is_final && $source->payload->declaration->kind === record_kind::interface_type
				&& !$target->payload->derives_from($source->payload))) self::fail($site, 'Final class does not implement the requested interface relationship');
		// Classes have one base; an interface may overlap an open class or another interface
		// without a currently known common implementer. No descendant inventory is needed.
		if (!$upward && !$target->payload->derives_from($source->payload)
			&& ($source->payload->declaration->kind === record_kind::structure || $target->payload->declaration->kind === record_kind::structure
				|| ($source->payload->declaration->kind === record_kind::class_type && $target->payload->declaration->kind === record_kind::class_type))) {
			self::fail($site, 'Object access requires related classes or related structs');
		}
		if (!$upward && !$source->payload->polymorphic()) self::fail($site, 'Checked object access requires a polymorphic source class, struct or interface');
		$site->settle_access($target, !$upward);
	}

	private static function fail(ast_object_access_expression $site, string $message): never
	{
		throw new diagnostic($site->source(), $site->byte_start(), $message);
	}
}

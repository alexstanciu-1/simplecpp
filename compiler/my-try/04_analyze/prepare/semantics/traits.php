<?php
namespace scpp\compiler;

/** Eligibility belongs to analysis; materialization alone never grants semantics. */
final class Trait_Preparation
{
	/** Whole-use admission is re-evaluated on every coordinator invocation, like imports. */
	public static function targets(collected_record $consumer): Storage /** Storage<collected_trait> */
	{
		$result /** Storage<collected_trait> */ = new Storage();
		$uses /** Storage<trait_use_node> */ = $consumer->syntax()->use_traits;
		foreach ($uses as $use) {
			$names /** Storage<named_type_node> */ = $use->traits;
			foreach ($names as $name) {
				$candidates = Scope_Lookup::types(weakref_get($name->occurrence()->scope), $name->name);
				if (q_count($candidates) !== 1) { throw new \RuntimeException('Trait use requires one unambiguous target: ' . $name->name); }
				if ($candidates[0]->origin() !== type_definition_origin::source) { throw new \RuntimeException('Trait use requires a trait target: ' . $name->name); }
				$target = Model::$type_catalog->source_declarations()->declaration($candidates[0]->definition_id());
				if (!($target instanceof collected_trait)) { throw new \RuntimeException('Trait use requires a trait target: ' . $name->name); }
				$trait = object_cast($target, collected_trait::class);
				self::source($trait);
				foreach ($result as $previous) {
					if ($previous === $trait) { throw new \RuntimeException('Duplicate trait use: ' . $name->name); }
				}
				$result->append($trait);
			}
		}
		return $result;
	}

	public static function source(collected_trait $trait): void
	{
		$syntax = $trait->syntax();
		$uses /** Storage<trait_use_node> */ = $syntax->use_traits;
		$constants /** Storage<member_constant_node> */ = $syntax->constants;
		$extended /** Storage<named_type_node> */ = $syntax->extended_interfaces;
		$implemented /** Storage<named_type_node> */ = $syntax->implemented_interfaces;
		if (!$uses->is_empty() || !$extended->is_empty() || !$implemented->is_empty()
			|| ($syntax->base_class !== null) || (q_count($syntax->modifiers) !== 0)) {
			throw new \RuntimeException('Trait source supports only direct properties, methods and constants');
		}
		$properties /** Storage<property_node> */ = $syntax->properties;
		$methods /** Storage<method_node> */ = $syntax->methods;
		foreach ($properties as $property) { self::member($property); }
		foreach ($methods as $method) { self::member($method); }
		foreach ($constants as $constant) { self::member($constant); }
	}

	public static function validate(collected_record $consumer): void
	{
		foreach (Member_Inventory::contributions($consumer) as $member) {
			self::member(object_cast($member->working->syntax(), declaration_node::class));
		}
	}

	private static function member(declaration_node $syntax): void
	{
		if ($syntax instanceof property_node) {
			$property = object_cast($syntax, property_node::class);
			if ($property->type_syntax === null) {
				throw new \RuntimeException('Trait properties require explicit types');
			}
		}
		elseif ($syntax instanceof method_node) {
			$method = object_cast($syntax, method_node::class);
			if (string_byte_starts_with($method->name, '__')
				|| !$method->has_parsed_body()) {
				throw new \RuntimeException('Trait methods require ordinary concrete bodies');
			}
		}
	}
}

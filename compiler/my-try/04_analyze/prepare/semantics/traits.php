<?php
namespace scpp\compiler;

/** Eligibility belongs to analysis; materialization alone never grants semantics. */
final class Trait_Preparation
{
	/** Invocation-local traversal state: recursive calls must share these maps. */
	private \SplObjectStorage $active /** hash<bool, shared<collected_trait>> */;
	private \SplObjectStorage $visited /** hash<bool, shared<collected_trait>> */;

	public function __construct()
	{
		$this->active = new \SplObjectStorage /** hash<bool, shared<collected_trait>> */();
		$this->visited = new \SplObjectStorage /** hash<bool, shared<collected_trait>> */();
	}

	/** Whole-use admission is re-evaluated on every coordinator invocation, like imports. */
	public static function targets(collected_record $consumer): Storage /** Storage<collected_trait> */
	{
		$result /** Storage<collected_trait> */ = new Storage();
		$traversal = new Trait_Preparation();
		foreach (self::direct_targets($consumer) as $trait) {
			$traversal->expand($trait, $result);
		}
		return $result;
	}

	/** Stable depth-first source order; identity deduplication never hides a cycle. */
	private function expand(collected_trait $trait, Storage $result /** Storage<collected_trait> */): void
	{
		if (isset($this->active[$trait])) { throw new \RuntimeException('Trait use cycle: ' . $trait->name); }
		if (isset($this->visited[$trait])) { return; }
		self::source($trait);
		$this->active[$trait] = true;
		$result->append($trait);
		foreach (self::direct_targets($trait) as $nested) { $this->expand($nested, $result); }
		unset($this->active[$trait]);
		$this->visited[$trait] = true;
	}

	/** Each use list resolves in its own author's lexical environment. */
	private static function direct_targets(collected_member_owner $consumer): Storage /** Storage<collected_trait> */
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
		$constants /** Storage<member_constant_node> */ = $syntax->constants;
		$extended /** Storage<named_type_node> */ = $syntax->extended_interfaces;
		$implemented /** Storage<named_type_node> */ = $syntax->implemented_interfaces;
		if (!$extended->is_empty() || !$implemented->is_empty()
			|| ($syntax->base_class !== null) || (q_count($syntax->modifiers) !== 0)) {
			throw new \RuntimeException('Trait source supports properties, methods, constants and simple trait uses');
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
			if (string_byte_starts_with($method->name, '__')) {
				throw new \RuntimeException('Trait methods require ordinary concrete bodies');
			}
			if (!$method->has_parsed_body()) {
				$abstract = false;
				foreach ($method->modifiers as $modifier) {
					if ($modifier === declaration_modifier::abstract_declaration) { $abstract = true; }
				}
				if (!$abstract || Member_Preparation::is_static($method->modifiers)) {
					throw new \RuntimeException('Trait requirements must be abstract instance methods');
				}
			}
		}
	}
}

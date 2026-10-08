<?php
namespace scpp\compiler;

/** Composition adapter: reuse member grammar without resolving traits in the parser. */
final class Trait_Materialization
{
	/** Build privately and publish only after all members have materialized successfully. */
	public static function instantiate(collected_trait $origin, collected_record $consumer): trait_instance
	{
		$instance = self::build($origin, $consumer);
		$instances /** Storage<trait_instance> */ = $consumer->trait_instances;
		$instances->append($instance);
		return $instance;
	}

	/** Construct privately; the coordinator publishes a complete consumer batch. */
	public static function build(collected_trait $origin, collected_record $consumer): trait_instance
	{
		$syntax = $origin->syntax();
		$uses /** Storage<trait_use_node> */ = $syntax->use_traits;
		$constants /** Storage<member_constant_node> */ = $syntax->constants;
		if (!$uses->is_empty() || !$constants->is_empty()) {
			throw new \RuntimeException('Trait materialization currently supports only direct properties and methods');
		}
		$origins /** Storage<declaration_node> */ = new Storage();
		$properties /** Storage<property_node> */ = $syntax->properties;
		$methods /** Storage<method_node> */ = $syntax->methods;
		foreach ($properties as $property) { $origins->append($property); }
		foreach ($methods as $method) { $origins->append($method); }
		$lexical = new scope();
		$lexical->set_parent(weakref_get($origin->scope));
		$tokens = Source_Fragments::snapshot($origin->token_snapshot());
		$run = new Parser_Run($tokens, $lexical);
		$parsed = $run->parse_member_fragments($consumer, $origins);
		$instance = new trait_instance($origin, $consumer, $parsed);
		$members /** Storage<trait_member_instance> */ = $instance->members;
		$working /** Storage<declaration_node> */ = $parsed->root->single_body()->declarations;
		$index = 0;
		foreach ($origins as $member) {
			$members->append(new trait_member_instance($member->occurrence(), $working[$index]->occurrence()));
			$index++;
		}
		return $instance;
	}

}

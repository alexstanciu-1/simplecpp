<?php
namespace scpp\compiler;

/** Composition adapter: reuse member grammar without resolving traits in the parser. */
final class Trait_Materialization
{
	/** Build privately and publish only after all members have materialized successfully. */
	public static function instantiate(collected_trait $origin, collected_record $consumer): trait_instance
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
		$tokens = self::snapshot($origin->token_snapshot());
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
		$instances /** Storage<trait_instance> */ = $consumer->trait_instances;
		$instances->append($instance);
		return $instance;
	}

	/** Token cleanup mutates rows and offsets: copying just the Storage handle is insufficient. */
	private static function snapshot(token_list $source): token_list
	{
		$result = new token_list();
		$result->file = $source->file;
		$result->content = $source->content;
		$result->first_token = $source->first_token;
		$result->end_token = $source->end_token;
		$result->content_offset = $source->content_offset;
		$rows /** Storage<token> */ = $source->tokens;
		$copies /** Storage<token> */ = $result->tokens;
		foreach ($rows as $row) { $copies->append(new token($row->offset, $row->length, $row->text())); }
		return $result;
	}
}

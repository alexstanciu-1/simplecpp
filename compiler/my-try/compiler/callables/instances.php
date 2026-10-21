<?php
namespace scpp\compiler;

/** Coordinator materialization and retirement of analysis-requested implementations. */
final class Callable_Instances
{
	/** Intern a complete private implementation; semantic dispatch admission belongs to analysis. */
	public static function obtain(collected_method $origin, collected_record $called): callable_instance
	{
		if (weakref_get($origin->instance_context) !== null) {
			throw new \LogicException('Instantiate the original method, not an instance');
		}
		$declaring = $origin->declaring_type();
		if (!($declaring instanceof collected_record) || !$origin->callable_syntax()->has_parsed_body()) {
			throw new \RuntimeException('Callable instance requires a concrete record method body');
		}
		if ($origin->collection->deleted || $called->collection->deleted
			|| ($origin->change_status === change_state::deleted) || ($called->change_status === change_state::deleted)
			|| !$origin->collection->parse_complete || !$called->collection->parse_complete) {
			throw new \RuntimeException('Callable instance requires live completed source parsing');
		}
		$instances /** Storage<callable_instance> */ = $origin->called_instances;
		foreach ($instances as $instance)
		{
			if ($instance->called_type() === $called) {
				if (!self::current($instance)) {
					throw new \LogicException('Retire stale callable instances before materialization');
				}
				return $instance;
			}
		}
		$lexical = new scope();
		$lexical->set_parent(weakref_get($origin->scope));
		$members /** Storage<declaration_node> */ = new Storage();
		$members->append($origin->callable_syntax());
		$run = new Parser_Run(Source_Fragments::snapshot($origin->token_snapshot()), $lexical);
		$parsed = $run->parse_member_fragments(object_cast($declaring, collected_record::class), $members);
		$declarations /** Storage<declaration_node> */ = $parsed->root->single_body()->declarations;
		$working = object_cast($declarations[0]->occurrence(), collected_method::class);
		$instance = new callable_instance($origin, $called, $parsed, $working);
		$working->instance_context = weak($instance);
		$instances->append($instance);
		return $instance;
	}

	/** Source edits and scope replacement invalidate the private syntax before origin disposal. */
	public static function current(callable_instance $instance): bool
	{
		$origin = $instance->origin();
		$called = $instance->called_type();
		return !$origin->collection->deleted && !$called->collection->deleted
			&& ($origin->change_status !== change_state::deleted) && ($called->change_status !== change_state::deleted)
			&& ($instance->parsed->root_scope()->parent_scope() === weakref_get($origin->scope))
			&& (weakref_get($instance->called_scope) === $called->syntax()->member_scope())
			&& ($instance->origin_input === $origin->collection->source_file()->content)
			&& ($instance->called_input === $called->collection->source_file()->content);
	}

	/** Include tombstones and private trait origins until semantic retirement finishes. */
	private static function methods(Storage $sources /** Storage<collected_file> */): Storage /** Storage<collected_method> */
	{
		$collections /** Storage<collected_file> */ = new Storage();
		foreach ($sources as $source) {
			$collections->append($source);
		}
		foreach (Trait_Composition::private_parses($sources) as $parsed) {
			$collections->append($parsed->collection);
		}
		$result /** Storage<collected_method> */ = new Storage();
		foreach ($collections as $collection)
		{
			$entries /** Storage<collected_name> */ = $collection->entries;
			foreach ($entries as $entry) {
				if ($entry instanceof collected_method) {
					$result->append(object_cast($entry, collected_method::class));
				}
			}
		}
		return $result;
	}

	/** Retire changed call families, otherwise preserve live source-snapshot keys. */
	public static function retire_stale(Storage $sources /** Storage<collected_file> */, scope $language): void
	{
		// Call-shape changes can remove the last demand. Conservatively rebuild the family
		// rather than retaining unreachable implementations or their obsolete diagnostics.
		if (self::call_shape_changed($sources)) {
			self::retire_sources($sources, $language);
			return;
		}
		foreach (self::methods($sources) as $method)
		{
			$previous /** Storage<callable_instance> */ = $method->called_instances;
			if ($previous->is_empty()) {
				continue;
			}
			$live /** Storage<callable_instance> */ = new Storage();
			$removed /** Storage<parsed_file> */ = new Storage();
			foreach ($previous as $instance)
			{
				if (self::current($instance)) {
					$live->append($instance);
				}
				else {
					$removed->append($instance->parsed);
				}
			}
			if (!$removed->is_empty()) {
				Source_Fragments::retire($removed, $language);
				$method->called_instances = $live;
			}
		}
	}

	/** Constant-only edits preserve identities and use ordinary dependency invalidation. */
	private static function call_shape_changed(Storage $sources /** Storage<collected_file> */): bool
	{
		foreach ($sources as $source) {
			if ($source->deleted) { return true; }
			$bodies /** Storage<namespace_body_node> */ = $source->root->namespace_bodies;
			foreach ($bodies as $body) {
				if ($body->imports_changed || $body->body->syntax_changed) { return true; }
			}
			$entries /** Storage<collected_name> */ = $source->entries;
			foreach ($entries as $entry) {
				if ($entry instanceof collected_record) {
					if ($entry->change_status !== change_state::unchanged) { return true; }
				}
				if ($entry instanceof collected_source_callable) {
					$callable = object_cast($entry, collected_source_callable::class);
					if ($callable instanceof collected_method) {
						if (object_cast($callable, collected_method::class)->declaring_type() instanceof collected_trait) { continue; }
					}
					if ($entry->change_status !== change_state::unchanged) { return true; }
					if ($callable->callable_syntax()->has_parsed_body()) {
						if ($callable->callable_syntax()->require_body()->syntax_changed) { return true; }
					}
				}
			}
		}
		return false;
	}

	/** Full origin disposal must retire private dependency work before releasing instance ownership. */
	public static function retire_sources(Storage $sources /** Storage<collected_file> */, scope $language): void
	{
		foreach (self::methods($sources) as $method)
		{
			$instances /** Storage<callable_instance> */ = $method->called_instances;
			if ($instances->is_empty()) {
				continue;
			}
			$removed /** Storage<parsed_file> */ = new Storage();
			foreach ($instances as $instance) {
				$removed->append($instance->parsed);
			}
			Source_Fragments::retire($removed, $language);
			$method->called_instances = new Storage /** Storage<callable_instance> */();
		}
	}

	/** Reset traversal observes existing private owners, including deleted origin declarations. */
	public static function private_parses(Storage $sources /** Storage<collected_file> */): Storage /** Storage<parsed_file> */
	{
		$result /** Storage<parsed_file> */ = new Storage();
		foreach (self::methods($sources) as $method) {
			$instances /** Storage<callable_instance> */ = $method->called_instances;
			foreach ($instances as $instance) {
				$result->append($instance->parsed);
			}
		}
		return $result;
	}
}

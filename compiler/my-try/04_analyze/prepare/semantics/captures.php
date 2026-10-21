<?php

/* Resolve creation-site environments; syntax and callable signatures stay separate. */
namespace scpp\compiler;

final class Capture_Preparation
{
	public static function prepare(closure_node $syntax, preparation_context $context): Storage /** Storage<prepared_capture> */
	{
		$names /** vector<string> */ = [];
		$modes /** hash<passing_mode> */ = [];
		if ($syntax->syntax_kind === closure_syntax_kind::arrow) {
			$names = self::discover($syntax, $syntax->anonymous_identity()->collection());
		}
		else {
			$references /** Storage<closure_capture_node> */ = $syntax->captures;
			foreach ($references as $reference) {
				if ($reference->variable->name === 'this') { Method_Preparation::base($reference->variable, $context); }
				else { Expression_Preparation::prepare($reference->variable, $context); }
				$name = $reference->variable->name;
				$names[] = $name;
				$modes[$name] = $reference->mode;
			}
		}
		$captures /** Storage<prepared_capture> */ = new Storage();
		$seen /** hash<bool> */ = [];
		foreach ($names as $name) {
			if (isset($seen[$name])) { throw new \RuntimeException('Duplicate capture: ' . $name); }
			$seen[$name] = true;
			$bindings /** vector<prepared_source_storage> */ = $context->locals->named($name);
			if (q_count($bindings) !== 1) { throw new \RuntimeException('Capture requires an established local: ' . $name); }
			$binding = $bindings[0];
			$capture = new prepared_capture();
			$capture->declaration = $binding->declaration;
			$capture->type = $binding->storage_type();
			$mode = passing_mode::value;
			if (isset($modes[$name])) { $mode = $modes[$name]; }
			if ($name === 'this') {
				if ($mode === passing_mode::reference) { throw new \RuntimeException('Receiver capture uses $this, not &$this'); }
				$receiver = $context->receiver;
				if ($receiver === null) { throw new \RuntimeException('Receiver capture requires an instance body'); }
				$origin = Reference_Preparation::storage_place($binding);
				$capture->borrow = new storage_borrow(new prepared_place($receiver, null, true), Storage_Lifetimes::place($origin, $context));
				$capture->access = storage_access::writable;
			}
			elseif ($mode === passing_mode::reference) {
				$place = Reference_Preparation::storage_place($binding);
				// Stable projections retain their containing root; aliases introduce no new lifetime.
				$storage = $place->root_storage();
				if (!$place->borrowable()
					|| (!($storage instanceof prepared_binding) && !($storage instanceof prepared_parameter)
						&& !($storage instanceof prepared_capture))) {
					throw new \RuntimeException('Reference capture requires stable local, parameter or captured storage');
				}
				$capture->borrow = new storage_borrow($place, Storage_Lifetimes::place($place, $context));
				$capture->access = $place->access();
			}
			else {
				Capability_Preparation::require_copy($capture->type, $context);
				$capture->transfer = self::transfer_type($capture->type);
			}
			$captures->append($capture);
		}
		return $captures;
	}

	/** Capture permission is separate from copyability; unknown domains stay closed. */
	private static function transfer_type(canonical_type_use $type): capture_transfer
	{
		$canonical = Type_Queries::canonical($type);
		$definition = $canonical->definition();
		if (($definition instanceof callable_type_definition) || ($definition instanceof closure_type_definition)) {
			return capture_transfer::callable_value;
		}
		$family = $canonical->family();
		if (($family === type_family::boolean) || ($family === type_family::integer)
			|| ($family === type_family::floating) || ($family === type_family::null_sentinel)
			|| $type->matches(Language_Types::string_type(Model::$language_scope))) {
			return capture_transfer::owned_value;
		}
		if ($definition instanceof abstract_nominal_type_definition) {
			if (object_cast($definition, abstract_nominal_type_definition::class)->nominal_kind() === nominal_type_kind::enumeration) {
				return capture_transfer::owned_value;
			}
		}
		if ($canonical instanceof applied_template_type) {
			if (($definition === Model::$type_catalog->definition('vector'))
				|| ($definition === Model::$type_catalog->definition('hash'))
				|| ($definition instanceof nullable_type_definition) || ($definition instanceof result_type_definition)) {
				$application = object_cast($canonical, applied_template_type::class);
				foreach ($application->arguments() as $index => $argument) {
					// The hash key protocol already restricts mixed keys to keyable scalars.
					if (($definition === Model::$type_catalog->definition('hash')) && ($index === 1)) {
						if (Type_Queries::canonical($argument)->definition() instanceof mixed_semantics) { continue; }
					}
					if (self::transfer_type($argument) !== capture_transfer::owned_value) { return capture_transfer::forbidden; }
				}
				return capture_transfer::owned_value;
			}
		}
		return capture_transfer::forbidden;
	}

	/** Reject known forbidden environments; erased callable values retain a runtime guard. */
	public static function require_transfer(canonical_type_use $type, preparation_context $context): void
	{
		if (!(Type_Queries::canonical($type)->definition() instanceof closure_type_definition)) { return; }
		$identity = Model::$type_catalog->closures()->identity($type);
		$context->worker->prepare_anonymous($identity, $context->owner);
		$body = object_cast($identity->syntax()->require_body()->work(), anonymous_body_work::class);
		$captures /** Storage<prepared_capture> */ = $body->captures;
		foreach ($captures as $capture) {
			if ($capture->transfer === capture_transfer::forbidden) {
				throw new \RuntimeException('Task callback captures are not transferable');
			}
			if ($capture->transfer === capture_transfer::callable_value) { self::require_transfer($capture->type, $context); }
		}
	}

	/** Capture environments, not registry flags, own transitive borrowing obligations. */
	public static function borrowed_lifetimes(canonical_type_use $type, preparation_context $context): Storage /** Storage<storage_lifetime> */
	{
		$result /** Storage<storage_lifetime> */ = new Storage();
		if (!(Type_Queries::canonical($type)->definition() instanceof closure_type_definition)) { return $result; }
		$identity = Model::$type_catalog->closures()->identity($type);
		$context->worker->prepare_anonymous($identity, $context->owner);
		$body = object_cast($identity->syntax()->require_body()->work(), anonymous_body_work::class);
		$captures /** Storage<prepared_capture> */ = $body->captures;
		foreach ($captures as $capture) {
			if ($capture->borrow !== null) { $result->append($capture->borrow->lifetime); }
			foreach (self::borrowed_lifetimes($capture->type, $context) as $origin) { $result->append($origin); }
		}
		return $result;
	}

	/** Existing occurrence/scope indexes supply free names, not a second AST traversal. */
	private static function discover(closure_node $target, collected_file $collection): array /** vector<string> */
	{
		$owners /** hash<closure_node, shared<scope>> */ = new \SplObjectStorage /** hash<closure_node, shared<scope>> */();
		$anonymous /** Storage<anonymous_callable_identity> */ = $collection->anonymous_callables;
		foreach ($anonymous as $identity) {
			if (!$identity->deleted) {
				$syntax = $identity->syntax();
				$owners[$syntax->body_scope()] = $syntax;
			}
		}
		$names /** vector<string> */ = [];
		$seen /** hash<bool> */ = [];
		$entries /** Storage<collected_name> */ = $collection->entries;
		$indexes /** vector<int> */ = $collection->variable_references;
		foreach ($indexes as $index) {
			$entry = $entries[$index];
			if ($entry->change_status === change_state::deleted) { continue; }
			if (isset($seen[$entry->name])) { continue; }
			$lookup_scope = object_cast(weakref_get($entry->scope), scope::class);
			if (self::required_by($lookup_scope, $entry->name, $target, $owners)) {
				$seen[$entry->name] = true;
				$names[] = $entry->name;
			}
		}
		return $names;
	}

	/** Parameters stop propagation; explicit nested bodies never contribute implicit captures. */
	private static function required_by(scope $start, string $name, closure_node $target,
		\SplObjectStorage $owners /** hash<closure_node, shared<scope>> */): bool
	{
		$current /** nullable<scope> */ = $start;
		while ($current !== null) {
			$lookup_scope /** scope */ = $current;
			if ($lookup_scope->is_function()) {
				if (!isset($owners[$lookup_scope])) { return false; }
				$owner /** closure_node */ = $owners[$lookup_scope];
				if (($owner !== $target) && ($owner->syntax_kind !== closure_syntax_kind::arrow)) { return false; }
				$parameters /** Storage<parameter_node> */ = $owner->signature_syntax->parameters;
				foreach ($parameters as $parameter) {
					if ($parameter->name === $name) { return false; }
				}
				if ($owner === $target) { return true; }
				// Body and signature scopes form one callable boundary, not two nested callables.
				$current = $owner->signature_scope()->parent_scope();
				continue;
			}
			$current = $lookup_scope->parent_scope();
		}
		return false;
	}
}

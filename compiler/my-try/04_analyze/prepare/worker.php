<?php

/* One incremental preparation path: settle declarations, then process selected bodies once. */
namespace scpp\compiler;

final class Preparation_Worker
{
	private scope $language;
	private Storage $instance_requests /** Storage<callable_instance_request> */;
	/** Identity-tagged control signal, never a user diagnostic or retained work field. */
	private \RuntimeException $materialization_signal;
	/** A failed item is attempted at most once by this invocation, but stays changed for the next. */
	private \SplObjectStorage $attempt_failed /** hash<bool, shared<preparation_owner>> */;
	private \SplObjectStorage $contracts /** hash<bool, shared<contract_work>> */;
	private \SplObjectStorage $callable_bodies /** hash<bool, shared<body_work>> */;
	private \SplObjectStorage $file_bodies /** hash<bool, shared<file_body_work>> */;
	private \SplObjectStorage $owners /** hash<bool, shared<preparation_owner>> */;

	/** Work lists are invocation-local; retained dependency records never reference this worker. */
	public function __construct(scope $language)
	{
		$this->language = $language;
		$this->instance_requests = new Storage /** Storage<callable_instance_request> */();
		$this->materialization_signal = new \RuntimeException('Callable materialization requires the coordinator');
		$this->attempt_failed = new \SplObjectStorage /** hash<bool, shared<preparation_owner>> */();
		$this->contracts = new \SplObjectStorage /** hash<bool, shared<contract_work>> */();
		$this->callable_bodies = new \SplObjectStorage /** hash<bool, shared<body_work>> */();
		$this->file_bodies = new \SplObjectStorage /** hash<bool, shared<file_body_work>> */();
		$this->owners = new \SplObjectStorage /** hash<bool, shared<preparation_owner>> */();
	}

	public function is_materialization_yield(\Throwable $error): bool
	{
		return same_exception($error, $this->materialization_signal);
	}

	public function materialization_requests(): Storage /** Storage<callable_instance_request> */
	{
		return $this->instance_requests;
	}

	/** A body may demand an implementation; declaration/default suspension is not enabled. */
	public function require_callable_instance(preparation_owner $owner, collected_method $origin, collected_record $called): collected_method
	{
		if (!($owner instanceof body_work)) {
			throw new \RuntimeException('Called-type implementation requires executable body context');
		}
		if (weakref_get($origin->instance_context) !== null) {
			throw new \LogicException('Callable demand requires an original method');
		}
		$instance = Callable_Inventory::find($origin, $called);
		if ($instance === null) {
			$requests /** Storage<callable_instance_request> */ = $this->instance_requests;
			$requests->append(new callable_instance_request($origin, $called));
			throw $this->materialization_signal;
		}
		$this->select_callable($instance->working->callable_syntax());
		$this->require_declaration($owner, $instance->working);
		return $instance->working;
	}

	/** After coordinator-ordered retirement, fresh/update runs share selection and execution. */
	public function prepare(Storage $sources /** Storage<collected_file> */): Storage /** Storage<prepared_file> */
	{
		$this->prepare_relationships($sources);
		foreach ($sources as $source) {
			if (!$source->deleted) {
				if ($source->prepared !== null) {
					$source->prepared->entry_body = null;
				}
				$this->select($source);
			}
		}
		// Private implementations share ordinary work, but are never additional program files.
		foreach (Callable_Inventory::source_collections($sources) as $collection)
		{
			if ($collection->deleted) { continue; }
			$entries /** Storage<collected_name> */ = $collection->entries;
			foreach ($collection->defined_elements as $position) {
				$entry = $entries[$position];
				if ($entry instanceof collected_method) {
					$method = object_cast($entry, collected_method::class);
					if (weakref_get($method->instance_context) !== null) {
						$this->select_callable($method->callable_syntax());
					}
				}
			}
		}
		$this->changed_lookups();

		// Contract processing may schedule more contracts; identity sets coalesce notifications.
		while (q_count($this->contracts) !== 0)
		{
			foreach ($this->contracts as $owner /** @object-key */)
			{
				try {
					$this->contract($owner);
				}
				catch (\RuntimeException $error) {
					// Failure is retained on owners; independent declarations may still settle.
				}
				break;
			}
		}
		while (q_count($this->callable_bodies) !== 0) {
			foreach ($this->callable_bodies as $owner /** @object-key */) {
				// Native iteration borrows the key slot; retain the work before erasing it.
				$selected_body /** body_work */ = $owner;
				unset($this->callable_bodies[$selected_body]);
				$this->body($selected_body);
				break;
			}
		}
		foreach ($this->file_bodies as $owner /** @object-key */) {
			$this->body($owner);
		}

		$this->require_success();
		// Only settled call facts participate; failure uses ordinary retained retry/publication guards.
		$cyclic = (new Reference_Cycles())->find($sources);
		foreach ($cyclic as $vertex) {
			$body /** function_body_node */ = $vertex->body;
			$body_owner /** body_work */ = $body->work();
			$this->failure($body_owner, 'S2S recursive reference-return cycles are deferred: ' . $vertex->label);
		}
		$this->require_success();

		$result /** Storage<prepared_file> */ = new Storage();
		foreach ($sources as $source)
		{
			if ($source->deleted) {
				continue;
			}
			if ($source->prepared === null) {
				$source->prepared = new prepared_file();
				$source->prepared->source = $source;
			}
			$prepared /** prepared_file */ = $source->prepared;
			$prepared->entry_body = weak(Program_Preparation::source_body($source));
			$result->append($prepared);
		}
		foreach ($result as $prepared) {
			$bodies /** Storage<namespace_body_node> */ = $prepared->source->root->namespace_bodies;
			foreach ($bodies as $body) { $body->imports_changed = false; }
			$entries /** Storage<collected_name> */ = $prepared->source->entries;
			foreach ($entries as $entry) {
				if ($entry instanceof collected_source_callable) {
					$callable = object_cast($entry, collected_source_callable::class);
					if (Callable_Inventory::deferred_body($callable) && $callable->callable_syntax()->has_parsed_body()) {
						$callable->callable_syntax()->require_body()->syntax_changed = false;
					}
				}
			}
		}
		return $result;
	}

	/** Analysis-only checkpoint; the same scheduler settles relations without claiming layout readiness. */
	public function prepare_relationships(Storage $sources /** Storage<collected_file> */): Storage /** Storage<type_relationship_work> */
	{
		$selected /** Storage<type_relationship_work> */ = new Storage();
		foreach ($sources as $source) {
			if (!$source->deleted && !$source->parse_complete) {
				throw new \RuntimeException('Relationships require a successful parsing/collection join');
			}
		}
		foreach ($sources as $source) {
			if ($source->deleted) { continue; }
			$bodies /** Storage<namespace_body_node> */ = $source->root->namespace_bodies;
			foreach ($bodies as $body) {
				$declarations /** Storage<declaration_node> */ = $body->declarations;
				foreach ($declarations as $node) {
					if ($node instanceof type_declaration_node) {
						$entry = object_cast($node->occurrence(), collected_member_owner::class);
						if ($entry instanceof collected_trait) { Trait_Preparation::source(object_cast($entry, collected_trait::class)); continue; }
						$work = $this->relationship_owner($entry);
						$this->select_owner($work);
						$selected->append($work);
					}
				}
			}
		}
		$this->changed_lookups();
		Import_Preparation::validate($sources);
		$error = '';
		foreach ($selected as $work) {
			try { $this->contract($work); }
			catch (\RuntimeException $failure) { $error .= $failure->getMessage() . "\n"; }
		}
		if ($error !== '') { throw new \RuntimeException($error); }
		return $selected;
	}

	/** Semantic retirement precedes frontend disposal, ordered by the coordinator. */
	public function retire_deleted_sources(Storage $sources /** Storage<collected_file> */): void
	{
		$retired = $this->retiring_owners($sources, false);
		$this->notify_retiring($retired);
		foreach ($retired as $owner /** @object-key */) {
			$this->retire($owner);
		}
		foreach ($sources as $source) {
			$this->detach_retired_work($source);
		}
	}

	/** Full reset has no surviving consumers; sever registrations before dropping graph roots. */
	public function discard_sources(Storage $sources /** Storage<collected_file> */): void
	{
		$retired = $this->retiring_owners($sources, true);
		foreach ($retired as $owner /** @object-key */) {
			$this->retire($owner);
		}
		foreach ($sources as $source) {
			$source->preparation_changes = new \SplObjectStorage /** hash<bool, shared<preparation_owner>> */();
			$anonymous /** Storage<anonymous_callable_identity> */ = $source->anonymous_callables;
			foreach ($anonymous as $identity) {
				$identity->signature_work = null;
				if ($identity->syntax()->has_parsed_body()) { $identity->syntax()->require_body()->detach_work(); }
			}
		}
	}

	/** Mark the complete batch before notification, including bodies belonging to deleted functions. */
	private function retiring_owners(Storage $sources /** Storage<collected_file> */, bool $all): \SplObjectStorage /** hash<bool, shared<preparation_owner>> */
	{
		$result /** hash<bool, shared<preparation_owner>> */ = new \SplObjectStorage /** hash<bool, shared<preparation_owner>> */();
		foreach ($sources as $source)
		{
			$anonymous /** Storage<anonymous_callable_identity> */ = $source->anonymous_callables;
			foreach ($anonymous as $identity) {
				if (!$all && !$source->deleted && !$identity->deleted) { continue; }
				if ($identity->signature_work !== null) { $result[$identity->signature_work] = true; }
				if ($identity->syntax()->has_parsed_body()) {
					$work = $identity->syntax()->require_body()->work();
					if ($work !== null) { $result[$work] = true; }
				}
			}
			$bodies /** Storage<namespace_body_node> */ = $source->root->retired_namespace_bodies;
			foreach ($bodies as $namespace_body) {
				if ($namespace_body->body->work() !== null) {
					$result[$namespace_body->body->work()] = true;
				}
			}
			if ($all || $source->deleted) {
				$active_bodies /** Storage<namespace_body_node> */ = $source->root->namespace_bodies;
				foreach ($active_bodies as $namespace_body) {
					if ($namespace_body->body->work() !== null) {
						$result[$namespace_body->body->work()] = true;
					}
				}
			}
			$entries /** Storage<collected_name> */ = $source->entries;
			foreach ($entries as $entry)
			{
				if ($entry instanceof collected_source_callable) {
					$retired_bodies /** Storage<function_body_node> */ = object_cast($entry, collected_source_callable::class)->callable_syntax()->retired_bodies;
					foreach ($retired_bodies as $retired_body) {
						if ($retired_body->work() !== null) { $result[$retired_body->work()] = true; }
					}
				}
				if ((!$all) && (!$source->deleted) && ($entry->change_status !== change_state::deleted)) {
					continue;
				}
				if ($entry->preparation_work_owner() !== null) {
					$result[$entry->preparation_work_owner()] = true;
				}
				if ($entry instanceof collected_member_owner) {
					$relationships = object_cast($entry, collected_member_owner::class)->relationship_work;
					if ($relationships !== null) { $result[$relationships] = true; }
					$contracts = object_cast($entry, collected_member_owner::class)->contract_work;
					if ($contracts !== null) { $result[$contracts] = true; }
					$lifecycle = object_cast($entry, collected_member_owner::class)->lifecycle_work;
					if ($lifecycle !== null) { $result[$lifecycle] = true; }
				}
				if ($entry instanceof collected_source_callable) {
					$function = object_cast($entry, collected_source_callable::class)->callable_syntax();
					if ($function->has_parsed_body()) {
						if ($function->require_body()->work() !== null) {
							$result[$function->require_body()->work()] = true;
						}
					}
				}
			}
		}
		foreach ($result as $owner /** @object-key */) {
			$owner->change_status = change_state::deleted;
		}
		return $result;
	}

	/** Traverse intact reverse links once; deleted owners cannot be scheduled back into work. */
	private function notify_retiring(\SplObjectStorage $retired /** hash<bool, shared<preparation_owner>> */): void
	{
		$pending /** Storage<preparation_owner> */ = new Storage();
		$seen /** hash<bool, shared<preparation_owner>> */ = new \SplObjectStorage /** hash<bool, shared<preparation_owner>> */();
		foreach ($retired as $owner /** @object-key */) {
			$pending->append($owner);
		}
		for ($index = 0; $index < q_count($pending); $index++)
		{
			$owner = $pending[$index];
			if (isset($seen[$owner])) {
				continue;
			}
			$seen[$owner] = true;
			$this->schedule($owner);
			foreach ($owner->dependents as $dependent /** @object-key */) {
				$pending->append($dependent);
			}
		}
	}

	/** Source change flags seed stable owners; analysis selects their typed work. */
	private function select(collected_file $source): void
	{
		$anonymous /** Storage<anonymous_callable_identity> */ = $source->anonymous_callables;
		foreach ($anonymous as $identity) {
			if ($identity->deleted || ($identity->signature_work === null)) { continue; }
			$this->select_owner($identity->signature_work);
			$work = $identity->syntax()->require_body()->work();
			if ($work !== null) { $this->select_owner($work); }
		}
		$bodies /** Storage<namespace_body_node> */ = $source->root->namespace_bodies;
		foreach ($bodies as $namespace_body) {
			$this->select_source_body($source, $namespace_body);
		}
	}

	/** Select each occurrence's executable work and independently owned declarations. */
	private function select_source_body(collected_file $source, namespace_body_node $namespace_body): void
	{
		$body = $namespace_body->body;
		if ($body->work() === null) {
			$body->attach_work(new file_body_work($source, $body));
		}
		$file_body /** body_work */ = $body->work();
		$this->select_owner($file_body);
		$nodes /** Storage<declaration_node> */ = $namespace_body->declarations;
		foreach ($nodes as $node) {
			if ($node instanceof callable_declaration_node) {
				$this->select_callable(object_cast($node, callable_declaration_node::class));
			}
			elseif ($node instanceof type_declaration_node) {
				$this->select_record(object_cast($node, type_declaration_node::class));
			}
			elseif ($node instanceof namespace_constant_node) {
				$this->select_owner($this->declaration_owner(object_cast($node->occurrence(), collected_constant::class)));
			}
			elseif ($node instanceof enum_node) {
				$this->select_owner($this->declaration_owner(object_cast($node->occurrence(), collected_enum::class)));
			}
			else { throw new \RuntimeException('Preparation is not supported for this declaration'); }
		}
	}

	/** Signature and implementation remain independent work identities. */
	public function prepare_anonymous(anonymous_callable_identity $identity, preparation_owner $consumer,
		?Storage $captures /** Storage<prepared_capture> */ = null, ?canonical_type_use $expected_type = null): void
	{
		if ($identity->signature_work === null) { $identity->signature_work = new anonymous_signature_work($identity); }
		$signature /** anonymous_signature_work */ = $identity->signature_work;
		if ($captures !== null) {
			// Only creation supplies inputs; later signature/capability queries retain them.
			$expected /** nullable<canonical_type_use> */ = null;
			if ($expected_type !== null) {
				if (Type_Queries::canonical($expected_type)->definition() instanceof callable_type_definition) { $expected = $expected_type; }
			}
			if ($signature->expected_type !== $expected) {
				$signature->expected_type = $expected;
				unset($this->attempt_failed[$signature]);
				$this->schedule($signature);
			}
		}
		$this->select_owner($signature);
		$this->require_contract($consumer, $signature);
		$syntax = $identity->syntax();
		if ($syntax->require_body()->work() === null) { $syntax->require_body()->attach_work(new anonymous_body_work($identity)); }
		$body /** body_work */ = $syntax->require_body()->work();
		if ($captures !== null) {
			object_cast($body, anonymous_body_work::class)->captures = $captures;
			$body->state = preparation_state::pending;
			// Newly supplied creation-site facts permit retry after an earlier scheduled attempt.
			unset($this->attempt_failed[$body]);
		}
		$this->owners[$body] = true;
		if (isset($this->attempt_failed[$body])) { throw new \RuntimeException($body->failure_message); }
		if (!Preparation_Status::complete($body)) { $this->body($body); }
		unset($this->callable_bodies[$body]);
		if ($body->failed) { throw new \RuntimeException($body->failure_message); }
	}

	/** Signature and implementation remain independent work identities. */
	public function select_callable(callable_declaration_node $node): void
	{
		$entry = object_cast($node->occurrence(), collected_source_callable::class);
		$this->select_owner($this->callable_owner($entry));
		if (Callable_Inventory::deferred_body($entry)) { return; }
		if (!$node->has_parsed_body()) { return; }
		if ($node->require_body()->work() === null) {
			$node->require_body()->attach_work(new source_callable_body_work($entry));
		}
		$body /** body_work */ = $node->require_body()->work();
		$this->select_owner($body);
	}

	public function select_record(type_declaration_node $node): void
	{
		$entry = object_cast($node->occurrence(), collected_member_owner::class);
		if ($entry instanceof collected_trait) {
			Trait_Preparation::source(object_cast($entry, collected_trait::class));
			return;
		}
		if ($entry instanceof collected_record) { $this->select_owner($this->record_owner(object_cast($entry, collected_record::class))); }
		$this->select_owner($this->contract_owner($entry));
		$this->select_owner($this->lifecycle_owner($entry));
		$constants /** Storage<member_constant_node> */ = Member_Inventory::constants($entry);
		foreach ($constants as $constant) {
			$this->select_owner($this->declaration_owner(object_cast($constant->occurrence(), collected_constant::class)));
		}
		$methods /** Storage<method_node> */ = Member_Inventory::methods($entry);
		foreach (Member_Inventory::requirements($entry) as $requirement) { $this->select_callable($requirement->callable_syntax()); }
		foreach ($methods as $method) {
			$this->select_callable($method);
		}
	}

	/** Composition membership changes independently of authored syntax. */
	public function invalidate_members(collected_record $record): void
	{
		$work = $record->preparation_work_owner();
		if ($work !== null) { $this->schedule($work); }
		if ($record->contract_work !== null) { $this->schedule($record->contract_work); }
		if ($record->lifecycle_work !== null) { $this->schedule($record->lifecycle_work); }
	}

	private function select_owner(preparation_owner $owner): void
	{
		$this->owners[$owner] = true;
		if (Preparation_Status::source_changed($owner) || ($owner->change_status !== change_state::unchanged)) {
			$this->schedule($owner);
		}
	}

	/** A function signature and its body have different owners and independent queue membership. */
	private function schedule(preparation_owner $owner): void
	{
		if ($owner->source->deleted || ($owner->change_status === change_state::deleted)) {
			return;
		}
		if ($owner->declaration() !== null) {
			$declaration /** collected_name */ = $owner->declaration();
			if ($declaration->change_status === change_state::deleted) {
				return;
			}
		}
		if ($owner->change_status !== change_state::added) {
			$owner->change_status = change_state::changed;
		}
		if ($owner instanceof contract_work) {
			$this->enqueue_contract(object_cast($owner, contract_work::class));
		}
		elseif (($owner instanceof source_callable_body_work) || ($owner instanceof anonymous_body_work)) {
			$this->enqueue_callable_body(object_cast($owner, body_work::class));
		}
		elseif ($owner instanceof file_body_work) {
			$this->enqueue_file_body(object_cast($owner, file_body_work::class));
		}
		else { throw new \LogicException('Unsupported preparation work role'); }
	}

	/** Apply the common queue guard after recording any declaration change. */
	private function queue_ready(preparation_owner $owner): bool
	{
		if ($owner->state === preparation_state::processing) {
			return false;
		}
		$owner->state = preparation_state::pending;
		return !isset($this->attempt_failed[$owner]);
	}

	/** Declaration change state follows its work state even during recursive processing. */
	private function enqueue_contract(contract_work $owner): void
	{
		$entry = $owner->declaration();
		if ($entry !== null) {
			if (!($owner instanceof type_semantic_work) && ($entry->change_status !== change_state::added)) {
				$entry->change_status = change_state::changed;
			}
		}
		if ($this->queue_ready($owner)) {
			$this->contracts[$owner] = true;
		}
	}

	private function enqueue_callable_body(body_work $owner): void
	{
		if ($this->queue_ready($owner)) {
			$this->callable_bodies[$owner] = true;
		}
	}

	private function enqueue_file_body(file_body_work $owner): void
	{
		if ($this->queue_ready($owner)) {
			$this->file_bodies[$owner] = true;
		}
	}

	public function declaration_owner(collected_definition $entry): contract_work
	{
		if ($entry instanceof collected_source_callable) {
			return $this->callable_owner(object_cast($entry, collected_source_callable::class));
		}
		if ($entry instanceof collected_record) {
			return $this->record_owner(object_cast($entry, collected_record::class));
		}
		if ($entry instanceof collected_constant) {
			if ($entry->preparation === null) { $entry->preparation = new constant_definition_work(object_cast($entry, collected_constant::class)); }
			return $entry->preparation;
		}
		if ($entry instanceof collected_enum) {
			if ($entry->preparation === null) {
				$entry->preparation = new enum_definition_work(object_cast($entry, collected_enum::class));
			}
			return $entry->preparation;
		}
		throw new \RuntimeException('Preparation work is not supported for this declaration');
	}

	public function callable_owner(collected_source_callable $entry): source_callable_signature_work
	{
		if ($entry->preparation === null) {
			$entry->preparation = new source_callable_signature_work($entry);
		}
		return object_cast($entry->preparation, source_callable_signature_work::class);
	}

	public function relationship_owner(collected_member_owner $entry): type_relationship_work
	{
		if ($entry->relationship_work === null) {
			$entry->relationship_work = new type_relationship_work($entry);
		}
		return $entry->relationship_work;
	}

	public function contract_owner(collected_member_owner $entry): type_contract_work
	{
		if ($entry->contract_work === null) { $entry->contract_work = new type_contract_work($entry); }
		return $entry->contract_work;
	}

	public function lifecycle_owner(collected_member_owner $entry): type_lifecycle_work
	{
		if ($entry->lifecycle_work === null) { $entry->lifecycle_work = new type_lifecycle_work($entry); }
		return $entry->lifecycle_work;
	}

	public function require_lifecycle(collected_member_owner $entry, preparation_context $context): prepared_type_lifecycle
	{
		$target = $this->lifecycle_owner($entry);
		$this->require_contract($context->owner, $target);
		return $target->require_facts();
	}

	public function require_type_contract(collected_member_owner $entry, preparation_context $context): prepared_type_contract
	{
		$target = $this->contract_owner($entry);
		$this->require_contract($context->owner, $target);
		return $target->require_facts();
	}

	/** Demand only ancestry; record layout and callable signatures remain independent work. */
	public function require_relationships(collected_member_owner $entry, preparation_context $context): prepared_type_relationships
	{
		$target = $this->relationship_owner($entry);
		$this->require_contract($context->owner, $target);
		return $target->require_facts();
	}

	public function record_owner(collected_record $entry): contract_work
	{
		if ($entry->preparation === null) {
			$entry->preparation = new record_definition_work($entry);
		}
		return $entry->preparation;
	}

	/** Identity lookup alone does not require a completed declaration; callers choose when it does. */
	public function depend(preparation_owner $owner, collected_definition $entry): contract_work
	{
		$target = $this->declaration_owner($entry);
		$this->depend_contract($owner, $target);
		return $target;
	}

	/** Dependency registration does not require a name or lookup publication. */
	public function depend_contract(preparation_owner $owner, contract_work $target): void
	{
		$owner->dependencies[$target] = $target->version;
		$target->dependents[$owner] = true;
	}

	/** Register the dependency before demanding completed facts; failed facts are never consumed. */
	public function require_declaration(preparation_owner $owner, collected_definition $entry): contract_work
	{
		$target = $this->declaration_owner($entry);
		$this->require_contract($owner, $target);
		return $target;
	}

	public function require_contract(preparation_owner $owner, contract_work $target): void
	{
		$this->depend_contract($owner, $target);
		$this->contract($target);
		$owner->dependencies[$target] = $target->version;
	}

	/** By-value record layout requires completion and therefore detects declaration cycles. */
	public function require_record(canonical_type_use $type, preparation_context $context): void
	{
		$entry = Type_Queries::source_record($type);
		if ($entry === null) {
			return;
		}
		$record /** collected_record */ = $entry;
		$this->require_declaration($context->owner, $record);
	}

	/** Preserve dependency ownership while replacing only the selected owner's registrations. */
	private function detach_dependencies(preparation_owner $owner): void
	{
		foreach ($owner->dependencies as $target /** @object-key */) {
			unset($target->dependents[$owner]);
		}
		foreach ($owner->lookups as $lookup /** @object-key */) {
			unset($lookup->dependents[$owner]);
			if (q_count($lookup->dependents) === 0) {
				$lookup->lookup_scope->remove_preparation_lookup($lookup);
			}
		}
		$owner->dependencies = new \SplObjectStorage /** hash<int, shared<contract_work>> */();
		$owner->lookups = new \SplObjectStorage /** hash<bool, shared<preparation_lookup>> */();
	}

	/** Rebuild selected signatures/layout facts; only effective changes propagate to consumers. */
	public function contract(contract_work $owner): void
	{
		Preparation_Status::require_active($owner);
		if (Preparation_Status::source_changed($owner)) {
			$this->schedule($owner);
		}
		if ($owner->state === preparation_state::processing) {
			if ($owner instanceof type_relationship_work) {
				throw new \RuntimeException('Cyclic type relationship');
			}
			throw new \RuntimeException('Cyclic by-value declaration dependency');
		}
		unset($this->contracts[$owner]);
		if (isset($this->attempt_failed[$owner])) {
			throw new \RuntimeException($owner->failure_message);
		}
		if ($owner->change_status === change_state::unchanged)
		{
			// A new edge can close a cycle through previously ready declarations.
			$owner->state = preparation_state::processing;
			try {
				foreach ($owner->dependencies as $dependency /** @object-key */) {
					$this->contract($dependency);
				}
			}
			catch (\RuntimeException $error) {
				$this->failure($owner, $error->getMessage());
				throw $error;
			}
			$owner->state = preparation_state::ready;
			if ($owner->change_status === change_state::unchanged) {
				return;
			}
		}
		$recovering = $owner->failed;
		$old_dependencies /** hash<int, shared<contract_work>> */ = $owner->dependencies;
		$this->detach_dependencies($owner);
		$owner->state = preparation_state::processing;
		$context = $this->context($owner);
		try
		{
			$changed = $this->rebuild_contract($owner, $context, $old_dependencies);
			$this->settle($owner);
			$this->settle_members($owner);
			if ($changed || $recovering) {
				$owner->version++;
				$owner->source->preparation_changes[$owner] = true;
				foreach ($owner->dependents as $dependent /** @object-key */) {
					$this->schedule($dependent);
				}
			}
		}
		catch (\RuntimeException $error) {
			$this->failure($owner, $error->getMessage());
			throw $error;
		}
	}

	/** Declarations have settled; each selected body replaces facts and dependencies once. */
	private function body(body_work $owner): void
	{
		Preparation_Status::require_active($owner);
		$this->detach_dependencies($owner);
		$owner->state = preparation_state::processing;
		$context = $this->context($owner);
		try
		{
			$this->rebuild_body($owner, $context);
			$this->settle($owner);
			$owner->version++;
			$owner->source->preparation_changes[$owner] = true;
		}
		catch (\RuntimeException $error) {
			if ($this->is_materialization_yield($error)) {
				$owner->state = preparation_state::pending;
				throw $error;
			}
			$this->failure($owner, $error->getMessage());
		}
	}

	/** Dispatch by retained work role; records carry identities, not processing hooks. */
	private function rebuild_contract(contract_work $owner, preparation_context $context, \SplObjectStorage $previous /** hash<int, shared<contract_work>> */): bool
	{
		if ($owner instanceof anonymous_signature_work) {
			$anonymous = object_cast($owner, anonymous_signature_work::class);
			$current = Declaration_Preparation::prepare_anonymous($anonymous->anonymous_identity()->syntax(), $context, $anonymous->expected_type);
			$changed = !Preparation_Changes::same_signature($anonymous->facts, $current);
			if ($changed) { $anonymous->facts = $current; }
			else { Preparation_Changes::restore_parameters($anonymous->anonymous_identity()->syntax(), $anonymous->require_facts()); }
			return $changed;
		}
		if ($owner instanceof type_lifecycle_work) {
			$lifecycle = object_cast($owner, type_lifecycle_work::class);
			$current = Type_Lifecycle_Preparation::prepare($lifecycle->member_definition(), $context);
			$changed = !Type_Lifecycle_Preparation::same($lifecycle->facts, $current);
			if ($changed) { $lifecycle->facts = $current; }
			$lifecycle->source_revision = (int)$lifecycle->member_definition()->revision;
			return $changed;
		}
		if ($owner instanceof type_contract_work) {
			$contracts = object_cast($owner, type_contract_work::class);
			$current = Type_Contract_Preparation::prepare($contracts->member_definition(), $context);
			$changed = !Type_Contract_Preparation::same($contracts->facts, $current);
			if ($changed) { $contracts->facts = $current; }
			$contracts->source_revision = (int)$contracts->member_definition()->revision;
			return $changed;
		}
		if ($owner instanceof type_relationship_work) {
			$relationships = object_cast($owner, type_relationship_work::class);
			$current = Relationship_Preparation::prepare($relationships->member_definition(), $context);
			$changed = !Relationship_Preparation::same($relationships->facts, $current);
			if ($changed) { $relationships->facts = $current; }
			$relationships->source_revision = (int)$relationships->member_definition()->revision;
			return $changed;
		}
		if ($owner instanceof source_callable_signature_work) {
			return $this->prepare_callable_signature(object_cast($owner, source_callable_signature_work::class), $context);
		}
		if ($owner instanceof record_definition_work) {
			return $this->prepare_record_definition(object_cast($owner, record_definition_work::class), $context, $previous);
		}
		if ($owner instanceof constant_definition_work) {
			$syntax = object_cast($owner, constant_definition_work::class)->constant_definition()->syntax();
			$old = $syntax->preparation();
			Constant_Preparation::declaration($syntax, $context);
			$current = $syntax->require_preparation();
			if ($old === null) { return true; }
			return ($old->visibility !== $current->visibility) || !$old->value->type->matches($current->value->type)
				|| ($old->value->value_key() !== $current->value->value_key());
		}
		if ($owner instanceof enum_definition_work) {
			$enum_syntax = object_cast($owner, enum_definition_work::class)->enum_definition()->enum_syntax();
			$old_enum = $enum_syntax->preparation();
			$current_enum = Enum_Preparation::declaration($enum_syntax, $context);
			$changed = !Enum_Preparation::same($old_enum, $current_enum);
			if ($changed) {
				$enum_syntax->set_preparation($current_enum);
			}
			return $changed;
		}
		throw new \LogicException('Unsupported declaration work role');
	}

	private function settle_members(contract_work $owner): void
	{
		if ($owner instanceof anonymous_signature_work) {
			$parameters /** Storage<parameter_node> */ = object_cast($owner, anonymous_signature_work::class)->anonymous_identity()->syntax()->signature_syntax->parameters;
			foreach ($parameters as $parameter) { $parameter->occurrence()->change_status = change_state::unchanged; }
			return;
		}
		if ($owner instanceof type_semantic_work) { return; }
		if ($owner instanceof source_callable_signature_work) {
			$this->settle_parameters(object_cast($owner, source_callable_signature_work::class)->callable_definition());
		}
		elseif ($owner instanceof record_definition_work) {
			$this->settle_fields(object_cast($owner, record_definition_work::class)->record_definition());
		}
		elseif (($owner instanceof constant_definition_work) || ($owner instanceof enum_definition_work)) {
			object_cast($owner, declaration_work::class)->required_declaration()->change_status = change_state::unchanged;
		}
		else { throw new \LogicException('Unsupported declaration work role'); }
	}

	private function rebuild_body(body_work $owner, preparation_context $context): void
	{
		if ($owner instanceof anonymous_body_work) {
			$anonymous = object_cast($owner, anonymous_body_work::class);
			$signature /** anonymous_signature_work */ = $anonymous->anonymous_identity()->signature_work;
			$this->require_contract($owner, $signature);
			Preparation_Cleanup::tree($owner->executable_body());
			Body_Preparation::bind_parameters($signature->require_facts(), $context);
			$captures /** Storage<prepared_capture> */ = $anonymous->captures;
			$guarantees /** Storage<storage_lifetime> */ = $context->borrowed_lifetimes;
			foreach ($captures as $capture) {
				$name = weakref_get($capture->declaration)->name;
				$receiver = $capture->receiver();
				if ($receiver !== null) {
					$name = 'this';
					$context->receiver = $receiver;
				}
				if (q_count($context->locals->local_named($name)) !== 0) {
					throw new \RuntimeException('Capture collides with a parameter: ' . $name);
				}
				if ($capture->borrow !== null) { $guarantees->append($capture->borrow->lifetime); }
				else { Capability_Preparation::require_copy($capture->type, $context); }
				foreach (Capture_Preparation::borrowed_lifetimes($capture->type, $context) as $origin) { $guarantees->append($origin); }
				$context->locals->add($name, $capture);
			}
			Body_Preparation::prepare_callable_statements($owner->executable_body(), $signature->require_facts(), $context, 0, '<closure>');
			$owner->executable_body()->syntax_changed = false;
			return;
		}
		if ($owner instanceof source_callable_body_work) {
			$this->prepare_callable_body(object_cast($owner, source_callable_body_work::class), $context);
		}
		elseif ($owner instanceof file_body_work) {
			$this->prepare_file_body(object_cast($owner, file_body_work::class), $context);
		}
		else { throw new \LogicException('Unsupported body work role'); }
	}

	/** Success alone clears persistent selection/error state; prior increments need no reset. */
	private function settle(preparation_owner $owner): void
	{
		$owner->state = preparation_state::ready;
		$owner->change_status = change_state::unchanged;
		$owner->failed = false;
		$owner->failure_message = '';
	}

	/** Preserve parameter fact identities when the effective signature is unchanged. */
	private function prepare_callable_signature(source_callable_signature_work $owner, preparation_context $context): bool
	{
		$syntax = $owner->callable_definition()->callable_syntax();
		$old = $syntax->preparation();
		Declaration_Preparation::prepare_callable($syntax, $context);
		$changed = !Preparation_Changes::same_signature($old, $syntax->require_preparation());
		if (!$changed) {
			$previous /** prepared_source_callable */ = $old;
			$syntax->set_preparation($previous);
			Preparation_Changes::restore_parameters($syntax, $previous);
		}
		return $changed;
	}

	/** Nested layout versions matter even if this record retains the same field type identities. */
	private function prepare_record_definition(record_definition_work $owner, preparation_context $context, \SplObjectStorage $previous /** hash<int, shared<contract_work>> */): bool
	{
		$this->require_relationships($owner->record_definition(), $context);
		$syntax = $owner->record_definition()->syntax();
		$old = $syntax->preparation();
		Declaration_Preparation::prepare_record($syntax, $context);
		$changed = !Preparation_Changes::same_record($old, $syntax->require_preparation());
		foreach ($previous as $target /** @object-key */) {
			if ($previous[$target] !== $target->version) {
				$changed = true;
			}
		}
		if (!$changed) {
			$facts /** prepared_record */ = $old;
			$syntax->set_preparation($facts);
			Preparation_Changes::restore_fields($syntax, $facts);
		}
		return $changed;
	}

	/** Demand the signature before replacing selected body facts. */
	private function prepare_callable_body(source_callable_body_work $owner, preparation_context $context): void
	{
		$entry = $owner->callable_definition();
		$syntax = $entry->callable_syntax();
		$this->require_declaration($owner, $entry);
		Preparation_Cleanup::tree($syntax->require_body());
		Body_Preparation::prepare_body($syntax, $context);
		$syntax->require_body()->syntax_changed = false;
	}

	private function prepare_file_body(file_body_work $owner, preparation_context $context): void
	{
		$body = $owner->executable_body();
		Preparation_Cleanup::tree($body);
		Body_Preparation::prepare_statements($body, $context, 0);
		$body->syntax_changed = false;
	}

	/** Members settle with their enclosing declaration, never during parsing. */
	private function settle_parameters(collected_source_callable $entry): void
	{
		$entry->change_status = change_state::unchanged;
		$parameters /** Storage<parameter_node> */ = $entry->callable_syntax()->signature_syntax->parameters;
		foreach ($parameters as $parameter) {
			$parameter->occurrence()->change_status = change_state::unchanged;
		}
	}

	/** Fields share their record's completion boundary. */
	private function settle_fields(collected_record $entry): void
	{
		$entry->change_status = change_state::unchanged;
		$fields /** Storage<property_node> */ = Member_Inventory::properties($entry);
		foreach ($fields as $field) {
			$field->occurrence()->change_status = change_state::unchanged;
		}
	}

	/** Propagate through reverse links once; unchanged signatures do not hide later recovery. */
	private function failure(preparation_owner $owner, string $message): void
	{
		$this->attempt_failed[$owner] = true;
		$owner->state = preparation_state::pending;
		$pending /** Storage<preparation_owner> */ = new Storage();
		$seen /** hash<bool, shared<preparation_owner>> */ = new \SplObjectStorage /** hash<bool, shared<preparation_owner>> */();
		$pending->append($owner);
		for ($index = 0; $index < q_count($pending); $index++)
		{
			$current = $pending[$index];
			if (isset($seen[$current])) {
				continue;
			}
			$seen[$current] = true;
			$current->failed = true;
			$current->failure_message = $message;
			$this->schedule($current);
			foreach ($current->dependents as $dependent /** @object-key */) {
				$pending->append($dependent);
			}
		}
	}

	/** Deduplicate originating messages and withhold completion if any selected owner remains dirty. */
	private function require_success(): void
	{
		$messages /** hash<bool> */ = [];
		$error = '';
		foreach ($this->owners as $owner /** @object-key */)
		{
			if ($owner->change_status !== change_state::unchanged)
			{
				$message = $owner->failure_message;
				if ($message === '') {
					$message = 'Preparation still has changed work';
				}
				if (!isset($messages[$message])) {
					$messages[$message] = true;
					$error .= $message . "\n";
				}
			}
		}
		if ($error !== '') {
			throw new \RuntimeException($error);
		}
	}

	/** Context is transient and always uses the selected owner's source token generation. */
	private function context(preparation_owner $owner): preparation_context
	{
		$context = new preparation_context();
		$context->worker = $this;
		$context->owner = $owner;
		$context->collection = $owner->source;
		$definition = $owner->declaration();
		if ($owner instanceof anonymous_signature_work) {
			$definition = Callable_Inventory::lexical_method(object_cast($owner, anonymous_signature_work::class)->anonymous_identity());
		}
		elseif ($owner instanceof anonymous_body_work) {
			$definition = Callable_Inventory::lexical_method(object_cast($owner, anonymous_body_work::class)->anonymous_identity());
		}
		if ($definition instanceof collected_method) {
			$method = object_cast($definition, collected_method::class);
			$context->member_owner = $method->declaring_type();
			$instance = weakref_get($method->instance_context);
			if ($instance !== null) {
				$context->called_type = $instance->called_type();
			}
		}
		$context->locals = new local_environment(null);
		$context->invocation_lifetime = $context->locals->region;
		$context->borrowed_lifetimes = new Storage /** Storage<storage_lifetime> */();
		$context->integer = Language_Types::integer($this->language);
		$context->boolean = Language_Types::boolean($this->language);
		$context->floating = Language_Types::floating($this->language);
		$context->string_type = Language_Types::string_type($this->language);
		return $context;
	}

	/** Observe every visited lookup pool, including absent names, before selecting a target. */
	public function observe(scope $scope, string $name, preparation_lookup_kind $kind, preparation_owner $owner): void
	{
		$existing /** nullable<preparation_lookup> */ = null;
		foreach ($scope->preparation_lookups_named($name) as $candidate) {
			if ($candidate->kind === $kind) {
				$existing = $candidate;
				break;
			}
		}
		if ($existing === null) {
			$existing = new preparation_lookup($scope, $name, $kind);
			$existing->candidates = $this->candidates($existing);
			$scope->add_preparation_lookup($existing);
		}
		$lookup /** preparation_lookup */ = $existing;
		$owner->lookups[$lookup] = true;
		$lookup->dependents[$owner] = true;
	}

	/** Project typed pools into one identity snapshot; value vectors are not covariant. */
	private function candidates(preparation_lookup $lookup): array /** vector<preparation_lookup_candidate_i> */
	{
		$result /** vector<preparation_lookup_candidate_i> */ = [];
		if ($lookup->kind === preparation_lookup_kind::property_name) {
			foreach ($lookup->lookup_scope->variables_named($lookup->name) as $property) {
				if ($property instanceof collected_field) { $result[] = object_cast($property, collected_field::class); }
			}
		}
		elseif ($lookup->kind === preparation_lookup_kind::method_name) {
			foreach ($lookup->lookup_scope->methods_named($lookup->name) as $method) {
				// Use the callable view, as function-name lookup does; the native
				// declaration also reaches this marker through collected_definition.
				$callable /** callable_definition_i */ = $method;
				$result[] = $callable;
			}
		}
		elseif ($lookup->kind === preparation_lookup_kind::import_name) {
			foreach ($lookup->lookup_scope->imports_named($lookup->name) as $binding) { $result[] = $binding; }
		}
		elseif ($lookup->kind === preparation_lookup_kind::type) {
			foreach ($lookup->lookup_scope->source_types_named($lookup->name) as $record) {
				$result[] = $record;
			}
		}
		elseif ($lookup->kind === preparation_lookup_kind::function_name) {
			foreach ($lookup->lookup_scope->callables_named($lookup->name) as $function) {
				$result[] = $function;
			}
		}
		else {
			foreach ($lookup->lookup_scope->constants_named($lookup->name) as $constant) {
				$result[] = $constant;
			}
		}
		return $result;
	}

	/** Membership changes can invalidate successful, ambiguous or previously missing lookups. */
	private function changed_lookups(): void
	{
		$seen /** hash<bool, shared<preparation_lookup>> */ = new \SplObjectStorage /** hash<bool, shared<preparation_lookup>> */();
		foreach ($this->owners as $owner /** @object-key */)
		{
			foreach ($owner->lookups as $lookup /** @object-key */)
			{
				if (isset($seen[$lookup])) {
					continue;
				}
				$seen[$lookup] = true;
				$candidates = $this->candidates($lookup);
				$changed = q_count($candidates) !== q_count($lookup->candidates);
				if (!$changed) {
					foreach ($candidates as $index => $entry) {
						if ($entry !== $lookup->candidates[$index]) {
							$changed = true;
						}
					}
				}
				$lookup->candidates = $candidates;
				if ($changed) {
					foreach ($lookup->dependents as $dependent /** @object-key */) {
						$this->schedule($dependent);
					}
				}
			}
		}
	}

	/** Clear semantic attachments only; collected membership and syntax belong to the frontend. */
	private function detach_retired_work(collected_file $source): void
	{
		$anonymous /** Storage<anonymous_callable_identity> */ = $source->anonymous_callables;
		foreach ($anonymous as $identity) {
			if (!$source->deleted && !$identity->deleted) { continue; }
			$identity->signature_work = null;
			Preparation_Cleanup::tree($identity->syntax());
			if ($identity->syntax()->has_parsed_body()) { $identity->syntax()->require_body()->detach_work(); }
		}
		$entries /** Storage<collected_name> */ = $source->entries;
		foreach ($entries as $entry) {
			if ($entry instanceof collected_source_callable) {
				$retired_bodies /** Storage<function_body_node> */ = object_cast($entry, collected_source_callable::class)->callable_syntax()->retired_bodies;
				foreach ($retired_bodies as $retired_body) {
					Preparation_Cleanup::tree($retired_body);
					$retired_body->detach_work();
				}
			}
			if (!$source->deleted && ($entry->change_status !== change_state::deleted)) {
				continue;
			}
			if ($entry->preparation_work_owner() !== null) {
				object_cast($entry, collected_definition::class)->preparation = null;
			}
			if ($entry instanceof collected_member_owner) {
				object_cast($entry, collected_member_owner::class)->relationship_work = null;
				object_cast($entry, collected_member_owner::class)->contract_work = null;
				object_cast($entry, collected_member_owner::class)->lifecycle_work = null;
			}
			if ($entry instanceof collected_source_callable) {
				$function = object_cast($entry, collected_source_callable::class)->callable_syntax();
				if ($function->has_parsed_body()) {
					$function->require_body()->detach_work();
				}
			}
		}
		$bodies /** Storage<namespace_body_node> */ = $source->root->retired_namespace_bodies;
		if (!$bodies->is_empty()) {
			if ($source->prepared !== null) {
				$source->prepared->entry_body = null;
			}
		}
		foreach ($bodies as $namespace_body) {
			$namespace_body->body->detach_work();
		}
		if ($source->deleted) {
			$active_bodies /** Storage<namespace_body_node> */ = $source->root->namespace_bodies;
			foreach ($active_bodies as $namespace_body) {
				$namespace_body->body->detach_work();
			}
			$source->prepared = null;
		}
	}

	/** Only contracts can be dependency targets; remove incoming edges before common cleanup. */
	private function retire_contract(contract_work $owner): void
	{
		foreach ($owner->dependents as $dependent /** @object-key */) {
			unset($dependent->dependencies[$owner]);
		}
		unset($this->contracts[$owner]);
	}

	private function retire_callable_body(source_callable_body_work $owner): void
	{
		unset($this->callable_bodies[$owner]);
	}

	private function retire_file_body(file_body_work $owner): void
	{
		unset($this->file_bodies[$owner]);
	}

	/** Explicitly sever both directions; strong-reference storage must not keep retired owners alive. */
	private function retire(preparation_owner $owner): void
	{
		if ($owner instanceof anonymous_signature_work) {
			$anonymous = object_cast($owner, anonymous_signature_work::class);
			$anonymous->facts = null;
			$anonymous->expected_type = null;
		}
		if ($owner instanceof type_lifecycle_work) { object_cast($owner, type_lifecycle_work::class)->facts = null; }
		if ($owner instanceof type_contract_work) { object_cast($owner, type_contract_work::class)->facts = null; }
		if ($owner instanceof type_relationship_work) {
			object_cast($owner, type_relationship_work::class)->facts = null;
		}
		$owner->change_status = change_state::deleted;
		$owner->source->preparation_changes[$owner] = true;
		if ($owner instanceof contract_work) {
			$this->retire_contract(object_cast($owner, contract_work::class));
		}
		elseif ($owner instanceof source_callable_body_work) {
			$this->retire_callable_body(object_cast($owner, source_callable_body_work::class));
		}
		elseif ($owner instanceof file_body_work) {
			$this->retire_file_body(object_cast($owner, file_body_work::class));
		}
		elseif ($owner instanceof anonymous_body_work) {
			$anonymous_body = object_cast($owner, anonymous_body_work::class);
			unset($this->callable_bodies[$anonymous_body]);
			$anonymous_body->captures = new Storage /** Storage<prepared_capture> */();
			Preparation_Cleanup::tree($anonymous_body->executable_body());
		}
		else { throw new \LogicException('Unsupported preparation work role'); }
		$this->detach_dependencies($owner);
		$owner->dependents = new \SplObjectStorage /** hash<bool, shared<preparation_owner>> */();
		unset($this->owners[$owner]);
		unset($this->attempt_failed[$owner]);
	}
}

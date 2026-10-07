<?php

/* One incremental preparation path: settle declarations, then process selected bodies once. */
namespace scpp\compiler;

final class Preparation_Worker
{
	private scope $language;
	/** A failed item is attempted at most once by this invocation, but stays changed for the next. */
	private \SplObjectStorage $attempt_failed /** hash<bool, shared<preparation_owner>> */;
	private \SplObjectStorage $declarations /** hash<bool, shared<declaration_work>> */;
	private \SplObjectStorage $function_bodies /** hash<bool, shared<function_body_work>> */;
	private \SplObjectStorage $file_bodies /** hash<bool, shared<file_body_work>> */;
	private \SplObjectStorage $owners /** hash<bool, shared<preparation_owner>> */;

	/** Work lists are invocation-local; retained dependency records never reference this worker. */
	public function __construct(scope $language)
	{
		$this->language = $language;
		$this->attempt_failed = new \SplObjectStorage /** hash<bool, shared<preparation_owner>> */();
		$this->declarations = new \SplObjectStorage /** hash<bool, shared<declaration_work>> */();
		$this->function_bodies = new \SplObjectStorage /** hash<bool, shared<function_body_work>> */();
		$this->file_bodies = new \SplObjectStorage /** hash<bool, shared<file_body_work>> */();
		$this->owners = new \SplObjectStorage /** hash<bool, shared<preparation_owner>> */();
	}

	/** After coordinator-ordered retirement, fresh/update runs share selection and execution. */
	public function prepare(Storage $sources /** Storage<collected_file> */): Storage /** Storage<prepared_file> */
	{
		foreach ($sources as $source) {
			if (!$source->deleted && !$source->parse_complete) {
				throw new \RuntimeException('Preparation requires a successful parsing/collection join');
			}
		}
		foreach ($sources as $source) {
			if (!$source->deleted) {
				if ($source->prepared !== null) {
					$source->prepared->entry_body = null;
				}
				$this->select($source);
			}
		}
		$this->changed_lookups();
		Import_Preparation::validate($sources);

		// Declaration processing may schedule more declarations; identity sets coalesce notifications.
		while (q_count($this->declarations) !== 0)
		{
			foreach ($this->declarations as $owner /** @object-key */)
			{
				try {
					$this->declaration($owner);
				}
				catch (\RuntimeException $error) {
					// Failure is retained on owners; independent declarations may still settle.
				}
				break;
			}
		}
		foreach ($this->function_bodies as $owner /** @object-key */) {
			$this->body($owner);
		}
		foreach ($this->file_bodies as $owner /** @object-key */) {
			$this->body($owner);
		}

		$this->require_success();
		// Only settled call facts participate; failure uses ordinary retained retry/publication guards.
		$cyclic = (new Reference_Cycles())->find($sources);
		foreach ($cyclic as $definition) {
			$body_owner /** body_work */ = $definition->syntax()->require_body()->work();
			$this->failure($body_owner, 'S2S recursive reference-return cycles are deferred: ' . $definition->name);
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
			$prepared->entry_body = Program_Preparation::source_body($source);
			$result->append($prepared);
		}
		foreach ($result as $prepared) {
			$bodies /** Storage<namespace_body_node> */ = $prepared->source->root->namespace_bodies;
			foreach ($bodies as $body) { $body->imports_changed = false; }
		}
		return $result;
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
		}
	}

	/** Mark the complete batch before notification, including bodies belonging to deleted functions. */
	private function retiring_owners(Storage $sources /** Storage<collected_file> */, bool $all): \SplObjectStorage /** hash<bool, shared<preparation_owner>> */
	{
		$result /** hash<bool, shared<preparation_owner>> */ = new \SplObjectStorage /** hash<bool, shared<preparation_owner>> */();
		foreach ($sources as $source)
		{
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
				if ((!$all) && (!$source->deleted) && ($entry->change_status !== change_state::deleted)) {
					continue;
				}
				if ($entry->preparation_work_owner() !== null) {
					$result[$entry->preparation_work_owner()] = true;
				}
				if ($entry instanceof collected_function) {
					$function = object_cast($entry, collected_function::class)->syntax();
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
			if ($node instanceof function_node) {
				$this->select_function(object_cast($node, function_node::class));
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
	public function select_function(function_node $node): void
	{
		$entry = object_cast($node->occurrence(), collected_function::class);
		$this->select_owner($this->function_owner($entry));
		if ($node->require_body()->work() === null) {
			$node->require_body()->attach_work(new function_body_work($entry));
		}
		$body /** body_work */ = $node->require_body()->work();
		$this->select_owner($body);
	}

	public function select_record(type_declaration_node $node): void
	{
		$entry = object_cast($node->occurrence(), collected_struct::class);
		$this->select_owner($this->record_owner($entry));
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
		if ($owner instanceof declaration_work) {
			$this->enqueue_declaration(object_cast($owner, declaration_work::class));
		}
		elseif ($owner instanceof function_body_work) {
			$this->enqueue_function_body(object_cast($owner, function_body_work::class));
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
	private function enqueue_declaration(declaration_work $owner): void
	{
		$entry = $owner->required_declaration();
		if ($entry->change_status !== change_state::added) {
			$entry->change_status = change_state::changed;
		}
		if ($this->queue_ready($owner)) {
			$this->declarations[$owner] = true;
		}
	}

	private function enqueue_function_body(function_body_work $owner): void
	{
		if ($this->queue_ready($owner)) {
			$this->function_bodies[$owner] = true;
		}
	}

	private function enqueue_file_body(file_body_work $owner): void
	{
		if ($this->queue_ready($owner)) {
			$this->file_bodies[$owner] = true;
		}
	}

	public function declaration_owner(collected_definition $entry): declaration_work
	{
		if ($entry instanceof collected_function) {
			return $this->function_owner(object_cast($entry, collected_function::class));
		}
		if ($entry instanceof collected_struct) {
			return $this->record_owner(object_cast($entry, collected_struct::class));
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

	public function function_owner(collected_function $entry): declaration_work
	{
		if ($entry->preparation === null) {
			$entry->preparation = new function_signature_work($entry);
		}
		return $entry->preparation;
	}

	public function record_owner(collected_struct $entry): declaration_work
	{
		if ($entry->preparation === null) {
			$entry->preparation = new record_definition_work($entry);
		}
		return $entry->preparation;
	}

	/** Identity lookup alone does not require a completed declaration; callers choose when it does. */
	public function depend(preparation_owner $owner, collected_definition $entry): declaration_work
	{
		$target = $this->declaration_owner($entry);
		$owner->dependencies[$target] = $target->version;
		$target->dependents[$owner] = true;
		return $target;
	}

	/** Register the dependency before demanding completed facts; failed facts are never consumed. */
	public function require_declaration(preparation_owner $owner, collected_definition $entry): declaration_work
	{
		$target = $this->depend($owner, $entry);
		$this->declaration($target);
		$owner->dependencies[$target] = $target->version;
		return $target;
	}

	/** By-value record layout requires completion and therefore detects declaration cycles. */
	public function require_record(canonical_type_use $type, preparation_context $context): void
	{
		$entry = Type_Queries::source_record($type);
		if ($entry === null) {
			return;
		}
		$record /** collected_struct */ = $entry;
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
		$owner->dependencies = new \SplObjectStorage /** hash<int, shared<declaration_work>> */();
		$owner->lookups = new \SplObjectStorage /** hash<bool, shared<preparation_lookup>> */();
	}

	/** Rebuild selected signatures/layout facts; only effective changes propagate to consumers. */
	public function declaration(declaration_work $owner): void
	{
		Preparation_Status::require_active($owner);
		if (Preparation_Status::source_changed($owner)) {
			$this->schedule($owner);
		}
		if ($owner->state === preparation_state::processing) {
			throw new \RuntimeException('Cyclic by-value declaration dependency');
		}
		unset($this->declarations[$owner]);
		if (isset($this->attempt_failed[$owner])) {
			throw new \RuntimeException($owner->failure_message);
		}
		if ($owner->change_status === change_state::unchanged)
		{
			// A new edge can close a cycle through previously ready declarations.
			$owner->state = preparation_state::processing;
			try {
				foreach ($owner->dependencies as $dependency /** @object-key */) {
					$this->declaration($dependency);
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
		$old_dependencies /** hash<int, shared<declaration_work>> */ = $owner->dependencies;
		$this->detach_dependencies($owner);
		$owner->state = preparation_state::processing;
		$context = $this->context($owner);
		try
		{
			$changed = $this->rebuild_declaration($owner, $context, $old_dependencies);
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
			$this->failure($owner, $error->getMessage());
		}
	}

	/** Dispatch by retained work role; records carry identities, not processing hooks. */
	private function rebuild_declaration(declaration_work $owner, preparation_context $context, \SplObjectStorage $previous /** hash<int, shared<declaration_work>> */): bool
	{
		if ($owner instanceof function_signature_work) {
			return $this->prepare_function_signature(object_cast($owner, function_signature_work::class), $context);
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
			return !$old->type->matches($current->type) || ($old->value_key() !== $current->value_key());
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

	private function settle_members(declaration_work $owner): void
	{
		if ($owner instanceof function_signature_work) {
			$this->settle_parameters(object_cast($owner, function_signature_work::class)->function_definition());
		}
		elseif ($owner instanceof record_definition_work) {
			$this->settle_fields(object_cast($owner, record_definition_work::class)->record_definition());
		}
		elseif (($owner instanceof constant_definition_work) || ($owner instanceof enum_definition_work)) {
			$owner->required_declaration()->change_status = change_state::unchanged;
		}
		else { throw new \LogicException('Unsupported declaration work role'); }
	}

	private function rebuild_body(body_work $owner, preparation_context $context): void
	{
		if ($owner instanceof function_body_work) {
			$this->prepare_function_body(object_cast($owner, function_body_work::class), $context);
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
	private function prepare_function_signature(function_signature_work $owner, preparation_context $context): bool
	{
		$syntax = $owner->function_definition()->syntax();
		$old = $syntax->preparation();
		Declaration_Preparation::prepare_function($syntax, $context);
		$changed = !Preparation_Changes::same_signature($old, $syntax->require_preparation());
		if (!$changed) {
			$previous /** prepared_function */ = $old;
			$syntax->set_preparation($previous);
			Preparation_Changes::restore_parameters($syntax, $previous);
		}
		return $changed;
	}

	/** Nested layout versions matter even if this record retains the same field type identities. */
	private function prepare_record_definition(record_definition_work $owner, preparation_context $context, \SplObjectStorage $previous /** hash<int, shared<declaration_work>> */): bool
	{
		$syntax = $owner->record_definition()->syntax();
		$old = $syntax->preparation();
		Declaration_Preparation::prepare_struct($syntax, $context);
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
	private function prepare_function_body(function_body_work $owner, preparation_context $context): void
	{
		$entry = $owner->function_definition();
		$syntax = $entry->syntax();
		$this->require_declaration($owner, $entry);
		Preparation_Cleanup::tree($syntax->require_body());
		Body_Preparation::prepare_body($syntax, $context);
		$syntax->require_body()->syntax_changed = false;
	}

	private function prepare_file_body(file_body_work $owner, preparation_context $context): void
	{
		$body = $owner->executable_body();
		Preparation_Cleanup::tree($body);
		Body_Preparation::prepare_statements($body, $context);
		$body->syntax_changed = false;
	}

	/** Members settle with their enclosing declaration, never during parsing. */
	private function settle_parameters(collected_function $entry): void
	{
		$entry->change_status = change_state::unchanged;
		$parameters /** Storage<parameter_node> */ = $entry->syntax()->parameters;
		foreach ($parameters as $parameter) {
			$parameter->occurrence()->change_status = change_state::unchanged;
		}
	}

	/** Fields share their record's completion boundary. */
	private function settle_fields(collected_struct $entry): void
	{
		$entry->change_status = change_state::unchanged;
		$fields /** Storage<property_node> */ = $entry->syntax()->properties;
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
		$context->locals = new local_environment(null);
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
		if ($lookup->kind === preparation_lookup_kind::import_name) {
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
		$entries /** Storage<collected_name> */ = $source->entries;
		foreach ($entries as $entry) {
			if (!$source->deleted && ($entry->change_status !== change_state::deleted)) {
				continue;
			}
			if ($entry->preparation_work_owner() !== null) {
				object_cast($entry, collected_definition::class)->preparation = null;
			}
			if ($entry instanceof collected_function) {
				$function = object_cast($entry, collected_function::class)->syntax();
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

	/** Only declarations can be dependency targets; remove incoming edges before common cleanup. */
	private function retire_declaration(declaration_work $owner): void
	{
		foreach ($owner->dependents as $dependent /** @object-key */) {
			unset($dependent->dependencies[$owner]);
		}
		unset($this->declarations[$owner]);
	}

	private function retire_function_body(function_body_work $owner): void
	{
		unset($this->function_bodies[$owner]);
	}

	private function retire_file_body(file_body_work $owner): void
	{
		unset($this->file_bodies[$owner]);
	}

	/** Explicitly sever both directions; strong-reference storage must not keep retired owners alive. */
	private function retire(preparation_owner $owner): void
	{
		$owner->change_status = change_state::deleted;
		$owner->source->preparation_changes[$owner] = true;
		if ($owner instanceof declaration_work) {
			$this->retire_declaration(object_cast($owner, declaration_work::class));
		}
		elseif ($owner instanceof function_body_work) {
			$this->retire_function_body(object_cast($owner, function_body_work::class));
		}
		elseif ($owner instanceof file_body_work) {
			$this->retire_file_body(object_cast($owner, file_body_work::class));
		}
		else { throw new \LogicException('Unsupported preparation work role'); }
		$this->detach_dependencies($owner);
		$owner->dependents = new \SplObjectStorage /** hash<bool, shared<preparation_owner>> */();
		unset($this->owners[$owner]);
		unset($this->attempt_failed[$owner]);
	}
}

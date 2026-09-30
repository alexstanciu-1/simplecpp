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

	/** Empty-model and update runs share selection, dependency propagation and execution. */
	public function prepare(Storage $sources /** Storage<collected_file> */): Storage /** Storage<prepared_file> */
	{
		foreach ($sources as $source) {
			if (!$source->deleted && !$source->parse_complete) {
				throw new \RuntimeException('Preparation requires a successful parsing/collection join');
			}
		}
		$this->remove_deleted_sources($sources);
		foreach ($sources as $source) {
			if (!$source->deleted) {
				$this->select($source);
			}
		}
		$this->changed_lookups();

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
			$result->append($prepared);
		}
		return $result;
	}

	/** Successful frontend joins retire deleted declarations before either backend resolves names. */
	public function remove_deleted_sources(Storage $sources /** Storage<collected_file> */): void
	{
		$retired = $this->retiring_owners($sources, false);
		$this->notify_retiring($retired);
		foreach ($retired as $owner /** @object-key */) {
			$this->retire($owner);
		}
		foreach ($sources as $source) {
			$this->remove_deleted($source);
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
			if ($all || $source->deleted) {
				if ($source->root->body->work() !== null) {
					$result[$source->root->body->work()] = true;
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
						if ($function->body->work() !== null) {
							$result[$function->body->work()] = true;
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

	/** Source change flags seed stable owners; concrete nodes select their typed work. */
	private function select(collected_file $source): void
	{
		if ($source->root->body->work() === null) {
			$source->root->body->attach_work(new file_body_work($source));
		}
		$file_body /** body_work */ = $source->root->body->work();
		$this->select_owner($file_body);
		$nodes /** Storage<declaration_node> */ = $source->root->declarations;
		foreach ($nodes as $node) {
			$node->select_preparation($this);
		}
	}

	/** Signature and implementation remain independent work identities. */
	public function select_function(function_node $node): void
	{
		$entry = object_cast($node->occurrence(), collected_function::class);
		$this->select_owner($this->function_owner($entry));
		if ($node->body->work() === null) {
			$node->body->attach_work(new function_body_work($entry));
		}
		$body /** body_work */ = $node->body->work();
		$this->select_owner($body);
	}

	public function select_record(struct_node $node): void
	{
		$this->select_owner($this->record_owner(object_cast($node->occurrence(), collected_struct::class)));
	}

	private function select_owner(preparation_owner $owner): void
	{
		$this->owners[$owner] = true;
		if ($owner->change_status !== change_state::unchanged) {
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
		$owner->enqueue($this);
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
	public function enqueue_declaration(declaration_work $owner): void
	{
		$entry = $owner->required_declaration();
		if ($entry->change_status !== change_state::added) {
			$entry->change_status = change_state::changed;
		}
		if ($this->queue_ready($owner)) {
			$this->declarations[$owner] = true;
		}
	}

	public function enqueue_function_body(function_body_work $owner): void
	{
		if ($this->queue_ready($owner)) {
			$this->function_bodies[$owner] = true;
		}
	}

	public function enqueue_file_body(file_body_work $owner): void
	{
		if ($this->queue_ready($owner)) {
			$this->file_bodies[$owner] = true;
		}
	}

	public function declaration_owner(collected_definition $entry): declaration_work
	{
		return $entry->preparation_work($this);
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
	public function require_record(type_definition $type, preparation_context $context): void
	{
		if ($type->kind !== type_kind::record) {
			return;
		}
		$entry /** collected_struct */ = $type->declaration;
		$this->require_declaration($context->owner, $entry);
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

	/** Reject stale work at process entry; recursive syntax dispatch needs no per-node flag. */
	public static function require_active(preparation_owner $owner): void
	{
		if ($owner->source->deleted || ($owner->change_status === change_state::deleted)) {
			throw new \LogicException('Deleted work cannot be prepared or generated');
		}
		$declaration = $owner->declaration();
		if ($declaration !== null) {
			if ($declaration->change_status === change_state::deleted) {
				throw new \LogicException('Deleted declaration cannot be prepared or generated');
			}
		}
		if (!$owner->source->parse_complete) {
			throw new \LogicException('Semantic work requires a completed parse');
		}
	}

	/** Rebuild selected signatures/layout facts; only effective changes propagate to consumers. */
	public function declaration(declaration_work $owner): void
	{
		self::require_active($owner);
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
			$changed = $owner->rebuild($this, $context, $old_dependencies);
			$this->settle($owner);
			$owner->settle_members($this);
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
		self::require_active($owner);
		$this->detach_dependencies($owner);
		$owner->state = preparation_state::processing;
		$context = $this->context($owner);
		try
		{
			$owner->rebuild($this, $context);
			$this->settle($owner);
			$owner->version++;
			$owner->source->preparation_changes[$owner] = true;
		}
		catch (\RuntimeException $error) {
			$this->failure($owner, $error->getMessage());
		}
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
	public function prepare_function_signature(function_signature_work $owner, preparation_context $context): bool
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
	public function prepare_record_definition(record_definition_work $owner, preparation_context $context, \SplObjectStorage $previous /** hash<int, shared<declaration_work>> */): bool
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
	public function prepare_function_body(function_body_work $owner, preparation_context $context): void
	{
		$entry = $owner->function_definition();
		$syntax = $entry->syntax();
		$this->require_declaration($owner, $entry);
		Preparation_Cleanup::tree($syntax->body);
		Body_Preparation::prepare_body($syntax, $context);
		$syntax->body->syntax_changed = false;
	}

	public function prepare_file_body(file_body_work $owner, preparation_context $context): void
	{
		$this->file_body($owner->source->root, $context);
		$owner->source->root->body->syntax_changed = false;
	}

	/** Members settle with their enclosing declaration, never during parsing. */
	public function settle_parameters(collected_function $entry): void
	{
		$entry->change_status = change_state::unchanged;
		$parameters /** Storage<parameter_node> */ = $entry->syntax()->parameters;
		foreach ($parameters as $parameter) {
			$parameter->occurrence()->change_status = change_state::unchanged;
		}
	}

	/** Fields share their record's completion boundary. */
	public function settle_fields(collected_struct $entry): void
	{
		$entry->change_status = change_state::unchanged;
		$fields /** Storage<field_node> */ = $entry->syntax()->fields;
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

	/** File declarations are prepared by their own list, never as executable entry statements. */
	private function file_body(file_node $root, preparation_context $context): void
	{
		Preparation_Cleanup::tree($root->body);
		Body_Preparation::prepare_statements($root->body->statements, $context);
	}

	/** Context is transient and always uses the selected owner's source token generation. */
	private function context(preparation_owner $owner): preparation_context
	{
		$context = new preparation_context();
		$context->worker = $this;
		$context->owner = $owner;
		$context->collection = $owner->source;
		$context->locals = new Key_Storage_List /** Key_Storage_List<prepared_storage> */();
		$context->integer = Language_Types::integer($this->language);
		$context->boolean = Language_Types::boolean($this->language);
		$context->floating = Language_Types::floating($this->language);
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
	private function candidates(preparation_lookup $lookup): array /** vector<collected_definition> */
	{
		$result /** vector<collected_definition> */ = [];
		if ($lookup->kind === preparation_lookup_kind::type) {
			foreach ($lookup->lookup_scope->source_types_named($lookup->name) as $record) {
				$result[] = $record;
			}
		}
		else {
			foreach ($lookup->lookup_scope->functions_named($lookup->name) as $function) {
				$result[] = $function;
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

	/** Relationships are already detached; remove collected rows and their scope/index memberships. */
	private function remove_deleted(collected_file $source): void
	{
		$entries /** Storage<collected_name> */ = $source->entries;
		$removed /** vector<int> */ = [];
		foreach ($entries as $entry)
		{
			if (!$source->deleted && ($entry->change_status !== change_state::deleted)) {
				continue;
			}
			if ($entry->preparation_work_owner() !== null) {
				object_cast($entry, collected_definition::class)->preparation = null;
			}
			if ($entry instanceof collected_function) {
				$function = object_cast($entry, collected_function::class)->syntax();
				if ($function->has_parsed_body()) {
					$function->body->detach_work();
				}
			}
			$entry_scope = object_cast(weakref_get($entry->scope), scope::class);
			$entry_scope->unregister($entry);
			$global = $entry_scope->published_scope();
			if ($entry->is_exported() && ($global !== null)) {
				$published /** scope */ = $global;
				$published->unregister($entry);
			}
			$removed[] = $entry->local_index;
		}
		foreach ($removed as $index) {
			$entries->remove($index);
		}
		$source->defined_elements = $this->present($entries, $source->defined_elements);
		$source->variable_references = $this->present($entries, $source->variable_references);
		$source->function_references = $this->present($entries, $source->function_references);
		$source->type_references = $this->present($entries, $source->type_references);
		$source->field_references = $this->present($entries, $source->field_references);
		$source->pending_bindings = $this->present($entries, $source->pending_bindings);
		if ($source->deleted)
		{
			if ($source->root->body->work() !== null) {
				$source->root->body->detach_work();
			}
			$source->prepared = null;
			$source->parse_complete = false;
			$source->root->declarations = new Storage /** Storage<declaration_node> */();
			$source->root->body->statements = new Storage /** Storage<statement_node> */();
		}
	}

	/** Compact occurrence work lists after their collected rows have been removed. */
	private function present(Storage $entries /** Storage<collected_name> */, array $indexes /** vector<int> */): array /** vector<int> */
	{
		$result /** vector<int> */ = [];
		foreach ($indexes as $index) {
			if (isset($entries[$index])) {
				$result[] = $index;
			}
		}
		return $result;
	}

	/** Only declarations can be dependency targets; remove incoming edges before common cleanup. */
	public function retire_declaration(declaration_work $owner): void
	{
		foreach ($owner->dependents as $dependent /** @object-key */) {
			unset($dependent->dependencies[$owner]);
		}
		unset($this->declarations[$owner]);
	}

	public function retire_function_body(function_body_work $owner): void
	{
		unset($this->function_bodies[$owner]);
	}

	public function retire_file_body(file_body_work $owner): void
	{
		unset($this->file_bodies[$owner]);
	}

	/** Explicitly sever both directions; strong-reference storage must not keep retired owners alive. */
	private function retire(preparation_owner $owner): void
	{
		$owner->change_status = change_state::deleted;
		$owner->source->preparation_changes[$owner] = true;
		$owner->retire_from($this);
		$this->detach_dependencies($owner);
		$owner->dependents = new \SplObjectStorage /** hash<bool, shared<preparation_owner>> */();
		unset($this->owners[$owner]);
		unset($this->attempt_failed[$owner]);
	}
}

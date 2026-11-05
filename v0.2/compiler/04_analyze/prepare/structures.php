<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Transient possible block exits; e.g. if (flag) break; yields fallthrough | breaks. Native uint8 flags. */
final class statement_completion
{
	public const int fallthrough /** uint8 */ = 1;
	public const int returns /** uint8 */ = 2;
	public const int breaks /** uint8 */ = 4;
	public const int continues /** uint8 */ = 8;
}

/**
 * Records whether an entry return is proven to fit native int or needs a runtime range check.
 *
 * Entry return:
 * ```text
 * return 8;
 * ```
 * A constant inside the target range selects proven; an unknown value requires checked conversion.
 */
enum exit_conversion: int /** uint8 */ {
	case proven = 1;
	case checked = 2;
}

/**
 * Signed target exit range, supplied by native target facts rather than the PHP host.
 *
 * Target facts:
 * ```text
 * new entry_target(31)
 * ```
 * This describes a signed native int with 31 value bits, regardless of the PHP host integer width.
 */
final readonly class entry_target
{
	/** Signed value bits, 15..63. */
	public int $digits /** uint8 */;
	public int $minimum /** int64 */;
	public int $maximum /** int64 */;

	/**
	 * Bound the target to integer widths representable by this compiler's int64 model.
	 *
	 * $digits: 15..63.
	 */
	public function __construct(int $digits /** uint8 */)
	{
		if (($digits < 15) || ($digits > 63)) {
			throw new \InvalidArgumentException('Unsupported native int width');
		}
		$this->digits = $digits;
		$this->maximum = $digits === 63 ? \PHP_INT_MAX : (1 << $digits) - 1;
		$this->minimum = -$this->maximum - 1;
	}

	public function contains(int $value /** int64 */): bool
	{
		return ($value >= $this->minimum) && ($value <= $this->maximum);
	}
}

/**
 * Completed entry context, without a second body or function list.
 * Example: return 8; selects this file's private main and the supplied native exit target.
 * The revision check prevents an old handle from observing a later preparation run.
 */
final readonly class prepared_entry
{
	/** PROPERTIES */
	/** Native shared-owning; retain source syntax for emission. */
	public ast_file_node $file;
	/** Native shared-owning; immutable target facts. */
	public entry_target $target;
	private int $revision /** uint32 */;

	/** CONSTRUCTOR */
	public function __construct(ast_file_node $file, entry_target $target)
	{
		$this->revision = $file->prepared_revision();
		$this->file = $file;
		$this->target = $target;
	}

	/** METHODS */
	public function require_prepared(): void
	{
		if ($this->revision !== $this->file->prepared_revision()) {
			throw new \LogicException('Stale entry preparation');
		}
	}

	public function body(): ast_function_body
	{
		$this->require_prepared();
		return ($this->file->main() ?? throw new \LogicException('Missing entry'))->body();
	}
}

/**
 * Completed compilation inventory and selected entry; facts remain exclusively on its AST nodes.
 * Example: main calls ping from support; files retains both roots, entry selects main.
 */
final readonly class prepared_program
{
	/** PROPERTIES */
	/**
	 * Retains the graph lifetime for module compilations; null for standalone AST preparation.
	 * Native shared-owning; retain module/source generation when present.
	 */
	public ?compilation_session $session;
	public string $entry_path;
	/** @var ast_file_node[] Native shared-owning source retention. */
	public array $files /** array<string,ast_file_node> */;
	/** Native owning; prepared entry handle. */
	public prepared_entry $entry;
	/** Native shared-owning; complete usage relations used to plan cross-unit declarations. */
	public Dependency_Watches $dependencies;
	/** @var int[] Completion identities, not semantic facts. */
	private array $revisions /** array<string,uint32> */;

	/** CONSTRUCTOR */
	/**
	 * Retain the prepared inputs and capture their completion revisions for later validation.
	 *
	 * @param ast_file_node[] $files
	 */
	public function __construct(string $entry_path, array $files /** array<string,ast_file_node> */, prepared_entry $entry,
		?compilation_session $session = null, ?Dependency_Watches $dependencies = null)
	{
		$this->session = $session;
		$this->dependencies = $dependencies ?? $session?->dependency_watches ?? new Dependency_Watches();
		$this->entry_path = $entry_path;
		$this->files = $files;
		$this->entry = $entry;
		$revisions = [];
		foreach ($files as $path => $file) {
			$revisions[$path] = $file->prepared_revision();
		}
		$this->revisions = $revisions;
	}

	/** METHODS */
	/** Reject emission through a handle whose source facts were reset or replaced. */
	public function require_prepared(): void
	{
		if (($this->session !== null) && ((!$this->session->is_current)
			|| ($this->session->files() !== $this->files) || ($this->session->preparation_work->pending_count() !== 0))) {
			throw new \LogicException('Stale module compilation');
		}
		foreach ($this->files as $path => $file) {
			if ($file->prepared_revision() !== $this->revisions[$path]) {
				throw new \LogicException('Stale program preparation');
			}
		}
		$this->entry->require_prepared();
	}
}

/**
 * Session-lineage preparation work grouped by source key and deduplicated by body identity; no AST copies or facts.
 * Example: two changed symbols enqueue their shared consumer once; failed preparation leaves it pending.
 */
final class preparation_work
{
	/**
	 * Native non-owning pending bodies grouped by session source key.
	 * @var \SplObjectStorage<ast_function_body,null>[]
	 */
	private array $bodies /** array<string,SplObjectStorage<ast_function_body,null>> */ = [];
	/** Number of attached live bodies; supports reuse reporting without scanning unchanged ASTs. */
	private int $total /** uint32 */ = 0;
	/** @var bool[] Source keys awaiting successful downstream publication; survives body completion and retries. */
	private array $changed_sources /** array<string,bool> */ = [];

	/** Includes declaration-only changes that do not enqueue a body. */
	public function change_source(string $source_key): void
	{
		$this->changed_sources[$source_key] = true;
	}

	/** @return bool[] */
	public function emission_sources(): array /** array<string,bool> */
	{
		return $this->changed_sources;
	}

	/** Only the successful output publisher acknowledges downstream work. */
	public function complete_emission(): void
	{
		$this->changed_sources = [];
	}

	/** Called once when a new body attaches; attachment identity is guarded by the body. */
	public function register(string $source_key, ast_function_body $body): void
	{
		$this->total++;
		$this->enqueue($source_key, $body);
	}

	/** Called once when a replaced/removed body detaches, including bodies that never registered a watch. */
	public function retire(string $source_key, ast_function_body $body): void
	{
		$this->complete($source_key, $body);
		$this->total--;
	}

	public function enqueue(string $source_key, ast_function_body $body): void
	{
		$this->change_source($source_key);
		$this->bodies[$source_key] ??= new \SplObjectStorage();
		$this->bodies[$source_key][$body] = null;
	}

	/** Remove completed/retired work and empty buckets; failures retain their pending membership. */
	public function complete(string $source_key, ast_function_body $body): void
	{
		$bucket = $this->bodies[$source_key] ?? null;
		if ($bucket === null) {
			return;
		}
		$bucket->offsetUnset($body);
		if (count($bucket) === 0) {
			unset($this->bodies[$source_key]);
		}
	}

	/**
	 * Clone each bucket so completion cannot alter snapshot iteration.
	 * @return \SplObjectStorage<ast_function_body,null>[]
	 */
	public function pending(): array /** array<string,SplObjectStorage<ast_function_body,null>> */
	{
		$snapshot = [];
		foreach ($this->bodies as $source_key => $bucket) {
			$snapshot[$source_key] = clone $bucket;
		}
		return $snapshot;
	}

	public function pending_count(): int /** uint32 */
	{
		$count = 0;
		foreach ($this->bodies as $bucket) {
			$count += count($bucket);
		}
		return $count;
	}

	public function body_count(): int /** uint32 */
	{
		return $this->total;
	}
}

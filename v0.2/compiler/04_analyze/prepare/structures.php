<?php

declare(strict_types=1);
namespace scpp\compiler;

/**
 * Canonical builtin definitions for the current subset; enum cases are shared object identities.
 */
enum type_definition: int /** uint8 */ {
	case int64 = 1;
	case void = 2;
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
	/** Native shared-owning source retention. */
	public array $files /** array<string,ast_file_node> */;
	/** Native owning; prepared entry handle. */
	public prepared_entry $entry;
	/** Completion identities, not semantic facts. */
	private array $revisions /** array<string,uint32> */;

	/** CONSTRUCTOR */
	/** Retain the prepared inputs and capture their completion revisions for later validation. */
	public function __construct(string $entry_path, array $files /** array<string,ast_file_node> */, prepared_entry $entry,
		?compilation_session $session = null)
	{
		$this->session = $session;
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
		if (($this->session !== null) && ($this->session->files() !== $this->files)) {
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

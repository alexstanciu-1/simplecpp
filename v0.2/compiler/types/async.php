<?php

declare(strict_types=1);
namespace scpp\compiler;

/** One resolved task ABI, shared by signature admission, dependency collection and C++ lowering. */
final readonly class async_task_binding
{
	private string $identity;

	public function __construct(string $identity)
	{
		$this->identity = $identity;
	}

	/**
	 * A suspending unit needs the complete promise. The emitter collects private demand by concrete task name.
	 * @param array<string,bool> $headers
	 * @param array<string,type_definition> $application_results
	 */
	public function require_definition(string $root, type_definition $type, array &$headers, array &$application_results): string
	{
		$name = $this->cpp_name($root);
		$headers['coroutine'] = true;
		$application_results[$name] = $type;
		return $name;
	}

	/** A producer prototype needs only a forward declaration. */
	public function forward_name(string $root): string
	{
		return 'class ' . $this->cpp_name($root);
	}

	public function cpp_name(string $root): string
	{
		return 'scpp_async_task_' . hash('sha256', $root . "\0" . $this->identity);
	}

	public static function header_path(string $task): string
	{
		return 'units/' . hash('sha256', $task) . '.hpp';
	}
}

/** An owned concrete value can publish the shared async recipe on demand. */
trait concrete_async_task
{
	/** Prepared fact: successful task binding for this layout generation, populated only on demand. */
	private ?async_task_binding $task_binding = null;

	protected function supports_async_storage(): bool
	{
		return true;
	}

	protected function async_task_identity(): string
	{
		return $this->concrete_identity();
	}

	public function async_task(): ?async_task_binding
	{
		if ($this->task_binding !== null) return $this->task_binding;
		if (!type_registry::HAS_ASYNC_FRAMES || !$this->supports_async_storage()) return null;
		return $this->task_binding = new async_task_binding($this->async_task_identity());
	}
}

/** Nominal value tasks keep their original declaration-based publication keys. */
trait nominal_async_task
{
	use concrete_async_task;

	protected function async_task_identity(): string
	{
		return $this->declaration->symbol_key();
	}
}

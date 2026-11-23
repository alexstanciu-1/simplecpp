<?php

declare(strict_types=1);
namespace scpp\compiler;

/** One resolved task ABI, shared by signature admission, dependency collection and C++ lowering. */
final readonly class async_task_binding
{
	/** PROPERTIES */
	/** Published C++ name for a runtime task or stable concrete identity for an application task. */
	private string $identity;
	private string $header;
	/** Null when blocking calls require a producer-owned bridge, including nullary calls. */
	public ?string $wait_header;
	public string $sync_wait;

	/** CONSTRUCTOR */
	private function __construct(string $identity, string $header, ?string $wait_header, string $sync_wait)
	{
		$this->identity = $identity;
		$this->header = $header;
		$this->wait_header = $wait_header;
		$this->sync_wait = $sync_wait;
	}

	/** METHODS */
	/**
	 * A suspending unit needs the complete promise. The emitter collects private demand by concrete task name.
	 * @param array<string,bool> $headers
	 * @param array<string,type_definition> $application_results
	 */
	public function require_definition(string $root, type_definition $type, array &$headers, array &$application_results): string
	{
		$name = $this->cpp_name($root);
		$headers[$this->header] = true;
		if ($this->wait_header === null) $application_results[$name] = $type;
		return $name;
	}

	/** A producer prototype needs only a forward declaration. @param bool[] $headers */
	public function forward_name(string $root, array &$headers): string
	{
		if ($this->wait_header === null) return 'class ' . $this->cpp_name($root);
		$headers['scpp/async_fwd.hpp'] = true;
		return $this->identity;
	}

	private function cpp_name(string $root): string
	{
		return $this->wait_header === null ? 'scpp_async_task_' . hash('sha256', $root . "\0" . $this->identity) : $this->identity;
	}

	/** STATIC METHODS */
	/** Runtime bindings depend only on the immutable generated registry. No source declarations enter this cache. */
	public static function published(type_definition $type): ?self
	{
		static $published = [];
		if (isset($published[$type::class])) return $published[$type::class];
		$abi = type_registry::async_result($type);
		if ($abi === null) return null;
		return $published[$type::class] = new self($abi['cpp'], $abi['header'], $abi['wait_header'], '::scpp::async::sync_wait');
	}

	public static function application(string $identity): self
	{
		return new self($identity, 'coroutine', null, 'sync_wait');
	}
}

/** Nominal types supply their existing declaration key to the concrete task recipe. */
trait nominal_async_task
{
	/** Prepared fact: successful task binding for this layout generation, populated only on demand. */
	private ?async_task_binding $task_binding = null;

	abstract protected function supports_async_storage(): bool;

	public function async_task(): ?async_task_binding
	{
		if ($this->task_binding !== null) return $this->task_binding;
		if (!type_registry::HAS_ASYNC_FRAMES || !$this->supports_async_storage()) return null;
		return $this->task_binding = async_task_binding::application($this->declaration->symbol_key());
	}
}

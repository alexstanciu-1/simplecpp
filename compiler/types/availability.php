<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Immutable application-wide selected module names; e.g. runtime-numeric enables its generated type exports. */
final readonly class type_availability
{
	/** @var bool[] Native owning membership index; no module or session references. */
	private array $modules /** array<string,bool> */;

	/** @param string[] $modules */
	public function __construct(array $modules /** list<string> */ = [], public bool $general_api = false)
	{
		$selected = array_fill_keys($modules, true);
		ksort($selected);
		$this->modules = $selected;
	}

	/** Null denotes an always-available export. */
	public function allows(?string $module): bool
	{
		return $module === null || isset($this->modules[$module]);
	}

	/** Select a published free name under this project's source-surface policy. */
	public function runtime_function(string $key, flavor $flavor): runtime_function|runtime_overloads|runtime_factory|null
	{
		return runtime_functions::free($key, $flavor)
			?? (($this->general_api || $flavor === flavor::cpp) ? runtime_functions::resolve('general:' . $key) : null);
	}

	/** Compare immutable selections before sharing a retained syntax generation. */
	public function same_as(self $other): bool
	{
		return $this->general_api === $other->general_api && $this->modules === $other->modules;
	}
}

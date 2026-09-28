<?php

/* Share canonical occurrence access only among name-bearing specializations. */
namespace scpp\compiler;

trait Collected_Occurrence
{
	public function optional_occurrence(): ?collected_name
	{
		return weakref_get($this->collected_occurrence);
	}

	/** Attach once during collection; preparation cleanup never changes this relationship. */
	public function attach_occurrence(collected_name $entry): void
	{
		if ($this->occurrence_attached) {
			throw new \LogicException('Syntax occurrence is already attached');
		}
		$this->collected_occurrence = $entry;
		$this->occurrence_attached = true;
	}

	public function occurrence(): collected_name
	{
		return object_cast(weakref_get($this->collected_occurrence), collected_name::class);
	}
}

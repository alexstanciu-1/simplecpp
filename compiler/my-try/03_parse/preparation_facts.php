<?php

/* Role: share preparation access and cleanup while concrete specializations own typed facts. */
namespace scpp\compiler;

/** Method grouping only; the consuming class declares its concrete prepared_facts field. */
trait Preparation_Facts
{
	public function preparation(): ?object /** @field-type prepared_facts */
	{
		return $this->prepared_facts;
	}

	public function set_preparation(object /** @field-type prepared_facts */ $facts): void
	{
		$this->prepared_facts = $facts;
	}

	public function clear_preparation(): void
	{
		$this->prepared_facts = null;
	}
}

<?php

/* Role: share per-run keyed presence tracking without owning domain comparison or storage. */
namespace scpp\compiler;

final class Key_Synchronization
{
	private int $revision;

	public function __construct(int $revision)
	{
		$this->revision = $revision;
	}

	/** A second visit to a key in this run is an invalid duplicate input. */
	public function present(sync_presence $presence, string $key): void
	{
		if ($presence->revision === $this->revision) {
			throw new \LogicException('Duplicate synchronization key: ' . $key);
		}
		$presence->revision = $this->revision;
	}

	public function missing(sync_presence $presence): bool
	{
		return $presence->revision !== $this->revision;
	}
}

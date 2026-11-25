<?php
declare(strict_types=1);

namespace Scpp\S2S\Jss;

final class JssNode
{
	/**
	 * @param array<string,mixed> $fields
	 */
	public function __construct(
		public readonly string $kind,
		public readonly array $fields = [],
	) {
	}

	/** @param array{kind?:mixed,fields?:mixed} $values */
	public static function __set_state(array $values): self
	{
		return new self(
			is_string($values['kind'] ?? null) ? $values['kind'] : '',
			is_array($values['fields'] ?? null) ? $values['fields'] : [],
		);
	}
}

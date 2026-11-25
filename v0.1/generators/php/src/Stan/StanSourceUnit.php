<?php
declare(strict_types=1);

namespace Scpp\S2S\Stan;

final class StanSourceUnit
{
	/** @param array{size:int,mtime:int,content_hash:string} $meta */
	public function __construct(
		public readonly string $path,
		public readonly string $sourceKey,
		public readonly array $meta,
		public readonly bool $isRuntimeShallow = false,
		public readonly ?string $contents = null,
	)
	{
	}
}

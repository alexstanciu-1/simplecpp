<?php
declare(strict_types=1);

namespace Scpp\S2S\Stan;

final class StanSourceMetaBuilder
{
	/** @return array{size:int,mtime:int,content_hash:string} */
	public function fromPath(string $path): array
	{
		return \build_file_meta($path);
	}

	/** @return array{size:int,mtime:int,content_hash:string} */
	public function fromContents(string $contents): array
	{
		return [
			'size' => strlen($contents),
			'mtime' => 0,
			'content_hash' => hash('sha256', $contents),
		];
	}
}

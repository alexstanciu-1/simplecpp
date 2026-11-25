<?php
declare(strict_types=1);

namespace Scpp\S2S\Frontend;

interface FrontendCallSurfaceInterface
{
	/** @param list<string> $chain */
	public function resolveNormalizedCallTarget(array $chain): ?string;

	/** @param list<string> $chain */
	public function resolveCallReturnType(array $chain): ?string;
}

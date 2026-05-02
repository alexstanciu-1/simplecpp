<?php
declare(strict_types=1);

// Fully qualified class access across namespaces.
namespace A;

class Box
{
	public static function value(): int
	{
		return 37;
	}
}

namespace B;

echo \A\Box::value(), "\n";

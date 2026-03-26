<?php
declare(strict_types=1);

// Basic namespace alias import with use.
namespace Lib\Math;

class Box
{
	public static function value(): int
	{
		return 43;
	}
}

namespace App;

use Lib\Math as M;

echo M\Box::value(), "\n";

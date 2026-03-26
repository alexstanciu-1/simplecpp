<?php
declare(strict_types=1);

// Basic class import with use.
namespace Lib;

class Box
{
	public static function value(): int
	{
		return 41;
	}
}

namespace App;

use Lib\Box;

echo Box::value(), "\n";

<?php
declare(strict_types=1);

// Basic method call.
class Box
{
	public function getValue(): int
	{
		return 17;
	}
}

$box = new Box();

echo $box->getValue(), "\n";

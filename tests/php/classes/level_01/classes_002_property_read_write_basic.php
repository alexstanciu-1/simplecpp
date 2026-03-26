<?php
declare(strict_types=1);

// Basic property read and write.
class Box
{
	public int $value = 0;
}

$box = new Box();
$box->value = 13;

echo $box->value, "\n";

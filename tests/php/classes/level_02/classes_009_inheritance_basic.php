<?php
declare(strict_types=1);

class BaseValue {
	public function value(): int {
		return 10;
	}
}

class ChildValue extends BaseValue {
}

$item = new ChildValue();
echo $item->value(), "
";

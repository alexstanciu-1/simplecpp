<?php

declare(strict_types=1);

namespace Lib\Math;

const SCALE = 3;

function add(int $a, int $b): int {
	return $a + $b;
}

function scale(int $value, int $factor = SCALE): int {
	return $value * $factor;
}

namespace Lib\Text;

function label(string $name, int $value): string {
	return $name . ":" . $value;
}

function wrap(string $prefix, string $body, string $suffix = "]"): string {
	return $prefix . $body . $suffix;
}

namespace Domain\Model;

class Counter {
	public int $base;

	public function __construct(int $base = 1) {
		$this->base = $base;
	}

	public function next(int $step = 1): int {
		$this->base = $this->base + $step;
		return $this->base;
	}

	public static function make(int $base = 1): Counter {
		return new Counter($base);
	}
}

namespace Domain\Service;

use const Lib\Math\SCALE;
use function Lib\Math\add;
use function Lib\Math\scale;
use function Lib\Text\label;
use function Lib\Text\wrap;

function build_line(string $name, int $left, int $right): string {
	$sum = add($left, $right);
	$scaled = scale($sum, SCALE);
	$text = label($name, $scaled);
	return wrap("[", $text);
}

function run_once(int $seed): string {
	$counter = \Domain\Model\Counter::make($seed);
	$first = $counter->next();
	$second = $counter->next(2);
	return build_line("run", $first, $second);
}

namespace App;

use function Domain\Service\build_line;
use function Domain\Service\run_once;

function compute_total(int $start, int $limit): int {
	$total = 0;
	$i = $start;
	while ($i < $limit) {
		$total = $total + $i;
		$i = $i + 1;
	}
	return $total;
}

function render_summary(int $left, int $right): string {
	$total = compute_total($left, $right);
	return build_line("sum", $total, 2);
}

$line1 = run_once(4);
$line2 = render_summary(1, 5);
$final = $line1 . "|" . $line2;
echo $final, "\n";

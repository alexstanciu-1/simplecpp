<?php

declare(strict_types=1);

namespace Stage3\Numbers;

function bump(int &$value, int $step = 1): int {
	$value = $value + $step;
	return $value;
}

function maybe_take(?int $value, int $fallback = 0): int {
	if ($value === null) {
		return $fallback;
	}
	return $value;
}

function append_piece(string $prefix, int $value): string {
	return $prefix . $value;
}

namespace Stage3\Pipeline;

class Box {
	public int $count;
	public string $name;

	public function __construct(string $name, int $count = 0) {
		$this->name = $name;
		$this->count = $count;
	}

	public function add(int $step = 1): int {
		$this->count = $this->count + $step;
		return $this->count;
	}

	public function rename(string $name): string {
		$this->name = $name;
		return $this->name;
	}

	public static function create(string $name, int $count = 0): Box {
		return new Box($name, $count);
	}
}

function run_round(Box $box, ?int $external, int &$sink): string {
	$local = \Stage3\Numbers\maybe_take($external, 2);
	$first = $box->add($local);
	$second = \Stage3\Numbers\bump($sink, $first);
	$title = $box->rename("pipe");
	return $title . ":" . $second;
}

namespace Stage3\App;

use function Stage3\Numbers\append_piece;
use function Stage3\Numbers\bump;
use function Stage3\Numbers\maybe_take;
use function Stage3\Pipeline\run_round;

function build_status(?int $seed, int &$state): string {
	$value = maybe_take($seed, 5);
	$box = \Stage3\Pipeline\Box::create("start", $value);
	$left = run_round($box, null, $state);
	$right = append_piece("state=", $state);
	return $left . "|" . $right;
}

function drive(int $start): string {
	$state = $start;
	$a = build_status(null, $state);
	$b = build_status(3, $state);
	$c = bump($state, 2);
	return $a . ";" . $b . ";" . append_piece("final=", $c);
}

$out = drive(1);
echo $out, "\n";

<?php

declare(strict_types=1);

namespace Stage3\Config;

const START = 2;
const STEP = 3;
const LIMIT = 5;

function prefix(): string {
	return "cfg";
}

namespace Stage3\State;

class Tracker {
	public int $value;
	public string $name;

	public function __construct(string $name, int $value = 0) {
		$this->name = $name;
		$this->value = $value;
	}

	public function move(int $step = 1): int {
		$this->value = $this->value + $step;
		return $this->value;
	}

	public function snapshot(): string {
		return $this->name . ":" . $this->value;
	}

	public static function boot(string $name, int $value = 0): Tracker {
		return new Tracker($name, $value);
	}
}

namespace Stage3\Logic;

use const Stage3\Config\LIMIT;
use const Stage3\Config\START;
use const Stage3\Config\STEP;
use function Stage3\Config\prefix;

function apply_moves(\Stage3\State\Tracker $tracker, int $extra = 0): string {
	$i = 0;
	while ($i < LIMIT) {
		$tracker->move(STEP);
		$i = $i + 1;
	}
	if ($extra > 0) {
		$tracker->move($extra);
	}
	return prefix() . "-" . $tracker->snapshot();
}

function make_tracker(): \Stage3\State\Tracker {
	return \Stage3\State\Tracker::boot("main", START);
}

namespace Stage3\Program;

use function Stage3\Logic\apply_moves;
use function Stage3\Logic\make_tracker;

function cycle_once(int $extra = 0): string {
	$tracker = make_tracker();
	$before = $tracker->snapshot();
	$after = apply_moves($tracker, $extra);
	return $before . "|" . $after;
}

function cycle_twice(): string {
	$a = cycle_once();
	$b = cycle_once(2);
	return $a . ";" . $b;
}

$result = cycle_twice();
echo $result, "\n";

<?php

declare(strict_types=1);

namespace Stage3\Contracts;

interface Renderable {
	public function render(int $value): string;
}

abstract class BaseRenderable implements Renderable {
	public string $prefix;

	public function __construct(string $prefix) {
		$this->prefix = $prefix;
	}

	public function decorate(string $body): string {
		return $this->prefix . $body;
	}
}

namespace Stage3\Renderers;

use function Stage3\Utils\twice;

class NumberRenderer extends \Stage3\Contracts\BaseRenderable {
	public function __construct(string $prefix = "n=") {
		parent::__construct($prefix);
	}

	public function render(int $value): string {
		$body = $value . "/" . twice($value);
		return $this->decorate($body);
	}

	public static function make(string $prefix = "n="): NumberRenderer {
		return new NumberRenderer($prefix);
	}
}

namespace Stage3\Utils;

const FACTOR = 2;

function twice(int $value): int {
	return $value * FACTOR;
}

function sum_to(int $limit): int {
	$total = 0;
	$i = 0;
	while ($i <= $limit) {
		$total = $total + $i;
		$i = $i + 1;
	}
	return $total;
}

namespace Stage3\Runner;

use const Stage3\Utils\FACTOR;
use function Stage3\Utils\sum_to;

function render_with(\Stage3\Contracts\Renderable $renderer, int $value): string {
	return $renderer->render($value);
}

function execute(int $limit): string {
	$renderer = \Stage3\Renderers\NumberRenderer::make("value=");
	$sum = sum_to($limit);
	$scaled = $sum * FACTOR;
	$left = render_with($renderer, $sum);
	$right = render_with($renderer, $scaled);
	return $left . "|" . $right;
}

namespace Stage3\Main;

use function Stage3\Runner\execute;

function run_all(): string {
	$a = execute(3);
	$b = execute(4);
	return $a . ";" . $b;
}

echo run_all(), "\n";

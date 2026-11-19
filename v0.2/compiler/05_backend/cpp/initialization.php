<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Emits private, ordered input backing and one published factory call; no type-name or conversion policy. */
final class Cpp_List_Initialization
{
	/** @param string[] $elements Converted element expressions in source order. */
	public static function expression(list_initialization_binding $binding, array $elements /** list<string> */): string
	{
		$count = count($elements);
		$type = $binding->element->cpp_type();
		$values = implode(', ', $elements);
		if ($count <= $binding->inline_entries) {
			return "([&]() {\n    $type input[]{{$values}};\n"
				. "    return $binding->factory(input, $count);\n}())";
		}
		return "([&]() {\n    $binding->input input(new $type" . "[$count]{{$values}});\n"
			. "    return $binding->factory(input.data(), $count);\n}())";
	}
}

<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Emits private, ordered input backing and one published factory call; no type-name or conversion policy. */
final class Cpp_List_Initialization
{
	/** Reserve contiguous input for a coroutine. Unwinding destroys only completed entries. */
	public static function staged_input(initialization_transport $binding, int $count, string $name): string
	{
		$guard = $name . '_guard';
		$size = "sizeof(Entry[$count])";
		if ($count <= $binding->inline_entries) {
			$backing = "alignas(Entry) unsigned char bytes[$size]; Entry* data = static_cast<Entry*>(static_cast<void*>(bytes));";
			$release = '';
		} else {
			$alignment = 'static_cast<std::align_val_t>(alignof(Entry))';
			$backing = "Entry* data = static_cast<Entry*>(::operator new($size, $alignment));";
			$release = " ::operator delete(data, $alignment);";
		}
		return "struct $guard { using Entry = $binding->element; $backing decltype(sizeof(Entry)) live = 0; "
			. "~$guard() { while (live) data[--live].~Entry();$release } } $name;";
	}

	/** @param string[] $elements Converted element expressions in source order. */
	public static function expression(initialization_transport $binding, array $elements /** list<string> */): string
	{
		$count = count($elements);
		$type = $binding->element;
		$values = implode(', ', $elements);
		if ($count <= $binding->inline_entries) {
			return "([&]() {\n    $type input[]{{$values}};\n"
				. "    return $binding->factory(input, $count);\n}())";
		}
		return "([&]() {\n    $binding->input input(new $type" . "[$count]{{$values}});\n"
			. "    return $binding->factory(input.data(), $count);\n}())";
	}
}

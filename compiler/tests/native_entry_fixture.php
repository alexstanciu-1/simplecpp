<?php

declare(strict_types=1);

/** Give a native fixture its own main while keeping the generated exception boundary callable. */
function rename_fixture_main(string $name, string $cpp): string
{
	return str_replace(
		['int main(int argc, char** argv)', 'int main()'],
		["int $name(int argc = 0, char** argv = nullptr)", "int $name()"],
		$cpp
	);
}

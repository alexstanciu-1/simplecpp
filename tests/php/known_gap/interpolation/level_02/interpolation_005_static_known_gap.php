<?php
declare(strict_types=1);

// Planned target form: a static access interpolation case.
class Box {
	public static string $name = "Static";
}

echo "Name " . Box::$name, "
";

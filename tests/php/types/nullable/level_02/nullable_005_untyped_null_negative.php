<?php
declare(strict_types=1);

// Untyped null stays unsupported.
$a = null;

echo ($a === null ? "null" : "value"), "
";

<?php
declare(strict_types=1);

// shell_exec should return command stdout as a string and false only on pipe-open failure.
$out = shell_exec("printf shell_exec_ok");

if ($out === false) {
    echo "false\n";
} else {
    echo $out, "\n";
}

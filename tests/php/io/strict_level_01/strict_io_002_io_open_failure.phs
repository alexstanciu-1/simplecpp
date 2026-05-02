<?php
declare(strict_types=1);

$fh /** resource_handle_t */;
echo take($fh, io_open("strict_missing_beta.txt", "rb")) ? "T\n" : "F\n";

fs_mkdir("strict_io_errors");
$path = "strict_io_errors/live.txt";

echo take($fh, io_open($path, "wb")) ? "T\n" : "F\n";
echo take($fh, io_open($path, "rb")) ? "T\n" : "F\n";
echo io_close($fh) ? "C\n" : "c\n";
fs_remove($path);
fs_rmdir("strict_io_errors");

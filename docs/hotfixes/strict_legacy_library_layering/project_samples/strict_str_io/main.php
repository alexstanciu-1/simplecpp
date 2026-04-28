<?php
declare(strict_types=1);

$root = "strict_str_io_root";
$path = $root . "/data.txt";
fs_mkdir($root);

$fh /** resource_handle_t */;
echo take($fh, io_open($path, "wb+")) ? "T\n" : "F\n";
$bytes /** int */ = 0;
take($bytes, io_write($fh, str_implode("|", str_explode(",", "a,b,c"))));
echo $bytes, "\n";
echo io_rewind($fh) ? "R\n" : "r\n";

$line /** string */ = "";
take($line, io_read($fh, 64));
echo str_strtoupper($line), "\n";
echo io_close($fh) ? "C\n" : "c\n";

fs_remove($path);
fs_rmdir($root);

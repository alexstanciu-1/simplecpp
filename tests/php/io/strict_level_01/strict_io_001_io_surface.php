<?php
declare(strict_types=1);

$root = "strict_io_root";
$path = $root . "/data.txt";
fs_mkdir($root);

$fh /** resource_handle_t */;
echo take($fh, io_open($path, "wb+")) ? "O\n" : "X\n";
$written /** int */ = 0;
take($written, io_write($fh, "line1\nline2"));
echo $written, "\n";
echo io_flush($fh) ? "F\n" : "f\n";
echo io_rewind($fh) ? "R\n" : "r\n";
$line /** string */ = "";
take($line, io_read_line($fh));
echo $line;
$pos /** int */ = 0;
take($pos, io_tell($fh));
echo $pos, "\n";
$chunk /** string */ = "";
take($chunk, io_read($fh, 100));
echo $chunk, "\n";
echo io_eof($fh) ? "E\n" : "N\n";
echo io_close($fh) ? "C\n" : "c\n";

$fh2 /** resource_handle_t */;
echo take($fh2, io_open($path, "rb")) ? "O\n" : "X\n";
echo (io_seek($fh2, 2) ?? -1), "\n";
$pos2 /** int */ = 0;
take($pos2, io_tell($fh2));
echo $pos2, "\n";
$chunk2 /** string */ = "";
take($chunk2, io_read($fh2, 3));
echo $chunk2, "\n";
echo io_close($fh2) ? "C\n" : "c\n";

fs_remove($path);
fs_rmdir($root);

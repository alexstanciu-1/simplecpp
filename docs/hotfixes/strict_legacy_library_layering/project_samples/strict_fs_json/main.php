<?php
declare(strict_types=1);

$file = "sample_strict_fs_json.txt";
$err /** error_t */;
$written /** int */ = 0;
take($written, $err, fs_put($file, "{\"name\":\"alex\",\"count\":2}\n"));
echo $written, "\n";

$data /** string */ = "";
take($data, $err, fs_get($file));
echo str_strlen($data), "\n";

$decoded = json_decode($data);
echo $decoded["name"], "\n";
echo $decoded["count"], "\n";

fs_remove($file);

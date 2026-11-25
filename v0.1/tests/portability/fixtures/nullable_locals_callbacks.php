<?php
final class Row { public int $value = 0; }
$row = new Row();
$optional /** nullable<Row> */ = null;
$optional = $row;
$selected /** Row */ = $optional;
$items /** vector<int> */ = [9];
task_run_publish_unordered($items, 1,
    function (int $item) use ($selected): int {
        task_synchronize(function () use ($selected, $item): void { $selected->value = $item; });
        return $item;
    },
    function (int $item): void {});
if ($selected !== $row) { throw new \RuntimeException('nullable identity'); }
echo $row->value;

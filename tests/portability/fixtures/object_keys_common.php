<?php
class Row {
 public int $value = 7;
}
class Index {
 public \SplObjectStorage $rows /** hash<int, Row> */;
 public function __construct() {
  $this->rows = new \SplObjectStorage /** hash<int, Row> */();
 }
}
$index = new Index();
$a = new Row(); $b = new Row(); $alias = $a;
$index->rows[$a] = 10;
$index->rows[$b] = 20;
$index->rows[$alias] = 11;
$a->value = 9;
echo q_count($index->rows), ":", $index->rows[$alias], ":", $index->rows[$b], ":";
$a = null; $alias = null;
$rows /** hash<int, Row> */ = $index->rows;
foreach ($rows as $key /** @object-key */) {
 echo $key->value, ":", $rows[$key], ":";
}
unset($index->rows[$b]);
echo q_count($index->rows), "\n";

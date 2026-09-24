<?php
namespace scpp\compiler;
final class Nested_Row { public int $value = 0; }
final class Nested_Root
{
    public Storage $rows /** Storage<Nested_Row> */;
    public Keyed_Storage $names /** Keyed_Storage<Nested_Row> */;
    public function __construct()
    {
        $this->rows = new Storage /** Storage<Nested_Row> */();
        $this->names = new Keyed_Storage /** Keyed_Storage<Nested_Row> */();
    }
}

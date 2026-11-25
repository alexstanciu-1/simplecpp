# 90 — Dynamic `->` property access
Doc Status: supporting

Representative subset; generation only, not native execution or full row coverage.

Source and generated text below are a representative example, not proof of the whole feature row. No emitted C++ was compiled or executed.

## PHP++ sample

[Source file](main.phs) · [Intended strict project configuration](prism.json)

```php
class Item { public int $value = 7; }
$item = new Item();
$name string = "value";
return $item->$name;
```

## S2S generated v1

Generation error; exit code `1`. [Command and raw logs](../logs/commands.json).

```text
Error: Object of class ast\Node could not be converted to string
```

No C++ emitted. [Raw diagnostic](v1.diagnostic.txt).

## S2S generated v2

Generation error; exit code `1`. [Command and raw logs](../logs/commands.json).

```text
S2S generation failed: main.phs: Expected field name at main.phs: byte 96
```

No C++ emitted. [Raw diagnostic](v2.diagnostic.txt).


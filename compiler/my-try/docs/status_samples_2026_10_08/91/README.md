# 91 — Named arguments, constructor property promotion
Doc Status: supporting

Representative subset; generation only, not native execution or full row coverage.

Source and generated text below are a representative example, not proof of the whole feature row. No emitted C++ was compiled or executed.

## PHP++ sample

[Source file](main.phs) · [Intended strict project configuration](prism.json)

```php
class Item { public function __construct(public int $value) {} }
$item = new Item(value: 7);
return $item->value;
```

## S2S generated v1

Generation error; exit code `1`. [Command and raw logs](../logs/commands.json).

```text
Scpp\S2S\Support\GenerationException: Unsupported expression lowering for AST kind 549 at line 2. Category: generator lowering gap. Requirement: add an explicit lowering rule for this expression shape or rewrite the source to a supported form.
```

No C++ emitted. [Raw diagnostic](v1.diagnostic.txt).

## S2S generated v2

Generation error; exit code `1`. [Command and raw logs](../logs/commands.json).

```text
S2S generation failed: main.phs: Expected parameter name at main.phs: byte 48
```

No C++ emitted. [Raw diagnostic](v2.diagnostic.txt).


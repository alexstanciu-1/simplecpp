# 89 — PHP enum reflection (`cases/from/tryFrom`, pseudo-properties), string backing and enum methods/interfaces
Doc Status: supporting

Representative subset; generation only, not native execution or full row coverage.

Source and generated text below are a representative example, not proof of the whole feature row. No emitted C++ was compiled or executed.

## PHP++ sample

[Source file](main.phs) · [Intended strict project configuration](prism.json)

```php
enum Mode: string { case Ready = "ready"; }
$cases = Mode::cases();
return count($cases);
```

## S2S generated v1

Generation error; exit code `1`. [Command and raw logs](../logs/commands.json).

```text
RuntimeException: Only literal int-backed enum case values are supported in the current enum lowering
```

No C++ emitted. [Raw diagnostic](v1.diagnostic.txt).

## S2S generated v2

Generation error; exit code `1`. [Command and raw logs](../logs/commands.json).

```text
S2S generation failed: Static call requires a source class or struct
S2S enum backing requires an integer type
```

No C++ emitted. [Raw diagnostic](v2.diagnostic.txt).


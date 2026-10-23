# Typed empty container parameter defaults
Doc Status: supporting

An ordinary method or constructor parameter with an explicit PHP array annotation
may use an empty default:

```php
public function collect(array $items /** vector<int> */ = []): int {
    return q_count($items);
}
```

The converter emits the annotated type and `= []`. It does not infer element types
from the body or callers. Missing annotations and nonempty array defaults reject.
The same syntax is accepted for promoted constructor parameters; this does not
change the existing promotion mapping or required-field initialization contract.

Each omitted argument produces a fresh empty value. An explicit argument uses the
ordinary value boundary, including container copies. Nullable null defaults remain
a separate contract. Interface container signatures remain subject to their existing
restrictions, and no new reference-default behavior is introduced.

The v0.2 compiler reuses registered initialization and declaration construction
facts; it does not recognize vector/hash names to admit defaults. The legacy PHP
S2S generator uses the parameter's explicit local type for its empty array initializer,
sharing the typed vector/hash initializer already used at local/property boundaries.
This is a narrow structural lowering rule, not symbol or receiver inference.

`tests/portability/empty_defaults.py --results FRESH` checks PHP, the current legacy
S2S route, and ordinary/prelowered v0.2 native execution. It proves method omission,
explicit values, repeated calls and promoted constructor defaults. It does not prove
an unmodified historical checkout or whole-compiler native execution.

`compiler/my-try/tests/empty_defaults.py --results FRESH` adds typed hash/nested
containers, retained rejection/recovery and a distinct generic provider with counted
factory invocations in both C++ modes. Broader provider integration and standalone
annotated empty expressions remain open under CONVERT-01; this checkpoint does not
close the whole backlog item.

## Standalone empty expressions and output targets

Use `/** vector<T> */ []` or `/** hash<T> */ []` for a standalone empty PHP array,
including call arguments, returns and whole-field replacement. A directly annotated
local initializer needs no second comment; nested literals inherit that explicit
structural intent. Callee/member types are not inferred. Missing intent rejects;
ordinary append/index syntax remains distinct from an array literal.

The converter defaults to `--target=v0.1`; select `--target=v0.2` for the newer
compiler. Legacy output uses its existing named construction; v0.2 output uses
`Type::[]` and the registered initialization path. This converter-only route choice
adds no native language rule. Source-only token caching remains shared; emitted
output fingerprints include the target, and target switches republish as needed.
The cross-route proof covers empty argument/local expressions, target switching,
warm reuse and invalid-target non-publication in addition to defaults.

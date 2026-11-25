# Module Inclusion Model (Static)
Doc Status: normative
**Status:** Intentional divergence from PHP

## Rules

1. `require`, `require_once`, `include`, `include_once` MUST use compile-time constant string literals:
```php
require 'file.php';        // valid
require __DIR__ . '/x';    // invalid
require $path;             // invalid
```

2. Resolution:
- paths are resolved at compile time
- relative to project root or entry file
- no `include_path`
- no runtime lookup

3. Semantics:
- inclusion is compile-time dependency linking
- all files form a static program graph
- namespace-scoped class headers may require forward declarations for referenced classes before full class definitions are emitted
- the generator should emit `class X;` style forward declarations early enough to support same-namespace cycles across included files

4. Not supported:
- dynamic includes
- conditional includes
- runtime module loading

## Rationale

PHP include semantics are runtime-dependent and non-deterministic.

This model enforces:
- deterministic builds
- static analysis compatibility
- correct C++ mapping
- safe header generation for cross-file class references without forcing users to flatten type graphs artificially

This is a deliberate language design decision.

See: module_inclusion_model.md

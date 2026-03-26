# `know_how` fixtures

This folder is for AST/exporter reconnaissance fixtures.

These files are not the main staged compatibility suite. Their role is narrower:
- isolate one syntax pattern at a time
- export its JSON AST beside the PHP file
- document what the current php-ast exporter actually emits
- drive generator fixes from exporter reality, not from assumptions about PHP surface syntax

## Confirmed exporter behavior

### `echo`
- `echo "A"; echo "B";` -> sibling `AST_ECHO` nodes
- `echo "A", "B";` -> also sibling `AST_ECHO` nodes
- practical lowering rule: emit one runtime print per `AST_ECHO`, in source order

### `unset`
- `unset($a);` -> one `AST_UNSET`
- `unset($a, $b);` -> sibling `AST_UNSET` nodes
- practical lowering rule: emit one runtime unset call per `AST_UNSET`, in source order

### `isset`
- `isset($a);` -> one `AST_ISSET`
- `isset($a, $b);` -> boolean-op tree combining single-operand `AST_ISSET` nodes
- practical lowering rule: lower `AST_ISSET` as a single-operand runtime call, and lower the surrounding boolean-expression tree separately

## Usage rule

When a construct is AST-uncertain, add a focused PHP sample here first, regenerate the JSON sidecar, then update lowering.


## Namespace / `use` reconnaissance notes

### Confirmed lowering model
- PHP namespaces emit under `scpp::...`
- fully-qualified PHP names such as `\A\B\C` lower to rooted C++ names such as `::scpp::A::B::C`
- non-root qualified names in normal code remain syntactic (`A\B\C` -> `A::B::C`)
- `use` declarations are treated as absolute imports and are emitted inside the target generated namespace block

### Supported `use` forms
- `use A\B\C;` -> `using ::scpp::A::B::C;`
- `use A\B\C as D;` -> `using D = ::scpp::A::B::C;`
- `use function A\B\f;` -> `using ::scpp::A::B::f;`
- `use function A\B\f as g;` -> `inline constexpr auto g = ::scpp::A::B::f;`
- `use const A\B\X;` -> `using ::scpp::A::B::X;`
- `use const A\B\X as Y;` -> `inline constexpr auto& Y = ::scpp::A::B::X;`
- grouped imports are expanded one imported element at a time

### Current mismatch worth remembering
- `use const` without alias can diverge from PHP when a same-name constant already exists in the current namespace
- explicit parentheses in arithmetic expressions must be preserved from the recursive AST structure; current code now emits grouped arithmetic with AST-driven parentheses

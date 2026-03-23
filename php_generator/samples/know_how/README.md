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

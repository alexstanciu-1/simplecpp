# Typed-map probes, iteration and native type emission
Doc Status: supporting

The converter now admits braced, by-value `foreach` with either `$value` or
`$key => $value` bindings. It uses the existing expression/body parser and preserves
the target's iteration syntax without discovering element or key types. Bare
`break;` and `continue;` are supported; levels, reference bindings, destructuring
and alternative colon syntax remain rejected.

Keyed `isset` admits one variable/member path with explicit literal or variable
keys, including fixed member keys such as `$positions[$file->id]`. Multiple
operands, calls, increments and other compound key expressions are rejected by
this slice. It is a language construct, not a function-map entry or PHP helper.
Its target owner remains the existing null-sensitive, non-mutating probe contract.
The proof covers non-null integer/bool maps, not every nullable or nested probe shape.

## Behavioral proof

`tests/portability/map_iteration.py` checks:

- zero and false values count as present;
- missing integer/string keys return false without changing map size;
- integer and string key/value iteration returns the expected entries;
- iteration assertions do not depend on hash ordering;
- scalar loop-variable reassignment does not modify the original vector;
- an iterated object still shares identity and observes mutations;
- empty iteration, continue and break behave as expected;
- private map fields can be probed through a fixed member key.

PHP and strict native v0.1.76 produce independently specified results. Structural
mutation of the traversed container and by-reference iteration are not covered or
recommended by this proof. `map_iteration_rejections.py` tests unsupported forms
and unchanged published output on rejection.

## PHP annotations become native PHS syntax

Authored executable PHP:

```php
private array $folders /** vector<vector<int>> */ = [];
```

Generated PHS:

```php
private $folders vector<vector<int>> = [];
```

The same rule applies to container locals and nullable fields/promoted parameters.
Type doc comments belong to the PHP authoring input; generated PHS uses explicit
native type syntax. Ordinary explanatory/doc comments can remain. This supersedes
earlier checkpoints describing type-comment emission. It changes no inferred
types and does not remove the documented native readonly enforcement limitation.

[Map/iteration evidence](../planning/compiler_migration/results/map-iteration-01/summary.json)
retains source, output and command logs. Cumulative compiler and nested-container
proofs are also rerun to validate the native type spelling on the selected target.
Source_Set production adaptation and JSON/snapshot-copy work remain separate;
this slice does not add a production file to the fourteen-file ready set.

## Explicit hash-slot removal

The preparation queue needs to release completed requests and dependency edges.
Portable PHP now admits `unset($map[$key]);` and fixed field paths such as
`unset($this->waiting[$request->key]);`. The converter preserves the construct;
the native hash owns removal and missing-key no-op behavior. No runtime facade or
symbol/type inference is added.

This narrow form accepts one indexed target, rooted at a variable, with optional
fixed members before the index. Its key is an integer/string literal or a variable
with optional fixed members. Variable removal, multiple operands, nested indexes,
dynamic members and computed/call keys are rejected. Bind computed keys to locals.
Authors must use hashes: accepting the syntax does not authorize vector deletion.
Do not remove from the same container being traversed.

The map-iteration proof now also checks integer/string removal, unaffected key
stability, missing-key no-ops, member-key removal, repeated removal and reinsertion.
Checker tests cover the rejected forms and unchanged output on rejection.

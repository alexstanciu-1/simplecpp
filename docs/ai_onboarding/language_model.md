# Language Model
Doc Status: supporting

Purpose: summarize the mental model an AI assistant should carry while editing this repository.

Primary semantic authorities:

- `specs/spec_map.md`
- `specs/dynamic_types.md`
- `specs/array_semantics.md`
- `specs/php/catalog.md`
- `generators/php/specs/rules.md`

## 1. Authoring Surface

Current rule:

- PHP is the source language users write.
- Generated C++ is not the authoring surface.

Generated C++ is useful for:

- debugging lowering
- understanding runtime calls
- inspecting where a semantic mismatch appears

Generated C++ is not the place to invent source-language behavior.

## 2. Authority Order

When sources disagree, follow:

1. `specs/spec_map.md`
2. top-level semantic specs under `specs/`
3. architecture specs under `specs/architecture/`
4. runtime and generator subsystem specs
5. machine-readable config/data
6. tests and tooling docs
7. implementation behavior

Do not silently normalize disagreements away. Treat them as a spec gap, known fail, or regression.

## 3. Dynamic Type Model

The most important semantic rule in the repo is:

- dynamic expressions remain `mixed_t` by default
- they become native only at explicit typed boundaries or explicit narrowing points

Typical approved explicit typed boundaries:

- typed local assignment
- typed property assignment
- typed container element assignment when the receiving slot type is explicit, such as `hash<T>[...]`
- typed append destination when the receiving element type is explicit, such as `vector<T>[]`
- typed by-value function or method parameter
- typed return
- explicit cast
- explicit narrowing guard such as `is_int(...)`

Important non-rules:

- no implicit mixed extraction just because an operator or overload would like it
- no by-reference normalization from `mixed_t` into native `T&`
- no overload-disambiguation through implicit mixed extraction

Practical editing guidance:

- if a bug is about `mixed`, typed destinations, or call-site normalization, inspect `specs/dynamic_types.md` first
- if the implementation hotspot is unclear, inspect `generators/php/src/Lowering/TypeMapper.php` and the runtime mixed helpers second

## 4. Arrays / Tables

Prism++ intentionally supports a narrower array/table model than full PHP.

Stable current expectations:

- plain reads do not create storage
- missing-key reads yield `null`
- write paths may create storage
- nested writes may autovivify missing intermediate table/hash nodes
- wrong-kind intermediates throw
- append is supported on compatible carriers
- typed destinations do not change read semantics; the read still happens first, then ordinary typed-boundary rules apply

Important safety rule:

- array/property paths are not approved native by-reference binding targets in the current safe subset

If a task touches indexed reads, append, `isset`, `empty`, `unset`, or nested writes, check `specs/array_semantics.md`.

## 5. Null, False, Truthiness

Keep these distinctions explicit:

- `null` is not `false`
- failure and absence should not be merged
- strict comparison is preferred

Condition guidance:

- avoid using unresolved `mixed` or arbitrary strings directly as conditions when intent matters
- normalize to a clear boolean or compare to the intended sentinel/state

Relevant references:

- `specs/php/catalog.md`
- `specs/php/canonical_examples.md`
- `specs/strict_mode.md`
- `specs/count_empty_isset_contract.md`

## 6. Generator Model

The generator is intentionally:

- deterministic
- structured
- local/syntactic first

It is not trying to be a full semantic compiler.

That means:

- if a supported source form can be lowered locally, the generator should emit it
- semantic invalidity may still be delegated to the C++ compiler unless a local generation rule says otherwise
- Codex should avoid assuming the generator performs deep rescue or inference

Generator-facing entry points:

- `generators/php/src/Transpiler.php`
- `generators/php/src/Generator/Generator.php`
- `generators/php/src/Lowering/TypeMapper.php`

The best sample-backed reality checks live under:

- `generators/php/samples/stage_*`
- `generators/php/samples/know_how/`

## 7. Runtime Model

Generated code targets the `scpp` runtime.

Practical public umbrellas:

- `runtime/include/scpp/runtime.hpp`
- `runtime/include/scpp/lang/php.hpp`

Generator-facing PHP semantic calls should follow the active PHP profile surface:

- legacy profile prefers `scpp::php::*` entrypoints
- strict profile may use flat visible names that lower directly to shared runtime families through the active profile registry

## 8. Editing Posture

Use this default posture:

- edit PHP input semantics in specs before code if meaning is unclear
- edit the generator when lowering is wrong
- edit the runtime when shared behavior/helpers are wrong
- edit docs when the authority is clear but hard to discover

Avoid these mistakes:

- treating generated C++ as the semantic authority
- widening PHP support based only on one local implementation trick
- assuming unsupported PHP features should be guessed into existence
- fixing a runtime symptom while ignoring a higher-level spec conflict
- editing the wrong layer because the first visible failure happened downstream

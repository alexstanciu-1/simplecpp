# Generator Design Principles For The Long-Term Fix
Doc Status: planning

Purpose: capture the generator-side anti-patterns to remove or constrain while we fix typed `hash<>` lowering in a way that respects the project rule that the PHP S2S generator is type-blind and runtime-interface-driven.

This note is planning only.
It does not override normative specs.

## Current Grouping Plan

We will address these in groups.

The first group is `foreach`.
Goal: make typed `hash<>` plus direct `foreach ($data as $k => $v)` usage remain strongly typed end-to-end without degrading into:

- dynamic `.get(...)` access
- invalid `cast<nullable<string_t>>`
- object-field writes lowered as keyed table writes

After the `foreach` group is corrected, run the normal test suites except matrix.

## Generator Anti-Patterns To Remove Or Constrain

- Inferring `foreach` key/value meaning from the source expression type.
  `renderForeachStatement()` should not depend on recovering a rich semantic type from the source expression just to decide what `$k` and `$v` are.

- Letting non-`this` method-call sources fall off the typed path.
  If `foreach ($loader->load() as $k => $v)` degrades just because the source is a method call on an object instead of `this`, the generator is making a semantic distinction it is not well-equipped to maintain.

- Reconstructing hash semantics in the generator when the runtime `foreach` interface already exposes them.
  Once lowering goes through `foreach_range(...)`, the generator should rely on entry-facing interfaces like `key()`, `value_copy()`, and `value_ref()`, not re-derive container meaning from AST origin.

- Using `mixed_t` fallback too early for loop locals.
  Early collapse of `$k` or `$v` to `mixed_t` is the root of several downstream bad lowerings and should be much more constrained.

- Branching property reads on guessed base type in a way that silently flips to dynamic `.get(...)`.
  `typed_property->disabled` becoming `typed_property.get("disabled")` means property lowering is trusting a degraded guessed type too much.

- Branching property writes on guessed base type in a way that silently flips to keyed table writes.
  `obj->field = x` should not become `obj["field"] = x` just because the generator lost confidence upstream.

- Injecting conversion casts as a repair for earlier type loss.
  `cast<nullable<string_t>>(property_name)` is a symptom that the generator already degraded the key local and is now papering over it.

- Treating `mixed_t` as a safe universal bridge for typed hash flows.
  In typed `hash<T>` paths, falling back to `mixed_t` should be a last resort, not a normal intermediate representation.

- Encoding object-vs-dynamic access policy in scattered local heuristics.
  If property reads, property writes, helper-call wrapping, and `foreach` typing all make their own local guesses, the system becomes inconsistent.

- Making authored loop style affect semantic outcome.
  `foreach ($props as $k => $v)` should not require defensive `(string)$k` stabilization just to preserve meaning the runtime already knows.

- Assuming the generator can reliably recover precise static types from arbitrary composed expressions.
  In a type-blind architecture, richer expressions like helper returns, chained method calls, or fetched typed objects are exactly where ad hoc inference will drift.

- Allowing `unknown` to silently downgrade into dynamic object-model behavior.
  There should be a clearer boundary between "generator cannot prove more" and "therefore emit dynamic table/object behavior."

- Using source-shape heuristics where runtime carrier interfaces should be authoritative.
  The long-term healthy pattern is: runtime carriers define access shape, the generator emits against those carriers, and the generator does not reinvent semantic typing around them.

- Preserving typed behavior only for happy-path surface forms.
  If typed hash works for direct locals but not for helper-returned or property-returned hashes, the lowering rule is too syntax-sensitive.

- Spreading typed-hash policy across special cases instead of one stable contract.
  `hash<T>` key/value handling, helper-call argument passing, property access after loop binding, and write-back behavior should all follow one runtime-facing contract, not multiple unrelated exceptions.

## Short Takeaway

The main anti-pattern is:
the generator lowers `foreach` through the correct runtime interface, then second-guesses that interface with partial semantic reconstruction.

That second step is where the degradation starts.

## Current `foreach` Conclusion

After the current hotfix pass, `foreach` lowering is much closer to the intended type-blind S2S model.

- The loop shape is runtime-interface-led.
- The generator lowers through `foreach_range(...)`.
- Key/value access flows through runtime entry interfaces such as `key()`, `value_copy()`, and `value_ref()`.
- The supported typed-hash cases no longer depend on defensive key stabilization workarounds in the generated C++.

Important nuance:

- The generator is still not "zero type awareness" around `foreach`.
- It still performs narrow local structural handling, such as preserving recognized container element/key types when they are already available from the declared surface.
- That remaining behavior is acceptable under the current project rule because it is local lowering support, not full semantic compilation.

So the accurate architectural statement is:

- `foreach` lowering is now runtime-interface-led, not semantic-compiler-led.
- Remaining generator awareness is narrow and local.
- It is not absolutely type-blind in the strongest possible sense, but it is aligned with the intended type-blind architecture.

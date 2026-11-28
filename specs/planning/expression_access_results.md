# Expression and access-path audit results

Doc Status: planning

2026-11-28. Compiler baseline `c0a428116`, integrated upstream revision `a702bab53`, final regression revision `56e95560c`. The [inventory](expression_access_inventory.md) gives the disposition of each of the 52 reviewed rows. Colors describe the observed cases within each row. They are not a percentage of the language implemented.

| Status at audit close | Rows | Meaning |
|---|---:|---|
| 🟢 Verified | 41 | The specified behavior or rejection has an observed witness. Some rows rely on existing suites. |
| 🔴 Defect | 1 | Two source-identical callable signatures behave differently in a conditional selection depending on their declaration origin. |
| 🟠 Follow-up | 10 | Broader contracts, diagnostics, surface decisions or architecture work. The concrete items are listed below. |

## 🟢 F1 fixed after audit. Callable selection now compares signatures

At the audit baseline, whole-value assignment between independent declarations of the same callable signature succeeded, while both selections below failed in preparation.

```php
$a function<int(int)> = fn($x) => $x + 1;
$b function<int(int)> = fn($x) => $x + 2;
$f = true ? $a : $b;                  // Ternary result types must be identical
// Independent probe
$f = match (1) { 1 => $a, default => $b };  // Match result types must be identical
```

Controls distinguish signature compatibility from declaration origin. Selecting the same local twice works. Selecting a local and its inferred copy works. Selecting two separate closure **values** produced by the same typed factory declaration works and invokes the correct body. Giving both factory outputs separate explicit but identical annotations reproduces the failure. The ternary also rejects when the two inputs are separately declared parameters of identical type. Resident and fresh preparation agree. At the audit baseline the arm join compared prepared type objects by identity. **Follow-up 🟢 fixed.** The shared preparation step now uses the existing exact type matcher. The default suite invokes both alternatives and checks parameter type, return type, arity and reference-mode mismatches for ternary and match, including recovery after a signature edit. [Preparation](../../compiler/04_analyze/prepare/prepare.php) · [Positive and failing controls](../../compiler/tests/expression_access_paths.py)

## 🟠 Remaining implementation, diagnostic and design work

| Item | Evidence and next decision |
|---|---|
| 🟢 F2 fixed after audit | Homogeneous nullable integers and identical shared class handles are implemented. Their both-empty outcomes differ. The registered tests check the domain error for integers and the empty result for optional handles. [Compiler contract](../../compiler/docs/coalesce.md) |
| 🟢 D1. Python nested typed-write diagnostic | Fixed in workspace 03. The preparation diagnostic identifies the storage available in Python. The default regression checks rejection, repair and native execution of both working controls. Typed-vector write admission remains a separate decision. |
| 🟢 D2. Plain PHP scalar stores through computed shared class handles | Grouped and ungrouped indexed receivers and shared factory results retain the selected allocation through the store. The native regression observes the original and replacement values, throwing RHS behavior and readonly edit/recovery. [Evaluation contract](../expression_evaluation.md#php-scalar-field-stores-through-selected-shared-owners) |
| 🟢 D3. Computed PHP callees and cross-flavor indexed invocation | PHP now accepts parenthesized expressions producing an owned callable. The registered ternary and match cases verify lazy selection, replacement during argument evaluation and recovery after a provider edit. C++, JavaScript and Python now accept `ops[key](arg)` using the same stored-call preparation and lowering. |
| D4. Decide additional access syntax and grouping expectations | A grouped optional field followed by an ordinary field access checks the resulting owner. On an absent outer receiver it raised `ownership_empty_access`; the continuous guarded member chain produced absence. Parentheses ending a field read also put a subsequent index outside the guard; the paired tests cover both preparation boundaries. A grouped scalar optional can feed coalescing. Grouped invocation of a nullsafe owned callable field now uses its empty state and ordinary argument ordering. PHP admits computed string-key reads on dynamic. PHP braced interpolation reads one declared field from a local or parameter. Plain local dynamic assignments, compounds and standalone updates accept computed string keys. Local dynamic write paths accept bracket and computed names at arbitrary depth for assignment, append, compounds and standalone updates. Computed calls, nonlocal computed writes, nullsafe writes and nonlocal/indexed `??=` remain limited. Decide each surface before broadening admission; a grouping comparison alone does not prove a language bug. |
| D5. Keep existing bounded semantics explicit | Non-Python mixed nested value reads, references escaping into movable slots and the sampled lexical comparisons of numeric-looking strings have existing boundaries. No evidence in this audit calls for changing boxed ownership, reference origins or comparison semantics. |
| A1. Consider preparing typed destinations once | Preparation records boxed roots. The emitter also calls `nested_collection_root` on typed stores, appends and mutations. This is duplicated classification, with incremental invalidation implications. The retained-owner and write-back probes did not reproduce another lost write. Treat a refactor as a separate engineering decision. [Preparation](../../compiler/04_analyze/prepare/prepare.php) · [Lowering](../../compiler/05_backend/cpp/emit.php) |

**Two historical gaps closed upstream during the audit.** PHP now prints scalar bytes/bools and reports the received type for a boxed echo operand. PHP kind predicates now admit dynamic values; `is_string` and `is_array` are available. New upstream coverage also exercises terminal checked vector/hash reads on nullsafe chains and nullsafe enum methods and read-only case properties. These changes are included in the integrated default-suite result. [QoS follow-up](php_flavor_developer_qos_reaudit.md) · [Nullsafe access contract](../conditional_expression_matrix.md)

## Workspace 03 reconciliation

The GAPS thread in `simple_cpp_03` owns the full inventory. The handoff was
`cc4a00c7f`. The pulled baseline for this pass was `d98403096`. The last
inventory column now contains 47 verified rows and five follow-up rows.
F1 (`59d400219`) accounts for the original red row. D1 closes one diagnostic
row. F2 (`cc4a00c7f`) closes two coalescing cases within a broader follow-up row.

On dev002, the root default configure and build passed for `d98403096` plus
the D1 compiler/test patch (stable patch ID `7ad72cd9a4be`). The focused
expression-path and coalescing selection passed **47/47** in 9.60 s. The
full default suite passed **662/662** with `ctest --preset default -j24`
in 90.66 s. These figures apply to the workspace 03 snapshot.

- **D1, Python preparation.** At the pulled revision,
  `v: vector<vector<int>> = [[3]]` followed by `v[0][0] = 8` parsed and
  rejected at line 2, column 1. The diagnostic offered a typed local/field
  path that Python excludes. The revised message is
  `Nested indexed write requires inline fixed-array storage or a shared Python container`.
  The registered `python_nested_write` regression compares resident and fresh
  diagnostics, then reads the typed element and writes and reads a shared
  boxed-list element in the repaired native program.
- **D2, PHP preparation.** Replayed with `class N {public int $x=3;}`,
  `function make():N {return new N();}` and `$owners vector<N>=[make()];`.
  Each destination in `$owners[0]->x=7`, `($owners[0])->x=7` and
  `make()->x=7` parses and reports `Field assignment requires writable local storage`.
  The D2 implementation on base `83f3ab25f` admits these plain stores.
  In the vector regression the original owner's destructor observes 7 after
  slot replacement, and a read of the replacement returns 3. A throwing RHS
  leaves the factory result at 3 and a previously written hash-held object at 7.
  On dev002 the D2 compiler/test patch (`cedd25a45ff9`) passed 15/15 focused
  checks and 663/663 default tests with `ctest --preset default -j24` in 45.80 s.
- **D3, parser.** At the handoff, direct `return ops[0](6)` in C++, JS and
  Python and PHP `(true ? $a : $b)(7)` all stopped at the invocation suffix.
  The PHP slice on base `a2aac22c9` reuses stored-call preparation and lowering
  for parenthesized owned callable results. In both conditional fixtures only
  the selected arm runs. After an argument replaces both bindings, the pending
  call returns 8 or 9 according to the selected arm, and the next call returns
  17. On dev002, 11/11 focused checks and 665/665 default tests passed.
  The second slice on base `fcc0a0f62` adds index-call syntax in the other
  three frontends. Each native consumer invokes vector and hash entries from
  a PHP provider. Argument-side replacement preserves the original callable
  for the pending call. Missing hash and empty vector entries have separate
  exception and argument-effect checks. On dev002, 19/19 focused checks and
  674/674 default tests passed.
- **D4, PHP.** The registered `grouped_absent` and `grouped_present` fixtures
  distinguish a grouped empty receiver's runtime failure from absence propagated
  by the continuous chain. Repeated checked reads (`adaee7bba`), ordinary
  member continuation (`7fa1e23dd`) and terminal indexed calls (`5b2388267`)
  have their own native regressions. On base `f478637e0`, the
  `callable_field_*` fixtures check grouped invocation with an absent router
  and an empty field, retention during argument-side replacement, a saved
  callable copy, and cross-file edit to an integer field and recovery. On
  dev002, 8/8 focused checks and 679/679 default tests passed.
  The paired `index_vector`, `index_hash` and `index_string_vector` fixtures
  compare grouped preparation errors with native continuous reads after
  removing the parentheses. An absent owner traces only the probe; a present
  owner traces the probe and one key on both successful and failed lookup.
  On base `4b7eab2a4`, dev002 passed 5/5 focused checks and 680/680 default tests.
  The `computed` fixture traces one receiver evaluation followed by one name
  evaluation, checks numeric strings, missing keys and the `remove` collision,
  and repeats the native trace after a cross-file key-type edit and recovery.
  The `computed_admission` fixture covers receiver restrictions and deferred call and write forms.
  The `computed_write` fixture checks key-before-RHS evaluation, insertion and replacement of the data key `"remove"`,
  preservation of its value on a throwing RHS, and a cross-file key-type edit and recovery.
  On base `a93fb15da`, dev002 passed 14/14 focused checks and 682/682 default tests.
  The `computed_mutation` fixtures cover the original operand across RHS replacement, single key evaluation
  for compound and prefix/postfix updates, a missing key before the RHS, and removal before write-back.
  The `computed_nested` fixtures trace both keys before the RHS, verify replacement-child writes through an alias,
  and check that a missing intermediate fails after RHS effects.
  D4-W1 verifies the replacement child's next append key and lookup after RHS effects.
  D4-W2 verifies the saved operand, current child, standalone updates, and missing or removed leaves.
  D4-W3 verifies a computed name in the middle of deeper paths, outer-to-inner key order,
  cross-file string-key edit and recovery, and missing-path timing for assignment and compound.
  The PHP interpolation fixtures verify declared string and integer fields, cross-file rename and recovery,
  and first-error ordering before echo emits the interpolated operand.
  In a separate probe, `$h?->x=7` stops in parsing (expected call statement). Indexed
  `$v[0] ??= 7` and field `$h->x ??= 7` on integer slots parse and reject
  in preparation with `Unsupported compound operands or write-back type`.
- **D5 and A1.** Keep the missing-key, mixed-read, stable-reference and text
  comparison contracts in the inventory. The intervening compiler changes
  concern terminal indexed calls. Typed stores, appends and updates still
  consult `nested_collection_root` in lowering. The existing native controls
  protect write-back behavior; they cannot prove classification happens once.

## 🟢 Native witnesses worth retaining

| Expression boundary | Observed on dev002 |
|---|---|
| Awaited key replaces A with B | Key enters and resumes once. Ordinary argument runs once. Both the pending call and the following lookup invoke B and return 26. |
| Awaited key removes the selected entry | Only key entry and resumption run. The checked lookup raises `hash_missing_key` before any argument or invocation. A present but empty callable instead evaluates the argument before raising `std::bad_function_call`. |
| Both key and argument suspend | Key installs B, then argument installs C. Pending call returns 26 from B; next lookup returns 36 from C. Rebinding the selected holder's registry during the key retains the original holder for the pending call. |
| Last external callable handle removed, then key or argument throws | Destruction happens at removal when the key is still being computed; a previously selected callable survives removal until unwind in the argument case. The caught typed exception has the same identity as the thrown object. No later operand or callee runs. |
| Failing writes | Throwing deep RHS leaves the prior leaf at 3 and its earlier side effect at 1. Modulo by zero and failed byte narrowing preserve the stored 7. Shallow typed-hash store accepts either permitted order of sibling evaluation while checking once-only effects and absence of partial stores. |
| Failure before a following suffix | An illegal index in a string, fixed byte array, vector or hash stops before conversion to the next key or evaluation of the outer call argument. |
| Computed method receiver and binding modes | Indexed receiver evaluated once per invocation. By-reference write reaches the caller, reversed named arguments run in written order, and unpacking passes the two vector elements once. |
| Original expression and conditional arms | Original parsed shape matches. Statement and value forms execute, a provider change makes the index invalid, and repair restores native behavior. A ternary preserves selected shared identity; an effectful match key runs only its chosen arm, and an exception suppresses the outer argument. |
| Reads inside writes and void calls used as values | Deep mixed destination admitted, but the identical deep read is still rejected in an index or RHS. Terminal nullsafe void statement admitted; using its result as key, argument or continued receiver rejects and repairs cleanly. |

## Verification and reproduction

- Ran on dev002 using Clang, libc++, the root default CMake preset and the configured PHP 8.5 compiler. The host uses its existing libc++ installation and the runtime ownership-header include path. Native fixtures compile and execute generated C++; negative fixtures compare diagnostics, exception types/messages, effects or fresh/resident output as appropriate.
- Baseline default suite **593/593** passed. After the pull, build and default suite at `d3ca7dd96` passed **629/629**. After the test timing adjustment at `56e95560c`, the final `ctest --preset default -j24` again passed **629/629**, in **39.41 s**. This includes **26 new registered regression cases**. Suite counts describe this checkout and preset. The historical AST/native replay also exercised PHP, C++, JavaScript and Python. Syntax-only cross-flavor rejects were not counted as native executions.
- To run the new default regressions alone from a configured checkout, use `ctest --preset default -R '^compiler_expression_path_' --output-on-failure`.
- The full callable-collection and boxed-collection on-demand drivers passed on dev002. In isolation the complete valid/invalid/repaired anchor and selection tests took 5.15 s and 4.52 s. Their default forms keep the invalid-provider check and native recovery, taking 1.39 s and 2.10 s respectively. The full sequences also passed after the split; run them with `anchor_full` and `selection_full` in the same driver.
- The F1 reproducers are registered as `compiler_expression_path_declared_ternary` and `compiler_expression_path_declared_match`. The F2 reproducers are registered as `compiler_expression_path_wrapper_coalesce` and `compiler_expression_path_handle_coalesce`. [Test driver](../../compiler/tests/expression_access_paths.py)

Scope is the semantic distinctions in the reviewed inventory. This is evidence for the tested shapes on dev002. It does not establish exhaustive language coverage or native portability on untested toolchains.

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
| D4. Decide additional access syntax and grouping expectations | [D4 slice list](#d4-slice-list) |
| D5. Keep existing bounded semantics explicit | Non-Python mixed nested value reads, references escaping into movable slots and the sampled lexical comparisons of numeric-looking strings have existing boundaries. No evidence in this audit calls for changing boxed ownership, reference origins or comparison semantics. |
| A1. Consider preparing typed destinations once | Preparation records boxed roots. The emitter also calls `nested_collection_root` on typed stores, appends and mutations. This is duplicated classification, with incremental invalidation implications. The retained-owner and write-back probes did not reproduce another lost write. Treat a refactor as a separate engineering decision. [Preparation](../../compiler/04_analyze/prepare/prepare.php) · [Lowering](../../compiler/05_backend/cpp/emit.php) |

**Two historical gaps closed upstream during the audit.** PHP now prints scalar bytes/bools and reports the received type for a boxed echo operand. PHP kind predicates now admit dynamic values; `is_string` and `is_array` are available. New upstream coverage also exercises terminal checked vector/hash reads on nullsafe chains and nullsafe enum methods and read-only case properties. These changes are included in the integrated default-suite result. [QoS follow-up](php_flavor_developer_qos_reaudit.md) · [Nullsafe access contract](../conditional_expression_matrix.md)

## D4 slice list

| Slice | Status | PHP scope |
|---|---|---|
| Earlier work (unnumbered) | Done | Grouping and nullsafe chain boundaries, nullsafe owned-callable field reads, one declared field in braced interpolation, and computed string-key reads on dynamic. |
| Earlier writes (unnumbered) | Done | Single computed-name assignment, compounds and standalone updates on local dynamic, then plain two-key assignment with a computed name at either hop. |
| D4-W1 | Done | Nested append on local dynamic paths containing a computed name. |
| D4-W2 | Done | Nested compounds and standalone updates on those local paths. |
| D4-W3 | Done | Computed names anywhere in an arbitrary-depth local dynamic write path. |
| D4-W4 / D4-W5 | Done together | Assignment, append, compounds and standalone updates through stable dynamic instance fields. |
| D4-Q1 | Done | `??=` on a declared nullable instance field. |
| D4-Q2 | Done | One key on a local typed hash. |
| D4-Q3 | Done | One index on a local typed vector, with checked bounds. |
| D4-Q4 | Done | One key on a typed hash or vector instance field. |
| D4-Q5 | Done | Two keys through hash-of-vector or vector-of-hash on either root. |
| D4-Q6 | Done | Typed vector/hash paths at any depth on locals and writable instance fields. |
| D4-Q7 | Done | One bracket key on local `mixed` or `dynamic`. Null mixed initializes on a successful store. Null dynamic fails before fallback. |
| D4-Q8 | Done | Separate conditional entry probing from nested checked-slot access. Use the shared entry contract for typed and boxed stores, including one boxed bracket key on an instance field. |
| D4-Q9 | Done | Complete same-family boxed paths at any depth on locals and instance fields. Include brackets on both families and computed string names on dynamic. Both roots reuse the boxed path store and ownership rules. |

### PHP conditional assignment acceptance

The completed workstream covers PHP standalone statements on declared locals and admitted instance fields. Preserve previously admitted local forms in other flavors. Preparation validates the writable destination, key types and fallback conversion even if runtime presence will skip the fallback.

| Destination in this scope | Rule |
|---|---|
| Direct published nullable carrier | Existing carrier presence and ordinary assignment conversion. |
| Typed vector/hash path | Existing checked intermediates. Missing hash leaf selects fallback. Invalid vector position throws before fallback. |
| Same-family boxed path | Brackets on mixed and dynamic, also computed string names on dynamic. Missing or null leaf selects fallback. Existing intermediates must be containers. A null mixed root can initialize only for a single-key store. |
| All indexed paths | Retain a field holder, save keys once in source order, then probe. After a selected fallback and conversion succeed, resolve the saved path in current storage and store once. A throwing fallback or conversion produces no final store. |

Use existing destination access and readonly rules. Entry probing is independent of how a provider descends into a child or publishes an updated child. Mixed publishes changed children back through the path, while dynamic preserves shared child identity. Neither needs to expose an element address across user code.

| Acceptance evidence | Registered compiler tests |
|---|---|
| Direct carrier presence, assignment conversion and retained edits | `coalesce_assignment`, `coalesce_assignment_native`, `coalesce_field_native` |
| Typed paths, checked bounds, saved-key order, replacement and readonly recovery | `coalesce_hash_native`, `coalesce_vector_*`, `coalesce_nested_native`, `coalesce_deep_native`, `coalesce_hash_field_native`, `coalesce_vector_field_native` |
| Boxed hit, missing/null, falsey values, key evaluation and null receivers | `coalesce_boxed_mixed`, `coalesce_boxed_dynamic`, `coalesce_boxed_null_dynamic` |
| Holder rebinding, field replacement, mixed initialization, dynamic null failure, readonly edit/recovery | `coalesce_boxed_field_*` |
| Local and field paths, computed names, throwing fallback, bad intermediates, child replacement/removal, mixed snapshots and dynamic aliases | `coalesce_boxed_path_mixed`, `coalesce_boxed_path_dynamic` |
| Rejected destinations, computed-name types and invalid skipped fallback | `coalesce_scope` |

Test names above omit the `compiler_` prefix.

Park parameters (all modes), globals, static properties, new direct carrier families including bare boxed locals and optional owners, indexed paths crossing from typed to boxed storage within one chain, other indexed families, append targets, nullsafe targets, assignment expressions, for-header uses and new flavor syntax. Each requires a separate proposal to enter scope. Fixed arrays are owned by their separate workstream.

Other D4 decisions outside conditional assignment:

- Computed dynamic calls are deferred pending a use case.
- Named PHP static dynamic properties now support all four computed write forms. The `computed_static` fixture checks cross-file reads, replacement and rename recovery. Globals remain deferred.
- PHP nullsafe write targets are read-only. The `nullsafe_write` fixture checks the shared diagnostic, value-context reads and recovery to an explicitly guarded write.

Fixed arrays are tracked separately. General interpolation expressions remain deferred beyond the completed single-field slice.

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
  D4-W4 and D4-W5 exercise computed field stores, append, compounds and standalone updates.
  The original holder's destructor observes the new value after its last external handle is replaced during a key or RHS.
  A readonly edit permits shared payload updates, rejects field replacement, and recovers when reverted.
  The PHP interpolation fixtures verify declared string and integer fields, cross-file rename and recovery,
  and first-error ordering before echo emits the interpolated operand.
  At the audit baseline, `$h?->x=7` stopped in parsing (expected call statement). Indexed
  `$v[0] ??= 7` on a local integer vector now checks bounds and skips fallback for every existing element.
  PHP field `$h->x ??= 7` requires a published nullable carrier.
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

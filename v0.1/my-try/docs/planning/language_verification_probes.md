# Missing proofs and probe designs
Doc Status: planning

Prepared 2026-10-18. These **36 tasks are not 36 confirmed absent tests**.
One task addresses the known 39 unseeded matrix rows; most identify a required
composition or state grid whose complete assertion coverage has not been established.
An equivalent existing proof should be mapped and reused before adding a new test.
The async source fixture has already been located and needs replay, not reinvention.
No probes were executed or compiler/runtime fixes made during this inventory.

For each task, record existing proof found, new probe needed, intentional exclusion,
contract decision, or blocked prerequisite. A source spelling rejected by current
implementation is not automatically a language restriction. Conversely, future
CH15 ideas are not automatically compatibility requirements. Use the linked
[concrete checks](language_verification_checks.md) and their semantic contracts.

| Task / check | Probe design and independent observation | Gap / dependency |
| --- | --- | --- |
| LV01-P01 / LV01.003 | Create one malformed source with CRLF, a multibyte comment and a later bad token; check the declared byte/line convention, then repair incrementally. | No dedicated combined diagnostic-position probe mapped; byte tokenizer tests alone do not prove presentation. |
| LV02-P01 / LV02.003 | Exercise same local spelling in sibling blocks and a nested closure, then edit a declaration to a conflict; observe the intended bindings and rejection/repair. | Cross-scope binding matrix needs a dedicated mapping, not global declaration-count checks. |
| LV03-P01 / LV03.003 | For each admitted signedness/width pair, check min/max boundary conversion and result type through local, argument, field and return sites; record unsupported pairs explicitly. | Existing width suites are bounded; no per-pair/per-boundary ledger yet. |
| LV04-P01 / LV04.003 | Create an operand-family/operator grid including float comparisons, bool equality, unary/mutation symmetry, fixed-width, dynamic, wrappers, power, concat, spaceship, xor and word aliases; record valid/invalid policy before probing. | Full matrix omits families/operators and contains stale expectations; complete-grid admission is not assumed. |
| LV05-P01 / LV05.003 | Force one invalid boxed conversion at local, field, element, argument and return boundaries with an effect counter; verify which effects occur and that a rejected store preserves the destination. | Existing boundary tests do not provide one reconciled effect/failed-store grid. |
| LV06-P01 / LV06.003 | Make left conversion and right expression independently fail with distinct errors/counters; test eager arithmetic and comparison in both operand orders and lazy alternatives. | Need a mapped cross-family error-order oracle, not only success order. |
| LV07-P01 / LV07.003 | Enumerate empty/present nullable and success/error/false/true wrapper states on each selection side; assert selected value/type, RHS count and rejected result_or_bool coalesce. | Matrix first blockers mask later selection paths; probe behind blockers only in labelled adapters. |
| LV08-P01 / LV08.003 | Run original strict brace-free if/else and nested loop transfers with exact counter outputs; isolate downstream missing builtins in a separate probe. | S06 remains original-source evidence; positive runtime proof is missing for the blocked source shape. |
| LV09-P01 / LV09.003 | Inventory named/default/variadic/reference combinations; use an ordered trace to distinguish argument evaluation order from parameter binding, and reject invalid duplicates/mixing. | Named arguments are existing CH15 discussion debt; do not invent a contract or enable them during verification. |
| LV10-P01 / LV10.003 | For nullable/result/falseable/bool-able scalar and admitted container payloads, contrast wrapped false/zero/empty with each sentinel; take twice and inspect output/error reset. | Construction blockers and old bare error spelling prevent a full source-level state grid today. |
| LV11-P01 / LV11.003 | Copy nested mixed containers and alias dynamic containers, mutate one path, then destroy/replace an owner during a call; verify values and identity separately. | Existing proofs need explicit coverage of copy/lifetime compositions rather than treating all boxed families alike. |
| LV12-P01 / LV12.003 | Probe ordinary equality/order separately from strict identity, then interpolation/concat with empty/NUL/UTF-8 and scalar peers; compare byte values and conversion failure. | Strict identity proof does not establish ordinary comparison or every text boundary. |
| LV13-P01 / LV13.003 | Construct nested data with two side-effectful entries and a failing conversion; check entry/key order, duplicate-key policy and cleanup of already-created values. | No complete nested-construction failure-effect grid mapped. |
| LV14-P01 / LV14.003 | Use keys and RHS that replace the root or invalidate a parent; test vector/hash/mixed/dynamic paths and assert documented partial effects/owner retention. | Add only uncovered combinations after matching existing field/path fixture assertions. |
| LV15-P01 / LV15.003 | Turn the 39 unseeded empty_keyed/empty_value/count_value semantic rows into valid strict sources with exact bool/count and no-mutation observations. | Confirmed generator coverage hole from full matrix audit; no emitted cases for these rows. |
| LV16-P01 / LV16.003 | Remove/append/replace the iterated container through both direct binding and a called alias, including an empty source; classify permitted behavior versus required guard/rejection. | CH06-ITER-01 and CH06-REF-01 need contract/guard review; do not bless incidental iterator behavior. |
| LV17-P01 / LV17.003 | Forward a reference through a method/closure and selection boundary, then attempt return/escape of a temporary owner; verify write-through or a justified rejection. | Broader reference origins/lifetimes remain CH07 debt; negative acceptance must cite current restrictions. |
| LV18-P01 / LV18.003 | Transfer unique/shared/weak state across a call that throws before/after acquisition; observe destructor trace, weak expiry and no double release. | Need per-family transfer/exception interaction coverage, not source acceptance alone. |
| LV19-P01 / LV19.003 | During method/property RHS evaluation, replace the last owning receiver; check method result, postfix snapshot and destructor timing for supported receiver forms. | Extend only uncovered class/inline/temporary cases; method lifetime extensions are separate decisions. |
| LV20-P01 / LV20.003 | Inventory trait constructor/destructor conflicts, interface/static dispatch and unique/weak joins; use minimal source probes to identify first blocker and governing contract. | CH15 trait/inheritance extensions are not implied by existing inheritance success. |
| LV21-P01 / LV21.003 | Use two source units referring to one static property; mutate/read it and edit its initializer/type/import; verify storage identity and incremental equivalence. | Per-scenario static storage and rebuild mapping is still required. |
| LV22-P01 / LV22.003 | Inventory string backing, cases/from/tryFrom, methods/interfaces and wrapper payloads; compare promised v0.1 forms with documented v0.2 deferrals. | Broader enum API support cannot be inferred from enum_name/value/from_value tests. |
| LV23-P01 / LV23.003 | Copy a borrowing closure into a property/container or return it, contrast with an owning capture, and observe admitted cleanup or the required escape rejection. | CH15 receiver capture/callable defaults/erased targets remain separate discussion items. |
| LV24-P01 / LV24.003 | Cross finally with scalar/void/value/reference/owning returns and loop transfers; record the admitted set, exact cleanup trace and rejection stage. | Existing bounded finally successes do not cover the deferred return/transfer families. |
| LV25-P01 / LV25.003 | Change an alias/namespace/provider declaration in one file while a consumer body remains unchanged; check new binding, collision rejection and repaired fresh output. | Need a traced interaction mapping across source/runtime symbol shadowing and imports. |
| LV26-P01 / LV26.003 | Emit a successful operand followed by a failing conversion and a counter-producing third operand; compare prior bytes and skipped effects under the output contract. | Existing output success tests need explicit partial-output/failure ordering coverage mapping. |
| LV27-P01 / LV27.003 | Call one generic with two admissible types, invalidate one provider constraint, repair it and compare reused versus replaced instances and executable values. | Metadata/type counts alone are not specialization behavioral evidence. |
| LV28-P01 / LV28.003 | Locate strict v0.1 async fixtures and run the smallest matching v0.2 source without translating to callbacks; if blocked, record the stage and defer suspension probes behind it. | Strict manifest already includes tests/tools/test_scpp_async_core_language.php PHS nowdoc with stdout 4242. Missing evidence is current v0.2 execution, not a source fixture. |
| LV29-P01 / LV29.003 | For every public signature/type/constant/property in the metadata ledger, map one exact source use and declared failure/disabled-module behavior where applicable. | Name matches are candidates only; overload/default/reference and property semantics need assertion-level mapping. |
| LV30-P01 / LV30.003 | Round-trip empty object/array, numeric-key object, NUL string, malformed JSON and both mixed/dynamic decode entrypoints; distinguish approved shape changes from defects. | CH15 shape identity is a contract issue; do not silently change source fixtures to match incidental storage. |
| LV31-P01 / LV31.003 | Open/use/close a resource, trigger one declared IO failure and unwind; check return wrapper and release behavior with local fixtures. | Strict IO resource_handle source binding was blocked in S02 migration probes; needs a dedicated first-blocker check. |
| LV32-P01 / LV32.003 | Cross-reference public v0.1 APIs against eight current metadata modules; record absent source bindings, optional dependencies and internal-only helpers, then probe each settled supported signature. | Metadata inventory alone cannot discover missing modules; network/UI/database availability must remain explicit. |
| LV33-P01 / LV33.003 | Compile and run a minimal strict multi-project exported callable with one selected provider through the actual v0.2 CLI; test missing entry/provider and stale executable rejection. | Fixture compilers do not prove production build/run wiring; CH14 checked-reader debt remains separate. |
| LV34-P01 / LV34.003 | For each feature with retained facts, edit its defining type/signature/state, force rejection, repair, and compare executable values plus fresh output on the same snapshot. | Existing incremental tests are not a language-wide edit matrix. |
| LV35-P01 / LV35.003 | Refresh reference selection, freeze both checkouts, rerun the strict ledger after S01/S03 and keep S02/legacy/adapted lanes separate. | Historical 17-blocker totals predate S03; no new regression claim without fresh baseline proof. |
| LV36-P01 / LV36.003 | Run small strict programs combining JSON/container boundaries, callable captures, inheritance construction, and exception cleanup with independent exact output/trace oracles. | Component suite passes do not establish these complete source workflows; choose minimal scenarios rather than fixing applications. |

## Runtime declaration ledger

The JSON inventory lists **170 callable signatures, 14 types, nine properties and
five constants** across the eight installed metadata modules, each with its JSON
pointer, declaration and candidate test locations. Every declaration remains
unverified at the signature level. Multiple overloads sharing a name need separate
argument/default/reference and result checks; name occurrence is not coverage.

Searching top-level PHP tests alone initially missed JSON document APIs, file-lock
transfer and process members. Those names occur in fixture/Python sources and are
now mapped as candidates. `source_location` has no literal-name candidate in the
scanned my-try test/support sources; this is a type-use review task, not proof that
its inferred values are untested or that its API is absent.

Metadata only inventories registered symbols. LV32-P01 must compare against the
public v0.1/runtime/spec surface to catch **missing registrations and modules**.
External services, PCRE2 and platform dependencies need explicit blocker records.

## Known test and scope hazards

- `type_catalog.php` contains exact built-in/provider counts. Treat a mismatch as
  a possible stale oracle and inspect the semantic invariant; do not blindly edit
  expected counts or classify it as a language regression.
- The settled zero-division contract preserves `division_by_zero` for floats.
  `scalar_admission.py --ieee-zero` is a counterfactual historical probe and is
  excluded from the acceptance schedule.
- Legacy profile/syntax, JSS, LLVM, STAN and native-compiler parity remain deferred.
  Root runtime tests can inform runtime behavior but cannot prove source admission.
- `tests/tools/test_scpp_async_core_language.php` includes legacy-tool/JSS checks.
  Replay its exact PHS nowdoc through the strict manifest rather than executing
  the entire tool test as a v0.2 language proof.
- The 330 imported cards are routed, not individually certified. New v0.2 additions
  and CH15 debt outside that import require separate source-contract reconciliation.
  The 283 discovered my-try source/support files include helpers and transitive
  fixtures; unmapped files are explicit discovery work, not automatic exclusions.

## Before a verification finding becomes a fix

Preserve the first reproduction, identify the owning contract and failing layer,
then classify disposition independently from difficulty. Mark the four handling
queues from the plan; do not perform even an apparent simple compiler fix inside
the verification goal. Missing probes and harness defects are not compiler defects.

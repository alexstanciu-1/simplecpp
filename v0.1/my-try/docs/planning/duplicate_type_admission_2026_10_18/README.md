# Duplicate type declaration admission
Doc Status: supporting

2026-10-18: DR-03 semantic admission repaired under the agreed review decision.
`Declaration_Preparation::type_name` checks the exact published type-name pool
through `Observed_Lookup::declared_types`. Relationship and enum work observe pool
membership; trait admission rechecks each invocation. Classes, structs, interfaces,
traits and enums share the rule. Imports and language fallback are not additional
declarations. Collection identities and backend artifact guards remain intact.
The qualified name is included in the diagnostic; source spans remain DR-08.

The focused PHP fixture passes 26 local preparation rejections: every ordered pair
of the five declaration kinds, plus a reopened namespace. Five cross-file sequences
start from successful emission, add a duplicate in another file, reject and retry
without edits, rename to recover and compare exact artifacts with a fresh build.
Each then reintroduces the duplicate, retries, removes its file, and restores exact
baseline artifacts. Fresh cross-file duplicates also reject. Checks call preparation
directly and require that invalid inputs leave no published C++ artifacts.

Four valid generated programs compile, link and execute without PCH: distinct
namespaces, separate type/function names, source shadowing of a language type, and
repeated identical imports. Existing enums, namespaces, imports, traits, interfaces,
relationships and observed_lookup PHP suites pass. These are seven focused regression
suites, not a full compiler regression run or a native compiler rebuild.

```sh
MY_TRY_PHP_EXECUTOR=cli python3 compiler/my-try/tests/records.py --suite duplicate_types --no-pch --results FRESH_DIRECTORY
```

The initial automatic executor selected CLI after sandbox denial of the private
FPM socket; final checks explicitly reused that documented fallback. All checks used
the shared 12-slot runner. [Focused results](focused/summary.json),
[regressions](regressions/summary.json), [dependency checks](dependencies/summary.json),
command journals, nonempty output streams and [input hashes](provenance.json) are
retained. Journal paths refer to the original temporary execution directories;
generated programs and build artifacts are excluded from this evidence archive.

Other review issues and previously deferred files are unchanged. The deferred-file
hash baseline was verified before commit. The review inventory records this repair
separately from its original execution observations.

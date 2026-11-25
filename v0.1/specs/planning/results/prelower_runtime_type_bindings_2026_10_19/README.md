# Runtime-owned C++ type recipe migration
Doc Status: planning

Date: 2026-10-19. Base `ad7c2a09` plus this slice. The user approved the coordinated
binding extension before implementation.

Core now supplies representation bindings for existing scalars, boxed types,
nullable/Result and ownership types, and the value modifier. Semantic registration
and capabilities are unchanged. Ordinary runtime definitions still bind by their
existing declaration identity. The existing C++ binding owner retains resolved
native argument recipes; there is no parallel semantic registry.

The renderer uses those recipes for ordinary and composed native representations.
Fixed arguments, zero-argument template applications, reordered semantic arguments,
and stored/payload choices are explicit data. This removes the equivalent runtime
binding rows from PHP and the ownership-family test from native argument rendering.
Only void retains a compiler-installed native binding. Hosts using runtime types
must select the supplying catalog; there is no implicit PHP fallback.

## Evidence

- All 26 saved native spelling/header cases match the pre-change baseline exactly.
- Eight focused PHP suites plus that parity comparison pass: catalog composition,
  type declarations/protocols, canonical registration/capabilities, generic bindings,
  runtime catalog and result-container emission.
- `runtime_type_bindings.py` passes schema rejection, explicit demand, reset,
  reordered argument and closed-native-template proofs. Generated layout probes
  and concrete lifecycle operations compile/run at O0/O2 and ASan/UBSan.
- `prelower_substitution.py --mutations` passes in 192.37 seconds: ordinary/concrete
  O0/O2, sanitizer and controlled allocator measurements; demand removal/replay,
  configuration switching, body/layout edits and restoration. Mutation counters
  remain zero in all modes; stock O2 allocation delta is zero in this fixture.
  Earlier compound/iteration allocation-elision differences are not closed by this.
- The focused portability checker passes all six touched production PHP files;
  this does not establish native compiler portability. No native compiler rebuild.

All probes used the shared 12-slot runner, with PHP CLI fallback. No PCH was used
in generated-program proofs; sanitizer leak detection was disabled.

The exploratory `tests/model.php` run fails its fixed startup-count expectation on
both the working tree and an isolated archive of unchanged HEAD. It was not repaired
in this slice. Initial exploratory catalog/result-container invocations omitted
required output-directory arguments; corrected invocations pass. The first checker
run also caught unsupported nullable/container annotation forms, corrected in source.

Local evidence: `/tmp/prelower-binding-migration/check3`, `recipe-native`,
`native-mutations`, `portability`, and `headcheck`. [Compact summary](summary.json).
Reproduce native coverage with:

```bash
python3 compiler/my-try/tests/runtime_type_bindings.py --results FRESH_RECIPES
python3 compiler/my-try/tests/prelower_substitution.py --mutations --results FRESH_MUTATIONS
```

## Remaining work

Automatic preparation selection still has category checks for integers and applied
semantic templates. Next use the installed recipe to detect physical template
applications, including closed templates behind nominal source identities. Existing
explicit engine requests already use the recipes. Runtime trait/member interoperability
and accounting's output-conversion gap remain separate, incomplete work. Metadata
coverage is not complete for every runtime operation or private implementation type;
non-type template arguments remain outside this slice.

# Scalar catalog and entry-contract checkpoint
Doc Status: derived

Five compiler files plus two bounded JSON framework methods migrate the prototype's
scalar language catalog and complete authoritative entry return binding. All original
scalar schema forms and explicit capabilities are preserved; runtime package/resource/
structural extensions remain pending and reject rather than losing semantic fields.

116 independent PHP/native outcomes passed. The retained original Catalog_Syntax runs
against the same valid/invalid corpus, checked against independently expected shared
definition facts. Eight host assertions prove catalog/input purity, repair, shared
membership and qualified UTF-8 lookup. Full type-store/materialization/session tests
remain future components; the original schema parser itself is executed directly.

```sh
python3 compiler/tests/scalar_catalog/run.py --results /tmp/FRESH-catalog --target-checkout /tmp/scpp-json-240-probe
python3 tools/php_portability/validate.py --results /tmp/FRESH-catalog-fast
```

Native target verified clean before/after at exact commit
9b4b33f35f053b487e018c94d6a4a7888d77c64a, clang++-18, strict profile. Cumulative
PHP/tool validation passed with 53 registered source files; that fast run does not
request native proof itself. This component's native closure plus unchanged prior
stage proofs supplies cumulative evidence. The retained oracle/purity gates were added
after native completion and passed separately and in the final fast suite. Compiler
and framework sources did not change after the successful native proof.

Authoring through first PHP-ready: **309.988 s (5m10s)**. PHP-ready through native-ready:
**129.592 s (2m10s)**. Native build: **47.738 s**. One native build/run, zero native
corrective cycles. Two checker corrections preceded PHP-ready: nullable previous input
became constructor-owned state, and a computed isset key became a local. An unsupported
checker CLI option was corrected separately; it was not a source failure. A nullable
return helper avoids an unsupported named-wrapper local without changing meaning.

Framework additions expose checked JSON integers and booleans using existing target
APIs. Explicit tests cover signed 64-bit endpoints, overflow, fractions/exponents,
wrong kinds, false and zero. No converter or target code changed. Named capability and
lifetime objects remain authoritative. Exact complete bytes replace SHA-256 for catalog
content identity; no weak fingerprint or native exit-type assumption was introduced.

Timings include observed waits and overlapping oracle/documentation work, exclude
initial inspection and final commit, and are not additive active typing measurements.
Raw milestones/source hashes, framework hashes, provenance and cycle counts are saved.
Next: source name resolution. src-runtime-preparation remains PHP unchanged.

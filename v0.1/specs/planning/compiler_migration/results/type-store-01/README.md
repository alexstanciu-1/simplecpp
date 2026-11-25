# Canonical type store proof
Doc Status: derived

133 outcomes passed in PHP and strict native execution against clean
`9b4b33f35f053b487e018c94d6a4a7888d77c64a`. Coverage includes pending/declaration
states, shape interning, recursive references, passing modes, field projection,
semantic result production, definition replacement and candidate isolation.
20 host checks cover mutation/rejection invariants. Direct retained-store execution
confirms 13 expected facts and separately reproduces the signature-key omission.

Both native builds passed. The second validated final review's restored early
intern-hit checks; no native stabilization correction was needed. Signature keys
now include result production to avoid retaining an incompatible signature after
the return type's representation changes. No converter or target changes.

Commands:

```
python3 compiler/tests/type_store/run.py --results /tmp/scpp-type-store-native-02 --target-checkout /tmp/scpp-json-240-probe
python3 tools/php_portability/validate.py --results /tmp/scpp-type-store-final-fast-01
```

`summary.json` records exact target, source hashes and command times. `timing.json`
records wall-clock milestones. `cycles.json` separates the harness count correction,
pre-authoring capability probe and final allocation refinement. The old full session
suites were not run; the direct retained-store oracle is part of the current runner.
Richer array/resource/source definition producers remain dependencies, not placeholders.

Authoring through PHP-ready: **295.467s**. PHP-ready through final native-ready: **287.335s** (includes host/oracle work and the intern-hit refinement). Total through consolidation: **661.972s**. Orientation and the declaration capability probe preceded authoring-start.

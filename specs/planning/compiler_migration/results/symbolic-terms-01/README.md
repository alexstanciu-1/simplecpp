# Symbolic template data contracts
Doc Status: derived

40 PHP/native outcomes pass against clean immutable
`9b4b33f35f053b487e018c94d6a4a7888d77c64a`. Covers symbolic identities/dependency,
provider object identity, argument ownership, 1,000-deep iterative comparison, exact
task bindings, permission reuse/staleness and absence. 529 pair comparisons and 23
dependency flags agree with direct retained term/Terms::same execution.

```
python3 compiler/tests/symbolic_terms/run.py --results /tmp/scpp-terms-native-02 --target-checkout /tmp/scpp-json-240-probe
python3 tools/php_portability/validate.py --results /tmp/scpp-terms-final-fast-01
```

Both native builds passed. The first covered 30 outcomes; the second followed added
permission-result contracts and covered 40. No conversion/native correction. Time after
first PHP-ready therefore includes further authoring. Permission rows in the proof are
fixture producer results: the real checking worker, joins and provider interpretation
remain pending. Summary records exact hashes/revision/commands; timing and cycles record
measured work. No target change and no changes to src-runtime-preparation.

Two supplementary host checks independently invalidate changed declaration and binding
dependencies while retaining the same owner/catalog. Added after native proof without
changing compiled source/probe; see host-summary.json.

Authoring to first PHP-ready: 104.086s. First PHP-ready to final observed native-ready: 291.315s (includes permission-model authoring and second native proof). Total through consolidation: 497.754s.

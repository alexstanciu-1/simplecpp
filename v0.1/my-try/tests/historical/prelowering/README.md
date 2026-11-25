# Historical prelowering comparison harnesses
Doc Status: historical

These verbatim source snapshots were retained from `1810bc2d` when the handwritten
recipe and runtime-ABI emission paths were removed. `.txt` deliberately prevents
them being mistaken for runnable current tests. That revision already contained
superseded assumptions in these harnesses; it is their archival provenance, not
a claim that they passed at that revision. Dated result documents identify the
original proof boundaries.

No production branch is retained solely to execute these comparisons.

| Historical proof | Current proof or retained migration obligation |
|---|---|
| Integer/vector recipe lifecycle, mode restoration, demand retirement and failure/repair | `tests/fixtures/prelower_templates.php` now targets the one general engine; `tests/prelower_null_output.py` runs it |
| Recipe arithmetic/cast/error behavior and nested containers | General substitution binary/cast/initializer/indexing fixtures and accounting acceptance; old recipe-specific native names/timings are historical |
| Opaque expression order, nested mutation, references, payload allocation/copy counters | General `prelower_substitution` cases and O0 `prelower_header_boundaries.py` |
| Physical opaque lifecycle, native layout/cache and observation bindings | Live `prelower_layout.py --o0-only`; lower-level shared representation APIs remain useful independently of source-lowering mode |
| Source-method receiver replacement, clearing retained handles, default/reference/owned-result boundaries | Migrated to `tests/prelower_source_lifecycle.py`, using ordinary/general concrete emission and shared instrumentation |
| Constructor argument/default ordering and injected failure stages | Migrated to the same lifecycle proof, including five method/constructor copy-failure stages, ownership identity and method/layout edit checks |

Useful proof inputs are therefore preserved, but archival test success is not
current acceptance. The migrated proof retains behaviors, not the retired mode's
native-source partition, helper names, obsolete rejection gates or exact compile-unit
counts. Its O0 scope does not renew historical O2/sanitizer claims. Remaining work is in
the [solidification TODO](../../../../../specs/planning/prelower_templates_solidification_todo_2026_10_20.md).
Current commands are in [testing](../../../docs/testing.md).

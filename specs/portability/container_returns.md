# Container method returns and nonpublic scalar state
Doc Status: supporting

Ordinary class and directly expanded trait methods can now specify their native
container return type alongside an executable PHP array return:

```php
public function file_ids(): array /** vector<int> */ {
    return $this->ids;
}
```

Generated PHS uses `public function file_ids(): vector<int>`, with no type-comment
workaround. The shared recursive parser also accepts hash and nested-vector return
annotations. An unannotated `array` return remains an error; the converter does not
inspect return expressions or callers to guess its element/key types. Generic
array parameters and general nullable/union method types are not added here.

Private/protected initialized scalar fields now preserve their visibility through
the existing field parser. This enables the private entry-file ID used by Source_Set.
Existing type/default checks still apply. Visibility emission is not a claim of
complete target enforcement against unauthorized external access.

## Behavior and proof

The proof returns vectors, maps, nested vectors and vectors of shared row objects
from trait methods. It checks that scalar/container edits to a returned copy do not
change the owner's stored membership or values, while mutating an object element
still changes that shared object. It also exercises empty static returns and
public accessors over initialized private/protected scalar state.

Receiving locals carry explicit container annotations. v0.1.76 generated an invalid
`.get()` for chained nested indexing when these locals were left unannotated;
explicit local type syntax makes the proof compile. This resembles the failure
tracked in #232, but the new inferred-return-local case is retained separately and
has not been added to that issue. No type inference is added to our converter.

Native generic return syntax on an interface declaration is rejected by the
selected target before C++ generation (`unexpected token "<"`). The converter
therefore diagnoses container interface returns explicitly for this profile;
scalar/named interface returns remain available. Do not emit type comments in PHS
to evade that target restriction. Interface implementation support is still separate.

Run `tests/portability/container_returns.py --results FRESH_RESULTS_DIRECTORY`
with optional `--target-checkout TARGET_CHECKOUT`. The rejection runner is
`tests/portability/container_returns_rejections.py`.

[Evidence](../planning/compiler_migration/results/container-returns-01/summary.json)
retains the PHP/native proof and the two failed target shapes. The cumulative
fourteen-file compiler proof and sixteen retained compiler fixtures are rerun.
No Source_Set production changes are included: implements, explicit snapshot
copying and schema-preserving JSON export remain subsequent work.

Later update: [Source_Set](compiler_source_set_slice.md) now passes the cumulative
native proof, with explicit snapshot copies and fixed-schema JSON export.

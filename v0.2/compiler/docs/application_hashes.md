# Application-defined hash values

Doc Status: supporting

`hash<Value,Key>` accepts application structs and bare classes as values, with the
currently published strict `int`/`int64` and `string` key identities. Empty locals,
keyed writes, checked value-copy reads, local copies and by-value function/method
parameters/returns use the existing collection and call protocols in all flavors.
There is no key coercion, application-defined key, or new conversion matrix.

```php
struct Point { public int $x = 7; }
function copy_points(hash<Point,string> $points): hash<Point,string> {
    return $points;
}
$points hash<Point,string> = [];
$p Point;
$points["origin"] = $p;
$copy = copy_points($points);
$q = $copy["origin"];
$q->x = 9;
$copy["origin"] = $q; // The original still holds x = 7.
```

Struct values copy independently; class values and shared fields preserve identity.
A missing key throws `std::out_of_range("hash_missing_key")`. Empty construction
never invokes the value constructor. The common concrete recipe owns entry lifetime,
copy failure cleanup, holes and growth; generated application code uses no C++ table
templates and includes no private runtime header.

## Common ownership and publication

The nominal record owns one lazy collection cache keyed by family and canonical key
name. Generated registry dispatch admits available specializations and constructs
vector/hash definitions; there is no handwritten hash key list in preparation. The
existing nominal watches invalidate signatures and bodies when application types change.
No AST node, symbol observer index or per-expression prepared record was added.

Generated types implement `application_collection_type`, supplying a recipe name,
canonical type arguments and transient lowering substitutions. Hash currently emits
one handler per published supported key identity, reusing the runtime hash PHP handler
template. This selects key operations during generation rather than during preparation.

`Cpp_Concrete_Collections` handles both families' demand, signature forwards, includes,
recipe assembly and output paths. Recipe metadata owns application packaging and support
source selection. Type arguments use the common `ConcreteType` adapter; concrete runtime
assertions remain active. Runtime and application hash wrappers share method fragments.
The untyped hash index stays linked from the runtime archive.

All collection identities and artifacts remain generation/source-owned. Existing
publication joins, source-adapter manifests and cleanup retire unused specializations.
Key/value argument arrays exist only transiently during lowering, not on AST occurrences.

## Boundaries and verification

Nested collection values,
explicit shared/weak value spellings, enum values and application-defined keys remain
separate slices. Nullable-returning `find()` is deferred rather than generating a new
application carrier implicitly. Runtime-specialization behavior remains unchanged.

`application_hashes_native.py` reuses the vector by-value signature/recovery harness
for both key types and all three flavors with Clang/GCC: independent struct copies,
shared class identity, cross-file functions/methods, type/default/layout/signature edits,
deletion, retry/recovery, strict-key rejection, namespace changes and last-demand retirement.
Each revision is compared with fresh output; no-op invocations prepare/rebuild nothing.

### Verification checkpoint — 2026-11-18

Passed 138 hash revisions (23 per key/flavor), 60 vector regression revisions,
Clang/GCC executable checks, and separate ASan/UBSan/leak probes. Additional probes
cover growth, embedded-NUL keys, required element constructors, class upcasts,
copy independence, shared identity and vector/hash coexistence. Fourteen focused
generator, compiler, runtime and provider tests also passed.

Three fresh native builds per fixture used O2/g0, Ninja8, mold, no PCH/LTO and a
prebuilt libc++ runtime. These runs use direct Ninja qualification. Times below are
milliseconds; frontend and LLVM are cumulative across translation units, not wall time.

| Key / flavor | Native median (range) | Frontend median | LLVM median | Link median |
| --- | --- | --- | --- | --- |
| int / PHP | 383 (347–384) | 198 | 274 | 63 |
| int / C++ | 380 (345–468) | 221 | 298 | 58 |
| int / JS | 403 (385–425) | 226 | 300 | 62 |
| string / PHP | 423 (402–456) | 237 | 322 | 57 |
| string / C++ | 389 (383–395) | 221 | 295 | 59 |
| string / JS | 398 (378–402) | 229 | 291 | 54 |

Initial PHP compiler measurements ranged from 9.28–17.29 ms; no-op requests took
1.39–2.65 ms and prepared/rebuilt nothing. These are focused fixture observations,
not an application-wide speedup claim. Raw results for this run are retained under
`/tmp/scpp-app-hash-repeated-times/` and `/tmp/scpp-app-hashes-final/`.

## Instance fields — vector and hash

Both application collection families now support instance fields with implicit empty
initialization. For example, `struct Bag { public vector<Point> $items; public
hash<Point,string> $lookup; }`. Empty initialization does not construct any `Point`,
so the element may require constructor arguments. Explicit field `=[]` syntax remains
outside this slice, matching the existing published collection field syntax.

Whole-record copies and field assignment use the existing collection copy operations.
Inline element values copy independently; class elements and shared fields retain
identity. Existing writable local/parameter/this-rooted field paths support mutation;
this slice does not broaden writable receiver expressions or references.

The header publisher emits complete element declarations, the shared concrete recipe,
and finally the containing record layout. It also handles source-private records and
standalone emission. No AST nodes or retained preparation facts were added. The existing
layout traversal visits concrete arguments without requiring their default constructors.
Recursive concrete field dependencies are diagnosed as deferred, not as an infinite
source-language layout; supporting them needs split concrete declaration/implementation
publication. This limitation applies to both direct and mutual recursion.

`application_collection_fields_native.py` covers both families together, nested fields,
required element constructors, class identity, field mutation/assignment, growth,
standalone emission, cross-file edits, deletion/retry/recovery, deferred recursive
fields and namespace changes, comparing retained output with fresh builds in all flavors.

Field verification passed 19 incremental revisions per flavor with Clang/GCC and
fresh-output comparison, plus standalone native emission, artifact retirement and
ASan/UBSan/leak checks. The existing application-vector recovery matrix and focused
published collection, value-passing, generator parity and concrete-recipe checks passed.
Construction/nested-struct regressions passed after updating two stale expected
messages from "requires a struct" to the already-existing "requires a record".

The seven-source field fixture measured the following milliseconds (three fresh native
runs; same O2/g0, Ninja8, mold, no PCH/LTO, prebuilt runtime and direct qualification
configuration as above). Trace work is cumulative, not parallel wall time.

| Flavor | PHP compiler | Native median (range) | Frontend | LLVM | Link |
| --- | --- | --- | --- | --- | --- |
| PHP | 11.70 | 528 (526–533) | 422 | 396 | 63 |
| C++ | 12.95 | 534 (520–574) | 412 | 408 | 58 |
| JS | 11.80 | 521 (520–562) | 436 | 418 | 57 |

No-op PHP requests measured 2.41–2.76 ms with zero prepared bodies and native rebuilds.
These fixtures differ from the earlier signature-only fixtures, so this is not a
before/after performance comparison. Raw field results are under
`/tmp/app-collection-fields-final/` and `/tmp/app-collection-fields-timings/`.

## Application-defined traversal

Value/keyed foreach uses the common traversal protocol, including inside yield producers.
Required element constructors are supported: the concrete recipe provides an initially
empty output slot and copy-constructs the selected element only after a successful advance.
See [foreach output storage](foreach.md#construction-aware-output-storage).

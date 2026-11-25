# Flat vector initialization

Doc Status: supporting

Explicitly typed locals can initialize published integer and string vectors from flat
positional input in cpp/php/js. Empty input keeps its existing construction path.
Source authority: [data initialization](../../specs/data_initialization.md).
Scope and proofs: [planning checklist](../../specs/planning/collection_initialization.md).

The parser uses one `ast_collection_expression`, owning its expressions in source
order and one compact list of approved conversion codes. Empty input uses the same
node. There are no per-entry wrapper objects or extra dependency indexes. Preparation
propagates destination element types recursively through nested literals and checks
`sequence_initializer_type`; it does not branch on element families.

Runtime metadata declares `initialization.mode: ordered_elements`. Optional `bulk`
metadata publishes a read-only factory and an optional private consuming factory.
The generated capability selects bulk where eligible and existing append bindings
otherwise, including application/nested vectors. Removing `bulk` selects append;
removing the initialization contract disables populated construction. The generated type handler supplies one shared `initialization_transport` with its native element spelling,
factory spelling, concrete heap-input guard and inline limit, constructed with the
canonical type rather than lazily during emission. Bindings remain fixed
within a compiler session. Provider edits require regeneration and a fresh process;
application source changes retain normal incremental preparation and emission reuse.

Small input uses a local typed array. Larger input uses a typed heap array with a
concrete cleanup guard. Metadata sets a 4096-byte budget and conservative limits of
512 integer or 128 string elements; generated `static_assert`s enforce the byte bound.
This keeps temporary storage bounded without introducing C++ storage templates or
native-layout calculations in preparation. Native typed arrays supply alignment and
partial-construction cleanup.

Both factories presize the final element block before population. The consuming path
moves only private input elements, with nonthrowing moves guaranteed by the existing
concrete element contract. Source lvalues are copied into that input, never moved
from. The read-only path copies elements. Result storage cleans up on failure before
an existing destination can be replaced. The input view/backing never escapes.

A representative PHP-flavor local:

```php
$values vector<int> = [7, 7, 7];
```

Its C++ initializer (omitting the unused-variable attribute):

```cpp
scpp::vector_int64 var_values_0 = ([&]() {
    scpp::int64 input[]{7L, 7L, 7L};
    return scpp::vector_int64::from_owned(input, 3);
}());
```

The lambda scopes input lifetime around one factory invocation. The braced array
preserves source entry order, single evaluation and suppression of later entries
when evaluation/conversion fails. Runtime factories own allocation/population;
lowering emits no per-element append calls.

Boxed lists and keyed literals use their own published factories. See the
[construction rules](../../specs/data_initialization.md#construction-targets).
Bulk transport for application element types, user factories, move-only inputs
and provider hot reload remain deferred.

Focused checks are registered as `compiler_list_initialization`,
`compiler_list_initialization_native`, `compiler_list_initialization_ownership`, and
`list_initialization_providers`. Native proof scripts accept `--cxx g++`; the ownership
script accepts `--sanitize`. The [dev002 measurements](../../specs/planning/benchmarks/flat_vector_initialization/report.md)
separate clean timing, phase profiling and allocation/lifetime verification.

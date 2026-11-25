# Metadata-defined synchronous Iterator

Doc Status: supporting

`runtime/metadata/runtime.json` owns `compiler.interfaces.iterator`: the source name,
method name, one required type argument and an optional key argument, public mutable
instance receiver, bool result and required exact-type reference outputs. The metadata
`optional_key_parameter` maps type argument 1 to the second writable output.
The generator validates this bounded contract
and emits `types/interfaces.php`. Names are emitted directly; no descriptor parsing,
method-name search or interface allocation occurs while preparing a loop.

All flavors accept `struct Counter implements Iterator<int>`. PHP uses
`public function next(int &$value): bool`, C++ uses `bool next(int &value)`, and JS
uses `next(&value:int):bool`. This is Simple C++'s compiler-known protocol, not PHP's
standard-library Iterator signature. `Iterator` is reserved as a source type name.
The current contract requires a locally declared method. General interface declarations,
interface-typed storage and multiple/inherited contracts remain
deferred; this feature does not enable virtual dispatch.

```php
struct Counter implements Iterator<int> {
    public int $position = 0;
    public function next(int &$value): bool {
        if ($this->position >= 3) { return false; }
        $value = $this->position;
        $this->position++;
        return true;
    }
}
$cursor Counter;
foreach ($cursor as $value) { echo $value; }
```

The keyed form is `Iterator<Value,Key>` with value-first outputs:

```php
struct Records implements Iterator<string,int> {
    public int $position = 0;
    public function next(string &$value, int &$key): bool {
        if ($this->position >= 3) { return false; }
        $value = "record";
        $key = $this->position;
        $this->position++;
        return true;
    }
}
$records Records;
foreach ($records as $key => $value) { echo $key, ":", $value; }
```

Value-only foreach also accepts keyed iterators and provides temporary key storage.
The key is a fresh loop-local binding; only successful calls expose a key/value pair
inside the body. Keys are produced values, without uniqueness, ordering or lookup
requirements. No new AST fields, key-type cache or watcher index is retained: the
existing type-reference sibling and method parameter supply the optional key.

An iterator need not know an end. Each call produces an item and returns true,
returns false to finish this traversal, or throws. The output parameter is for
producing a value, not a promised incoming value/state channel. Existing foreach
value bindings are assigned only after true; writes made by next before false do
not overwrite them. A typed conversion also occurs only after a successful advance.

## Ownership and preparation

Structs keep ordinary field-copy semantics. Foreach borrows the selected struct;
resuming foreach over the same instance continues its current state. Copy explicitly
when independent state is wanted. Shared fields in a copied cursor keep ordinary
shared identity. Shared cursor receivers retain their allocation through the loop,
including when body code clears or replaces the original owner variable.

The struct AST adds one owned `interface_reference` using existing compact type
syntax. Its canonical definition retains one non-owning validated method link.
The element type is read from that method's already prepared parameter; no second
signature or element-type cache is stored. Contract validation follows ordinary
method signature preparation and precedes every body. The existing declaration
pass collects only implementing types into an ephemeral list; settled contracts
return immediately. Preparation performs no per-element or fixed-point analysis.

Type dependencies use the existing type watcher; foreach bodies watch the selected
method's stable symbol. Invalidating a type clears its method link. Ordinary signature
preparation handles parameter restrictions (including currently deferred inline-struct
reference parameters). Existing source arguments still use the ordinary binder;
compiler-created output storage binds directly to the already checked exact signature.

[Yield producers](yield.md) now reuse this consumption protocol with concrete move-only
cursors. That ownership rule is separate from copyable user-authored Iterator structs.

## Common consumption protocol

Types expose an optional iteration capability. Generated collection bindings emit
their own position/limit state; struct iterators need neither. The emitter retains
the receiver, initializes any protocol state, and emits native while/advance. Source
methods use the normal flattened function name and declaration/header publication.
There are no synthetic method ASTs, lambdas, runtime interface objects or vtables.
An existing custom-loop destination uses staging until next succeeds; exact-type
write-back moves the temporary instead of adding an unnecessary value copy.

## Verification

`tests/iterator.php` and `iterator_native.py` cover all flavors: copied and resumed
state, exhaustion, continue, string values, body-fed producers, shared-owner release,
and next writing output before false. Invalid modes/types/arity/access/static/return
contracts reject; keyed loops require the second type argument and output. Incremental edits, marker removal, deletion,
repeated failures and recovery match fresh native artifacts while preserving caller
ASTs. `tools/api_generator/tests/iterator_contract.py` verifies renamed metadata
changes only the generated contract file, and malformed contracts reject.

On 2026-11-18 all 27 native Iterator fixtures and all 135 existing collection foreach
fixtures passed. Provider relocation and PHP loop/contract checks passed. Six selected
shared-owner and false-output consumers passed ASan/UBSan/leak detection against the
instrumented runtime, using the previously documented libstdc++ sanitizer build.

Representative copy/resume fixture, single runs, O2/g0, libc++, Ninja8, mold,
no PCH/LTO, prebuilt runtime. Native wall includes the direct Ninja qualification-cache
subprocess. PHP compiler time is separate. Traces overlap; these are correctness-run
measurements, not an optimization comparison. The PHP fixture suite including
incremental/fresh checks took 178.73 ms.

| Flavor | PHP compiler ms | Native wall ms | Frontend ms | LLVM backend ms | Link ms |
| --- | ---: | ---: | ---: | ---: | ---: |
| C++ | 2.303 | 223.266 | 13.440 | 15.672 | 71 |
| PHP | 1.346 | 175.908 | 7.660 | 9.087 | 57 |
| JS | 1.469 | 178.088 | 8.298 | 8.273 | 61 |

Local artifacts and traces: `build/iterator-proof/` and `build/iterator-builtin-regression/`.

### Keyed contract verification

The keyed extension passed 63 native fixtures across all three flavors, including
value-only consumption, duplicate string keys, negative integer keys, shared receiver
release, false-output protection, copy/resume and cross-file incremental recovery.
Contract arity changes and successful string-to-int key changes reuse the caller AST;
invalid contracts, marker deletion and restoration agree with fresh compilation.
No-op runs prepare zero bodies. Existing built-in foreach and metadata checks pass.
No retained fields or indexes were added by this extension.

Single keyed-fixture runs below use the same native build settings described above.
PHP timings are from a separate compiler-only fixture run; the native test suite's
PHP fixture generation (including all incremental/fresh checks) took 393.26 ms.
These are observations under current machine load, not a before/after comparison.

| Flavor | PHP compiler ms | Native wall ms | Frontend ms | LLVM backend ms | Link ms |
| --- | ---: | ---: | ---: | ---: | ---: |
| C++ | 1.483 | 213.885 | 11.850 | 11.984 | 71 |
| PHP | 0.820 | 216.120 | 11.210 | 11.340 | 71 |
| JS | 1.535 | 309.702 | 18.021 | 20.277 | 97 |

Native artifacts/traces: `build/keyed-iterator-final/`.

## Common-record integration boundary

The shared class/struct AST and type definitions also retain the written Iterator
contract. Admission remains explicit on structs with their own public nonvirtual
next method. Classes, inherited/virtual next contracts and producer methods are
not enabled by the record-model merge. Signature preparation validates ordinary
method contracts before settling Iterator. No duplicate record or Iterator AST
hierarchy is introduced.

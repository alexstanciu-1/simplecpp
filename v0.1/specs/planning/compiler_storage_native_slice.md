# Compiler Storage toolchain integration
Doc Status: planning

Authority: `specs/compiler_storage.md`. This integration restores the strict PHS
Storage and Keyed_Storage bindings from PR #244 (`d8ddde93`) alongside the current
Key_Storage_List implementation. It does not merge unrelated historical features.

Storage wraps shared record handles with numeric tombstones and a live count.
Keyed_Storage keeps an exact string-key index and insertion order. Collection
assignment aliases membership; record handles retain identity after removal.
The keyed wrapper uses standard hash facilities; runtime hash allocation-failure
and memory/performance consolidation remain separate work.

## Reproducible checks

```sh
php tests/tools/test_scpp_compiler_storage_typing.php
python3 tests/tools/test_scpp_compiler_storage.py
```

These cover real strict PHS conversion, STAN, native build/execution, nested and
static collections, aliases, retained handles, numeric holes, exact string keys,
iteration order, cross-file returns, and invalid operations. The assertion helper
uses the supported debug-exit primitive; exception inheritance is outside this
fixture's scope and the historical namespaced Exception base does not lower on
this checkout.

Whole-compiler validation and remaining STAN findings are tracked in
`compiler/my-try/docs/portability/native_adaptations.md`. Successful collection
fixtures do not establish that the whole compiler builds natively.

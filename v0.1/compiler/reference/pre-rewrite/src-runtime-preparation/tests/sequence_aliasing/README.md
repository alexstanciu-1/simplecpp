# Native sequence append alias probe
Doc Status: supporting

Run from the repository root:

```sh
python3 src-runtime-preparation/tests/sequence_aliasing/run.py
```

`--config PATH` selects a preparation configuration. The runner uses its Clang,
standard, target and include directories. Three parallel jobs build O0, O1 and
O1 with AddressSanitizer/UBSan. Temporary build directories are removed afterward;
a JSON report is printed to stdout. LeakSanitizer is disabled because it cannot
inspect processes under the execution sandbox's ptrace; address/UB checks remain
active, and the fixture independently checks tracked-object lifetime balance.

`probe.cpp` calls the existing `scpp_provider::sequence_append` helper and actual
`scpp::vector_t`. Twelve cases combine scalar/managed witnesses, an independent
source/first-slot alias/last-slot alias, and spare capacity/forced growth. Growth
fills to the observed capacity rather than assuming a library growth factor.
The managed witness owns heap-backed text; its noexcept move changes the old
value. Checks cover original and appended values, actual growth, call copy/move
counts and zero remaining tracked objects after destruction.

No compiler or preparation-schema feature is implemented. This deliberately
investigates a wider native case than the isolated helper's documented distinct-
source contract. Old interior references are never used after growth. Source
language acceptance, prepared LLVM ABI traversal, mixed compiler-owned payloads,
move-append, callbacks that mutate the same container, exception recovery and
concurrent mutation are not proved here. See the
[findings and proposed metadata contract](../../../docs/details/provider_append_aliasing.md).

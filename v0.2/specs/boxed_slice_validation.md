# Boxed public API validation

Doc Status: supporting
Date: 2026-10-29
Contract: [boxed_slice.md](boxed_slice.md).

## Evidence

- `tests/boxed/consumer.cpp` links generated declarations/definitions to the private
  runtime. It proves null/default and exact scalar extraction, allocation-free
  scalar construction/copy, mixed COW, dynamic alias/nested identity, explicit
  scalar conversion, container rejection, byte/NUL strings, classified string keys,
  absent reads, key/append limits, clean/move/self-assignment and mutation failures.
  Assertions remain active in Release.
- `tests/boxed/boundary.cmake` preprocesses the standalone public include closure and
  rejects templates. Compiler rejection checks cover native arguments, implicit
  family crossings, traversal/subscript/truthiness, private storage and native extraction.
- `tests/boxed/publication.py` proves deterministic output/check mode, both single
  family publications, absence of the unrequested public class, conversion demand,
  dependency/policy/header/name rejection and no output on rejected preparation.
  Structurally invalid layout facts reject in preparation; valid-looking wrong
  sizes fail generated ABI assertions at C++ compilation.
- The native-probe integration proof now exports the actual layout header and
  changes it to prove context/cache invalidation. Layout is not inferred from PHP
  or a fixed capacity. Generated assertions also check kind discriminants.

Release checks cover all 45 CTest tests: the initial run passed 42; the three
integration failures were corrected and passed on focused rerun. Those failures
were isolated probe source dependencies and two input-fingerprint fixtures after
optional metadata registration. Existing expected artifact hashes were retained.
The expanded boxed publication checks also passed on final rerun.

`tests/build_cutover.py` then passed a fresh source-only export and its full 45-test
suite, with compiler/Cargo sources removed. It also passed unchanged-build stability,
metadata/publication/PHP/template/target invalidation, production builds with tests
disabled, datetime enable/disable demos and core/module symbol separation.

Focused libc++ UBSan passed boxed consumer, freshness and boundary tests (3/3).
A separate Clang ASan+UBSan generated-consumer executable passed with libstdc++;
leak detection was disabled, so this is not a leak-sanitizer proof.

The frozen G1 inputs now include the optional boxed metadata; only `input_sha256`
fields changed. Scalar projections exclude boxed metadata like other optional
families, and the full-metadata scalar fixture has its new input key. Every existing
expected artifact fingerprint remains unchanged, verified independently of reruns.

## Cost and scope

Generated mixed and dynamic are both 24 bytes on this target, equal to their private
values. Public scalar/default construction and copying allocate nothing; wrappers
add no per-value pimpl allocation. Layout facts and compile-time assertions, rather
than this observation, govern the ABI. String extraction/conversion copies and may
allocate; container mutation follows the private ownership/failure contracts.
No new throughput claim is made.

No operators, typed boxed collection/carrier compositions, source normalization,
compiler registration, JSON, native weak/shared adapters or public traversal were
added. Existing cycle/traversal debts remain. The core default publication still
omits boxed APIs; explicit demand is demonstrated by `tests/boxed/publication.json`.

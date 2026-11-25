# Linked AST native proof
Doc Status: planning

Normal STAN-enabled native build and incremental rebuild passed; 142 exact
PHP/native comparisons passed (48 valid, 94 rejected). All 48 valid emitted
programs compiled with Clang and executed with the expected exit code. The driver
checks repeat compilation and post-rejection recovery. It also traverses every
successful AST and checks child identity/order against named structure fields,
parent, previous/next sibling, first child and child ordinal in PHP and native.

Reproduce with a fresh results directory:

```sh
python3 compiler/my-try/tools/native_validate.py \
  --target-checkout /tmp/scpp-native-244 \
  --candidate-revision d8ddde93b04d0e23d295e30f662c3a81b0d50fd1 \
  --results compiler/my-try/build/native-linked-ast-04
```

Candidate retains the preceding portability/weak-scope toolchain fixes. No new
candidate patch was needed; the configured target pin is unchanged. Full logs,
commands and candidate file hashes are in that local results directory. Saved
source hashes identify the compiled snapshot (subsequent formatting/comments and
host-only assertions do not change its native behavior).

Initial attempts: 01 stopped at STAN for uninitialized child ordinal; 02 reached
C++ and exposed uint32 reads at signed map/argument boundaries; 03 stopped during
conversion because isset requires a simple key. Fixes were an explicit zero
uint32 default, explicit int conversions, and typed lookup locals. Attempt 04
passed. Advisory analysis remains: 210 errors, 78 warnings, zero blockers.

Additional PHP checks cover every concrete node kind produced by fixtures,
cycles, duplicate membership, attempted reparenting and unchanged graph on
rejection. The base is abstract in PHP. Native v0.1 emits a polymorphic C++ base
without enforcing abstractness when no pure virtual method exists; construction
uses concrete subclasses only. Named child lists are still retaining aliases.
No compact allocation, native expiration or destruction-depth claim is made.
See compiler/my-try/docs/ast_layout.md for layout and limits.

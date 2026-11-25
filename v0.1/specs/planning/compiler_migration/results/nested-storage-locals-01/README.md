# Explicit locals for nested Storage access
Doc Status: planning

Two-file PHP fixture: `tests/portability/fixtures/nested_storage/{records,main}.php`.
The record and collection property declarations are separate from the consuming
method. Both cases use the same candidate, with normal STAN enabled.

The explicitly typed locals compile and execute with the expected PHP trace:

```text
0:9:same
removed:9
```

This proves numeric append/read/removal, keyed assignment/read, aliasing of collection
membership, and shared record identity. Fresh locals loaded from the root observe
mutations made through the first locals. The emitted code uses native `.append`,
`.read`, `.assign` and `.unset` operations.

The direct-access variant removes only the first two typed collection locals and
uses `$root->rows` / `$root->names` at those operation sites. It passes STAN but fails
C++ compilation: lowering emits `root->rows->append` and ordinary subscripts. Thus
explicit receiver typing is sufficient for this reproduction; no STAN or S2S change
was necessary. This does not prove every nested collection expression in the compiler.

Reproduction: convert either two-file source tree with `tools/php_portability/convert.php`,
initialize a strict project whose entry is main.phs, enable the compiler runtime
module, select clang++-18, and run the recorded candidate with `scpp run --build-runtime`.
The measured attempts reused the candidate's existing runtime artifact instead.
For PHP, load my-try/boot.php, records.php, then main.php. Direct variant main.php,
both native logs and exact source fingerprints are saved here.

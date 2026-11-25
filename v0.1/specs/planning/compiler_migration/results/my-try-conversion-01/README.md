# Small compiler conversion-only inspection
Doc Status: derived

Date: 2026-09-24. No native generation, compilation or execution was performed.
Inputs: 26 unchanged PHP files from compiler/my-try/{01_prepare_inputs,02_tokenize,
03_parse,04_analyze,05_llvm,06_native,compile}. boot.php, main.php, test harnesses
and PHP Storage implementations were excluded as host/reference surfaces. This
selection is a probe, not a new compiler portability manifest or a no-export rule.

The production convert.php command against this source snapshot failed at
01_prepare_inputs/file.php:15 (clearstatcache) and left project-output empty.
A second diagnostic pass used the same project declaration index/trait expansion
and converter, catching errors per file. Its two successful emissions are saved
under partial/. These are inspection artifacts, not a runnable converted compiler.
The 24 rejected files each report their FIRST failure only. Fixing these may expose
further failures. Successful emission does not resolve named types or dependencies.

## Inspection

- 04_analyze/structures.php preserves five explicit maps, including integer token
  keys, named declaration values and the integer template-slot map.
- 06_native/structures.php preserves required process fields and build reference;
  execution becomes nullable<native_process_result> initialized to null.
- Several `expected =` / `expected parameter separator` diagnostics land on adjacent
  Storage<T> annotations; they are missing custom bindings, not instructions to
  add fake initializers or remove explicit element types.
- Tokenizer's first declaration failure is its private class constant. Static
  property support does not establish class-constant declaration support.
- Host filesystem calls need explicit adaptation. Byte-sensitive token/name logic
  needs byte helpers, not blind substitution of code-point q_strlen/string helpers.
- match, null coalescing, interpolated strings, the nonempty policy-map initializer,
  raw builtins and the host dbg/reporting boundary need separate review. No source
  semantics were changed or hidden to force a green conversion result.
- generate.php's diagnostic refers to expressions.php after actual trait expansion;
  it is not an isolated missing-trait artifact.

## Per-file results

| Source | Result |
| --- | --- |
| 01_prepare_inputs/file.php | 01_prepare_inputs/file.php:15: unsupported syntax: clearstatcache |
| 01_prepare_inputs/module.php | 01_prepare_inputs/module.php:19: unsupported syntax: scandir |
| 01_prepare_inputs/structures.php | 01_prepare_inputs/structures.php:32: expected = |
| 02_tokenize/structures.php | 02_tokenize/structures.php:30: expected = |
| 02_tokenize/tokens.php | 02_tokenize/tokens.php:11: expected explicit scalar or named property type |
| 03_parse/parser.php | 03_parse/parser.php:30: unsupported syntax: ?? |
| 03_parse/structures.php | 03_parse/structures.php:66: expected = |
| 03_parse/syntax.php | 03_parse/syntax.php:14: unsupported syntax: match |
| 04_analyze/collect/collect.php | 04_analyze/collect/collect.php:29: unsupported syntax: str_starts_with |
| 04_analyze/collect/structures.php | 04_analyze/collect/structures.php:67: expected = |
| 04_analyze/prepare.php | 04_analyze/prepare.php:22: unsupported syntax: array_merge |
| 04_analyze/structures.php | Converted; output inspected |
| 04_analyze/templates.php | 04_analyze/templates.php:19: expected parameter separator |
| 05_llvm/expressions.php | 05_llvm/expressions.php:15: unsupported syntax: ltrim |
| 05_llvm/functions.php | 05_llvm/functions.php:22: unsupported syntax: " |
| 05_llvm/generate.php | 05_llvm/expressions.php:15: unsupported syntax: ltrim |
| 05_llvm/names.php | 05_llvm/names.php:15: unsupported syntax: strlen |
| 05_llvm/prepare.php | 05_llvm/prepare.php:17: expected = |
| 05_llvm/statements.php | 05_llvm/statements.php:16: unsupported syntax: in_array |
| 05_llvm/structs.php | 05_llvm/structs.php:13: expected parameter separator |
| 05_llvm/structures.php | 05_llvm/structures.php:12: expected ] |
| 05_llvm/write.php | 05_llvm/write.php:14: unsupported syntax: array_merge |
| 06_native/run.php | 06_native/run.php:13: expected parameter separator |
| 06_native/structures.php | Converted; output inspected |
| compile/compile.php | compile/compile.php:26: unsupported syntax: dbg |
| compile/model.php | compile/model.php:17: expected = |

## Reproduction

From the repository root, create a fresh temporary probe directory with a source/
subdirectory. Copy only the seven directories listed above, preserving their paths
and selecting .php files. Run:

```sh
php tools/php_portability/convert.php PROBE/source PROBE/project-output --stats
php specs/planning/compiler_migration/results/my-try-conversion-01/probe.php PROBE
```

The first command is expected to reject. The second records each converter outcome
in PROBE/results.json and successful text in PROBE/partial/. It intentionally emits
partial diagnostic artifacts, unlike the production converter's atomic publication.
Run from the repository root; the driver uses the current local converter. Compare
results.json source hashes and tool_hashes.json before treating this checkpoint as
applicable to later edits. Per-file timings cover only conversion/expansion, not
source copying or total review time.

Next useful independent slice: define the host-only export boundary and review
byte-helper/source adaptations. Full Storage conversion remains dependent on native
source bindings; none of these files is registered conversion-ready by this probe.

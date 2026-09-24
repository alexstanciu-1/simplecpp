# LLVM formatting source-adaptation checkpoint
Doc Status: derived

Date: 2026-09-24. PHP behavior checks and conversion only; no native generation,
compilation or execution. No compiler files added to the conversion-ready manifest.

Scope: LLVM_Names, LLVM_Writer, and interpolation in LLVM_Functions. The host boot
now loads the existing portability framework; implementation code uses its fixed
global byte/count helpers. No converter or native runtime changes were needed.

LLVM_Names uses byte length/access/slicing and byte construction. Hex encoding and
strict suffix decoding use bounded ASCII operations instead of regex, sprintf,
ord/chr and raw string builtins. Its declaration pool has explicit map/list intent.
LLVM_Writer retains section/line order and joins through an explicit typed helper;
function emission uses concatenation instead of PHP interpolated strings.

## Validation

- All 38 PHP files linted.
- tests/storage.php, ast.php, model.php and llvm_text.php passed in host PHP.
- tests/llvm.php passed its generation/rejection checks; no generated program built.
- All 19 emitted LLVM files matched the prior initialization-audit outputs exactly.
- llvm_text.php checks every byte 0..255, combined roundtrips, Unicode/NUL input,
  reserved-looking source names, identity suffixes, malformed escapes/suffixes,
  empty sections, multi-parameter formatting and unterminated-block rejection.
- convert.php accepted the three edited LLVM files in an isolated source directory.
- Repeated the 26-file diagnostic pass with the checkpoint-01 probe: 5 accepted,
  21 first-diagnostic rejections (previously 2/24). results.json records source
  hashes, outcomes and timings; partial/ preserves the accepted generated text.

The functions trait is validated by conversion but emits no independent class;
LLVM_Generator still fails in another expanded trait. Accepted record/helper files
still depend on unresolved types/Storage bindings. These counts describe syntax
acceptance, not a runnable converted compiler or native correctness/performance.
No source changes were made to suppress the remaining diagnostics.

Reproduction uses the source-copy selection and probe.php described in
../my-try-conversion-01/README.md. For behavior, run the four PHP scripts above;
run tests/llvm.php with a fresh output directory to generate IR without compiling.
The normal tests/run.py includes llvm_text.php but also performs native work, so
it was deliberately not invoked for this no-compile checkpoint.

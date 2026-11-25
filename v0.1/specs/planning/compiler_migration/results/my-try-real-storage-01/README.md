# Real Storage conversion checkpoint
Doc Status: supporting

Conversion-only pass; no native compilation or toolchain pin changes.
`results.json` records first rejection per source from the real source projection,
without fake Storage. Source hashes include the current compiler and converter.

## Result

27 of 29 source files structurally accepted; the two rejections are instanceof in
03_parse/syntax.php and 05_llvm/prepare.php. Counts include two host class omissions
and three traits expanded into consumers. Complete atomic conversion rejects and
publishes no complete output. SplObjectStorage and implicit concrete AST payload
access still need native contracts even in structurally accepted files.

## Reproduce

From repository root, choose a new directory:

```sh
php compiler/my-try/tools/conversion_probe.php NEW_DIRECTORY
php tools/php_portability/convert.php NEW_DIRECTORY/source NEW_DIRECTORY/complete --stats
```

The first command deliberately records all first-error diagnostics and partial
inspection output. The second fails at syntax.php and does not publish a compiler.
Do not build the partial output.

## Validation

Passed PHP lint for all my-try PHP files, storage/tokenizer/AST/model/LLVM-text
behavior tests, and tests/llvm.php source-to-IR fixtures/rejections. All 19 resulting
LLVM outputs were byte-identical to the preceding fake-storage checkpoint. The PHP
host report renders tokens/AST/names/LLVM while Compiler::exec itself is silent;
native sample execution was not invoked.

Portability proofs passed: storage_bindings.php, compiler_syntax.php, run.py,
check.py, traits.py, required_fields.py, nullable_parameters.py, static_properties.py.
All were run without a native target/build option. git diff --check passed.

An initial tests/llvm.php attempt failed because its required parent output folder
was absent; rerunning with the folder created passed. A target-only AST narrowing
probe could not get beyond its input/signature preprocessing and is not evidence
for native instanceof support. No generated files were patched.

PHP behavior was rechecked throughout the adaptation; final source hashes are
recorded here. The per-file conversion probe has no timing instrumentation, so this
checkpoint makes no performance or native-attempt claims.

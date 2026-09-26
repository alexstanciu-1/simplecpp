# Constructor and invocation-lifetime checkpoint
Doc Status: planning

See [worker lifetimes](../../../../../compiler/my-try/docs/lifecycle/worker_lifetimes.md)
for the implemented ownership and reuse contract. This checkpoint follows
my-try-native-01, using the same exact PR #244 candidate and recorded overlay.
The global verified target remains unchanged.

All 29 production inputs plus the existing sample driver convert successfully.
PHP tokenizer, AST, model, LLVM and calls tests passed. The 19 LLVM fixture files
match the prior checkpoint; calls.php compiled/executed 28 sample programs with
expected results. These sample executions use the PHP compiler, not a native
compiler executable.

Normal candidate `scpp build` was attempted three times after conversion:
22 diagnostics after initial lifecycle extraction, then 6 after explicit local
typing of scope/struct index, then the same 6 on final verification. No STAN bypass
was used. Required-field initialization diagnostics are now zero. Remaining
return-path/enum-name failures stop the build before C++ compilation; prior C++
blockers have not thereby been resolved.

Reproduction: copy the seven production stage folders into a fresh input tree,
retain the sample driver from my-try-native-01, convert it, install framework with
`--filesystem`, and build with the recorded candidate and compiler/filesystem
runtime modules. Source fingerprints and final native analysis output are saved
beside this note. This is a lifecycle proof checkpoint, not native compiler parity.

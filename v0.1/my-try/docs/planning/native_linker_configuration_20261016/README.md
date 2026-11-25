# Native linker configuration evidence
Doc Status: supporting

See the [report](../native_linker_configuration_20261016.md) for scope, results and limitations. summary.json contains all three-trial native timings/scaling and 14 configuration cases. The two focused case files record PHP-hosted real-tool proofs for both native consumers. Native source hashes, implementation.patch, driver.php and provenance.json bind the rebuilt candidate and remote executable.

Archived scripts are experiment evidence, not installed/location-independent tools. They use the preceding accounting benchmark layout, adjacent repo/runtime, separate Git accounting fixture and PHP reference. Native driver rebuild uses the existing converted compiler project and rebuild_linker.py; source/main.php must be this archived driver, with the current 06_native sources copied into the native source tree. The build retains -O2 through the prior clang-o2 wrapper and --no-stan policy.

Remote sequence: mold_matrix.py, mold_heavy.py, verify_configuration.py, then mold_provenance.py. Fresh result/build directories are required. The heavy script restores the matrix's final batch; configuration verification then reuses that restored baseline. Use LD_LIBRARY_PATH=$PWD/lib for the copied native compiler runtime. No wrapper substitutes Mold: measure-clang records the production argv unchanged.

Durable local raw evidence:
`/home/alexv/__AI/simple_cpp/simple_cpp_01/sandbox/accounting-recovery-20261016/native-linker-integration-20261016/dev002-results/`

Remote workspace:
`alex-ai@dev002.travelfuse.ro:/home/alex-ai/accounting-native-linker-20261016/bench/`

Native compiler build logs:
`/home/alexv/__AI/simple_cpp/simple_cpp_01/sandbox/accounting-recovery-20261016/native-ninja-accounting-20261016/compiler-logs-linker-config/`

Focused real-tool logs:
`/home/alexv/__AI/simple_cpp/simple_cpp_01/sandbox/accounting-recovery-20261016/linker-config-proof-2/`
The initial proof directory preserves a corrected test expectation (five cold objects: two application + three provider objects); the implementation did not require a repair after that attempt.

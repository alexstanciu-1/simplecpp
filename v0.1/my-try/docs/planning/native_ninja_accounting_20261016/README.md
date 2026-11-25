# Native Ninja accounting evidence
Doc Status: supporting

`summary.json` contains all nine measured runs and the independently selected
median-total representative for each scenario. `provenance.json` records the exact
native source hashes, compiler binary hash, host, toolchain and build settings.
`driver.php` is the authored PHP benchmark entrypoint compiled into the native binary.
The Python/Clang wrapper files are archived verbatim from the durable experiment;
they are evidence scripts, not installed CLI tools or location-independent entrypoints.

Original workspace:
`/home/alexv/__AI/simple_cpp/simple_cpp_01/sandbox/accounting-recovery-20261016/native-ninja-accounting-20261016/`

Its parent contains `repo/` and `recovered/`; the recovered fixture lives in
`recovered/accounting-large-20261010/accounting_512k/`. To reproduce in another
location, preserve this layout and use a fresh experiment directory. The fixture is
528,088 bytes across 93 PHS files and is kept in its own Git repository during runs.

Sequence: `build_compiler.py`, PHP reference emission using the checkout's
`compiler/my-try/main.php --s2s accounting --entry main.phs --output php-published`,
`benchmark.py`, `profile_body.py`, `repeat.py`, then `validate_final.py`.
The original `compiler-logs*`, `reference-logs`, `measurements*`, `body-trace` and
`validation` directories retain commands, failures, per-Clang timings, sampled RSS,
stdout/stderr and traces. The earlier failed native-publication attempt is separately
retained under `measurements-failed-native-1` and `published-failed-native-1`.
Each benchmark restores the authored body edit through Git in `finally`.

The compiler rebuild uses a Clang wrapper that replaces `-O0`/`-O3` with `-O2`,
retaining existing debug information and other flags; it does not patch generated
C++ or the repository build system. Both the compiler and Ninja build worker execute
natively. Application builds use `-O0`, the shared runtime PCH and GNU ld.

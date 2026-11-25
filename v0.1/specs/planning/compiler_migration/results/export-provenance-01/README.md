# Source-export project/backend provenance
Doc Status: planning

279 PHP/native outcomes agree with the retained records on acceptance. Project
identity remains explicit under relocation. Lexical POSIX root normalization rejects
relative roots, parent traversal and NUL while preserving arbitrary other bytes.
Every appended byte from 0 through 255 is exercised; native accepted path bytes are
read back as integers. Backend/target/layout/ABI/runtime keys remain required and
CPU/features may be empty. All fields preserve exact supplied strings.

Both source files remain in their prototype ownership directories. Construction
establishes no filesystem existence or backend capability proof. Runtime preparation
is untouched. Tagged export identities, accepted layouts and source export binding
remain separate dependencies.

Strict clang++-18 target: clean
`9b4b33f35f053b487e018c94d6a4a7888d77c64a`. First PHP and native attempts passed;
zero correction cycles. Saved summaries contain commands, timings and source hashes.

Run `python3 compiler/tests/export_provenance/run.py --results FRESH` with optional
`--target-checkout /tmp/scpp-json-240-probe`; cumulative validation uses
`python3 tools/php_portability/validate.py --results FRESH`.

Timing limitation: the initial timestamp begins at file writing, after drafting.
The full authoring interval is unknown and is not reported as the short write/test
duration. Native stabilization and command durations remain measured.

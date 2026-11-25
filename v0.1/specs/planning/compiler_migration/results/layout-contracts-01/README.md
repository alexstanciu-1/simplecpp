# Accepted layout and dependency records
Doc Status: planning

34 PHP/native outcomes: 13 snapshot/provenance/error checks, four integer-predicate
checks and 17 measured-layout cases compared with the retained constructor. Six
additional PHP-only cases reject malformed offset/field carriers before publication.
The native strict target is clean clang++-18 at
`9b4b33f35f053b487e018c94d6a4a7888d77c64a`; first build passed without corrections.

The initial 30-case PHP checkpoint preceded review of integer carrier checking.
`q_is_int` now delegates to existing PHP/native predicates through the function map;
no target implementation or converter grammar changed. Its global facade is generated
with `php tools/php_portability/generate_global_functions.php`. Final PHP/native
checks exercise the restored explicit check.

The five records preserve batch-owned container membership and exact immutable
object sharing. Bounds include empty records, mismatched cardinality, negative/
duplicate/descending/out-of-range offsets, misalignment and very large powers.
Task/result records retain selection provenance; the measuring worker and acceptance
join are not implemented by this slice.

Run `python3 compiler/tests/layout_contracts/run.py --results FRESH` with optional
`--target-checkout /tmp/scpp-json-240-probe`; cumulative validation uses
`python3 tools/php_portability/validate.py --results FRESH`. Exact commands, source
hashes and timing boundaries are in the saved summaries.

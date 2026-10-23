# Complete nullable-array annotation checkpoint
Doc Status: planning

CONVERT-02 reuses structural container parsing and emits the existing nullable
wrapper once. Source uses `?array $items /** ?vector<int> */ = null`.

Evidence: `/tmp/nullable-annotations-03` passes declaration/mismatch checks for both
converter targets and PHP/current legacy native execution (absent, present empty,
constructor input, ordinary-method input and return). Both v0.2 modes still reject
with `S2S nullable value boundary requires a scalar or inline record payload`.
That is a blocker reproduction, not successful native v0.2 execution. Upgrade
`tests/portability/nullable_container_annotations.py` when NULL-01/02 are implemented.

`/tmp/nullable-conversion-scan-02` passes conversion of 255 compiler files and four
driver/proof files for both targets, plus empty-annotation and nullable-field
regression/non-publication checks. Of 259 v0.2 PHS outputs, 258 are byte-identical
to the last measured bootstrap. The sole difference is the previously committed
prepared-data-family explanatory comment; the exact replacement was checked.
The annotation migration itself leaves emitted compiler code unchanged. This is
not a new bootstrap measurement; the latest run remains 198.90 s / 432.3 MiB RSS,
422.5 MiB PHP heap, stopping on AST-01. Full backlog completion remains unproved.

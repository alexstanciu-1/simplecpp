# Package physical measurement ingestion
Doc Status: planning

One migrated source file adds 106 PHP/native outcomes and 106 retained importer
acceptance comparisons. Native target: clean strict clang++-18 checkout at
`9b4b33f35f053b487e018c94d6a4a7888d77c64a`. First native build passed with no production correction cycle. A final verification
build follows a rejection-probe hardening: rejected inputs skip value comparisons,
so invalid fixture fields cannot mask unexpected acceptance.
The first portable PHP checkpoint passed before a missing oracle include was fixed.

Coverage: six type kinds, exact identity, alignment powers/divisibility, integer
width and signedness, absent versus false, fractional/string rejection and very
large positive measurements. Physical metadata produces a measurement record,
not an accepted semantic type. No provider permissions, catalog definitions or
source export ownership are inferred. Unexposed void size behavior matches the
prototype; exposed void still needs the later binding-specific zero check.

Commands: `python3 compiler/tests/package_measurements/run.py --results FRESH`
and the same command with `--target-checkout /tmp/scpp-json-240-probe`.
Cumulative check: `python3 tools/php_portability/validate.py --results FRESH`.

`cases.json` holds independent expected values; the probe validates measurements
and rejection while the oracle invokes the actual retained Package_Types importer.
Summaries carry command timings and source hashes. This proves physical ingestion
only; full package type exposure/ownership acceptance remains pending.

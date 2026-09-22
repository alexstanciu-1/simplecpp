# Physical ABI and source-export contracts
Doc Status: planning

219 PHP/native outcomes: a 198-case capability/state/operation matrix, six exact
role-semantic profiles and 15 ABI/task/export association checks. The first 204
cases compare directly with retained source capability/role code. Imported operations
remain invalid as source implementations; absent support cannot carry a callable.
Move roles retain their deferred status with no copy substitution.

Record checks prove separate implementation/import targets, copied container
membership, exact shared project/layout/identity/capability associations, extension
validation, integer adaptation names and missing/unavailable-operation rejection.
Physical export preparation and join acceptance are not implemented here. The final
source_linkage record awaits its runtime-input and analyzed-verification owners.

Strict clang++-18 target: clean
`9b4b33f35f053b487e018c94d6a4a7888d77c64a`. First native build passed with zero
correction cycles. Two checker fixes precede first passing PHP: a reserved local
and an unsupported string class constant. A redundant target invocation stopped
at the same checker error, before build; it is saved separately.

Run `python3 compiler/tests/source_export_contracts/run.py --results FRESH` with
optional `--target-checkout /tmp/scpp-json-240-probe`; cumulative validation uses
`python3 tools/php_portability/validate.py --results FRESH`. Summaries record commands,
source hashes, timings and failed attempts.

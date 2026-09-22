# Package type composition and source payload binding
Doc Status: planning

38 PHP/native cases, each with independently expected acceptance, plus retained
prototype acceptance comparison. Successful paths check catalog/native/source
identity and physical storage kind; every case checks binding membership and
accepted owners remain unchanged. Cases cover all three owners together, compiler
name override, duplicate operation/type IDs, unknown bindings, conflicting owners,
source identity/layout/lifecycle corruption, nullable forbidden metadata, unexposed
resource validation and failures after earlier rows succeeded.

The first native build passes on clean pinned target
9b4b33f35f053b487e018c94d6a4a7888d77c64a with strict clang++-18. No native correction
cycles. Two PHP fixture corrections preceded the first success: use Json_View.key
and the complete parameter-reference constructor; use the actual nested source key
and producer language-name object schema. Failed attempts are preserved.

This proves type-map composition, not a fully accepted runtime package, receipt
authorization or actual source export generation. Those still require their owning
coordinator/protocol steps. Existing leaf exposure/import/layout proofs remain
registered in cumulative validation.

Run: python3 compiler/tests/package_type_map/run.py --results FRESH
--target-checkout /tmp/scpp-json-240-probe. Cumulative fast validation:
python3 tools/php_portability/validate.py --results FRESH.

Cumulative fast validation passes with 120 registered production files. This run
executes every registered PHP stage plus tooling regressions; focused native
evidence for the new component is recorded separately in proof/summary.json.

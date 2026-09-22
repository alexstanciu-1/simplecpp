# Accepted native type imports
Doc Status: planning

40 PHP/native outcomes agree with the retained package importer. Coverage includes
provider/type identity, exact definition reuse, storage mismatch, absent language
definitions, generic missing-capability diagnostics, C++ copy traits, empty lifecycle
metadata and forbidden competing markers. The test differentiates local package ID
from accepted foreign type ID. Provenance fields are retained; target compatibility
is still the enclosing package coordinator's responsibility.

The retained comparison rejected an initial assumption that a null resource marker
was absent. The implementation and expected value were corrected before native
compilation. `initial-expectations` is preserved as failed comparison evidence;
`first-php` is the corrected behavioral checkpoint.

Strict clang++-18 target: clean
`9b4b33f35f053b487e018c94d6a4a7888d77c64a`. First native build passed with no
corrective cycle. Timings and command/source hashes are saved.

Run `python3 compiler/tests/native_type_import/run.py --results FRESH` with optional
`--target-checkout /tmp/scpp-json-240-probe`; cumulative validation uses
`python3 tools/php_portability/validate.py --results FRESH`.

This proves the accepted native-owner branch, not source-export ownership or the
complete package type map, package retention, checksums or lease lifetime.

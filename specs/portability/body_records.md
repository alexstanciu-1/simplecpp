# Typed body value and statement records
Doc Status: supporting

`check_bodies/data/values.php` owns the nine checked value forms and their concrete
payloads. `Typed_Value` retains source/type IDs and a checked integer tag. Named
payload accessors replace the prototype's broad PHP union: exact decimal text,
producing call ID, shared place, conversion, operation or byte literal. Default
record/construction values carry no payload. Constructor checks prevent mismatched
or extra payloads; accessors reject the wrong kind.

`Conversion_Value` retains its positive input ID and the integer-widen operation.
`Operation_Value` retains ordered left/right IDs and the exact type-model operation
contract. The completed-body owner must still validate earlier operand IDs,
canonical types, call ranges and source provenance. These records do not claim
that a body is checked merely because construction succeeded.

`Byte_Literal::to_json()` explicitly emits the prototype's binary-safe hex object,
including embedded NUL and bytes outside UTF-8. This replaces JsonSerializable
reflection; future debug consumers must call the explicit projection. Raw bytes
stay unchanged and shared for checking/lowering.

`data/statements.php` owns calls, arguments, statements and scope ranges. A call's
zero result ID still represents void. Arguments retain canonical passing modes;
statement constructor acceptance preserves return/write/destination and scope
invariants. `write_kind` and `return_mode` replace the old enum fields (including
the PHP property named return). Statement call segments stay zero-based, while
value/local IDs remain one-based. Contextual range validation remains downstream.

The tagged carrier uses ordinary named classes and optional payload references,
not a native union. This is an explicit first representation, with compact tagged
storage deferred to the optimization pass. Exact payload objects remain shared;
no repeated per-consumer reconstruction is required. Short-lived worker cursors,
conversion selection, completed body snapshots and evaluation ordering remain
separate migration work.

`compiler/tests/body_records` proves all nine payload forms, exact decimal text,
all 256 byte values, identity, wrong-access/extra-payload rejection, void calls,
argument modes and empty scopes. It also exhausts 1,000 statement-mode/destination/
value/scope combinations against independent expectations and the original
prototype. Native fixture scope corrections are recorded separately from
production changes and actual C++ builds.

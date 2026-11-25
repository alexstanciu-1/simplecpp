# Resource locations and canonical endpoints
Doc Status: supporting

Resource_Location owns copied static field-path membership. Root descriptors keep
the original local-ID key; nested keys retain colon/dot boundaries. An element or
array index stops descriptor projection before the dynamic component. Overlap is
same-local ancestor equality, independent of element index and unrelated siblings.

Resource_Locations discovers descriptor roots and accepted record resource leaves
from the migrated Resource_Obligations owner. Parameter_Resources replaces the
prototype parameter-to-nested-array result with explicit ordered parameter rows;
Parameter_Endpoint and Distinct_Endpoints replace destructured tuple results. Future
ownership consumers must use these records, retaining zero-based parameter positions
and one-based checked-local IDs. No symbol/type resolution is added to the converter.

Canonical endpoint parsing uses a bounded byte scanner instead of regex, explode,
array_map and integer round-trip conversion. Decimal components reject signs, leading
zeros, empty components, non-ASCII digits, separators and signed-64-bit overflow.
The maximum accepted ordinal remains 9223372036854775807; digit subtraction occurs
before accumulation to avoid a transient overflow. Pair ordering is byte lexical,
not PHP numeric-string comparison. Callers pass explicit string paths rather than
string|int unions and must normalize numeric PHP hash keys at that boundary.
Projection validates its canonical path input instead of coercing malformed text;
location constructors reject nonpositive local IDs/negative field ordinals. These
are internal producer constraints, not new language syntax. Diagnostic construction
returns the existing attributed diagnostic record for the consuming worker, replacing
the old direct Source_Error throw shortcut; full stage failure handling remains to wire.

491 PHP/native outcomes pass. 477 compare directly with preserved project/overlap/
endpoint/pair helpers. Seven explicit place-prefix cases cover dynamic projection
stopping; seven checked-body inventories prove root descriptors, scalar exclusion,
parameter positions and nested record paths 0/0.0. The nested cases materialize real
storage/record contracts before body checking. Copies preserve original path membership,
and body-derived diagnostics retain path/span/reason. Allocation safety, alias-effect
application and complete ownership flow are still separate dependencies.

Run `python3 compiler/tests/resource_locations/run.py --results FRESH --target-checkout TARGET`.
Evidence: `specs/planning/compiler_migration/results/resource-locations-01`.

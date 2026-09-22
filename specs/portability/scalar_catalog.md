# Scalar catalog and authoritative entry binding
Doc Status: supporting

Five compiler files migrate the prototype's version-1 scalar language catalog:
Named_Definition, Type_Catalog, Catalog_Syntax, Language_Types, and Entry_Contract.
They cover exactly the loader's void/integer/floating definition forms, not every
extended named definition that runtime packages or later source composition can supply.

Definitions retain exact name/namespace, representation, optional lifetime and
signedness, integer-family identity, wrapping-addition/ordered-comparison permissions
and structural-field eligibility. Empty internal family means absence; the JSON schema
rejects an explicitly empty family. Only the documented operation modes are accepted.
Nonintegers cannot acquire integer permissions; void cannot have a lifetime, and
value types require one. No language meaning is inferred from a type's spelling.
Unsupported structural/resource/native-layout forms reject until their real owners
are migrated, rather than being silently stripped into scalar definitions.

Catalogs index exact byte-length-prefixed qualified names and retain shared definition
identity. Literal and entry defaults must be exact catalog integer objects, not equal
copies or definitions from another snapshot. Optional boolean binding requires an
unsigned one-bit integer. Zero is not a missing value; unsigned false is not absent
signedness. Rows preserve declaration order without allocating canonical type IDs.

Catalog_Syntax validates the complete schema using immutable JSON views. Unknown or
missing fields, wrong object/list/scalar kinds, malformed definitions, unsupported
policies and bad defaults reject. The scalar lifetime schema still permits only
value/unavailable copy and no executable cleanup; integer zero construction and
ordinary assignment/expiring-value policies follow the original loader's rules.

Language_Types binds the previous catalog in its constructor and reads an explicit
path. Equal bytes reuse the exact previous catalog; changed bytes build a new private
one. Errors leave the previous catalog intact. The content key is a prefix plus the
complete source content, replacing the prototype SHA-256 digest without weakening
identity. This is a measured-optimization candidate, not a persisted-cache compatibility
promise. No implicit sample/default catalog path or session shell is installed.

Entry_Contract binds an accepted Entry_Selection to the exact catalog entry_return_type.
It validates file-entry and integer categories. Changing the catalog's entry type can
preserve the source symbol while replacing the return definition. This is language
meaning only: no native exit width/ABI, signature materialization or body checking is
inferred. The previous source-selection component remains useful as its first step.

## Concrete framework addition

Json_View now supplies integer() and boolean(). Integer extraction requires an integer
JSON spelling in signed 64-bit range; fractions, exponents, overflow, booleans, strings
and containers reject. PHP checks its decoded integer carrier before returning it;
native uses the existing checked json_node_int operation. Boolean extraction requires
actual JSON true/false and uses json_node_boolean natively. Both preserve false and
zero. Raw numeric tokens and generic serialization remain outside this schema adapter.
These are runtime facade additions, not converter inference or new target functionality.

## Evidence

116 independent PHP/native outcomes include schema failures, numeric/boolean boundary
cases, equal/changed reads, default identity, old-snapshot preservation and typed entry
binding. The retained original catalog parser runs on the same valid/invalid corpus
and is checked against independently expected definition facts. Eight host assertions
prove catalog/policy/list purity, repair reuse and UTF-8-qualified lookup.
See [timings and provenance](../planning/compiler_migration/results/scalar-catalog-01/README.md).

Next: source name resolution, following the prototype's analysis order. Canonical type
storage/materialization, provider packages, record/resource extensions, signatures,
bodies and the compiler session remain future components. src-runtime-preparation stays
PHP unchanged.

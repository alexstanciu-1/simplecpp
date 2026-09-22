# Source declaration lookup
Doc Status: supporting

`resolve_symbols` owns lookup roles and immutable occurrence targets. Type lookup
selects an exact scalar catalog definition before a source struct; value lookup
selects project constants; family lookup selects source template structs. Global
function lookup uses source bytes and excludes methods. Missing names return null
(or zero for function IDs); invalid roles and non-name callee nodes are errors.
Lexical parameters and block constants will precede this global lookup in the worker.

`Name_Binding` replaces the prototype's union payload with a checked numeric ID and
nullable exact catalog definition. Only one payload is active. Template positions
are zero-based; other numeric targets are positive. Comparison ignores occurrence
location but preserves role, target kind and exact provider object identity. These
are declaration identities, not canonical type IDs. No type inference occurs here.

The active source grammar is global, so no unused namespace argument is exposed.
Provider records/families await their real catalog owner; they are not represented
by scalar definitions. Lexical resolution, template argument checking and reuse
selection are subsequent components, not established by this lookup proof.

40 PHP/native outcomes cover lookup separation, absence, method exclusion, exact
catalog identity, template position zero and invalid target contracts. A retained
prototype oracle checks 15 numeric constructor cases. Native passed on the first
build against `9b4b33f35f053b487e018c94d6a4a7888d77c64a`.
See [evidence](../planning/compiler_migration/results/name-lookup-01/README.md).

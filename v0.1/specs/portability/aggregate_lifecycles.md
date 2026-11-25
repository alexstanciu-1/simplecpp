# Aggregate lifecycle composition
Doc Status: supporting

`resolve_types\Lifecycle_Composition` owns aggregate permissions and constituent plans.
It consumes declared canonical type identities, authoritative member lifetime contracts,
a repeat count and a compact `Lifecycle_Bodies` row. It does not execute bodies or infer
ABI, native layout, resource paths or syntax bindings.

Unavailable member permissions dominate automatic composition. Custom copy requires
field default construction; custom assignment owns its field updates. User copy,
assignment or destruction suppresses implicit move. Automatic moves select copy for
members whose expiring contract requests it. Destruction visits nontrivial fields in
reverse order. Fixed-array repetition stays compact. Complete primitive record exports
are available for default/copy/assignment/destruction; no move implementation is invented.
Imported operations retain their identity and are not exported as source bodies.

Named_Definition represents general values, independently checking lifetime, integer
capabilities and inline field eligibility. The current JSON catalog remains scalar-only. Type_Cache and Record_Definitions now
materialize normalized record/array recipes and retain ownership/layout metadata.
Typed storage-family provider integration remains pending.

Proof: 36 shared PHP/native outcomes and eight PHP-only mutation/domain checks.
See [evidence](../planning/compiler_migration/results/aggregate-lifecycles-01/README.md).

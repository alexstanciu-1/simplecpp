# Simplified native Storage delivery brief
Doc Status: planning

This local contract supersedes the earlier owner/view and hook-heavy design.
Issue #242 / PR #243 have not yet been updated for this simplification. Their
published API and tests are historical inputs, not the current compiler requirement.

Implement Storage<T> as a numeric shared object list and Keyed_Storage<T> as a
string-keyed shared object collection. T names the record; elements
are shared_p<T>, without double wrapping. Container assignment aliases its shared
state. append/read/replacement preserve record identity. Old handles survive removal
and replacement; fresh reads select current membership. Null records reject.

Current surface: constructor(capacity=0), append(record)->position, replace(position,
record), remove(position), reserve(capacity), count(), is_empty(), [], isset/unset,
and ordered iteration. Capacity is a constructor/runtime argument, never a template.
Positions start at zero, are monotonically assigned, preserve holes and never shift
or reuse after removal. Invalid reads/replacements/removes reject without insertion;
unset of absence is a no-op. Count means live members. Mutation during traversal is
unsupported. Preserve wrong-key and overflow rejection. Native allocation failure
must leave a valid container; no application hooks or poisoned-owner protocol is needed.

No Storage_View, read-only mode, internal_* methods, key-mode flag, key-position
map, secondary-index hooks or generic ownership-policy arguments are required now.
Dedicated compiler lookup indexes remain with their process/data owners. Do not
implement serialization, automatic weakrefs/cycle collection or optimized inline
record layouts in this slice. Existing richer native helpers may remain separate,
but do not force their machinery into compiler authoring.

Converter binding should recognize explicit Storage<T> and Keyed_Storage<T>
annotations and capacity-only
construction; fluent reads and field edits operate on shared handles. Prove object
identity, repeated membership, replacement/removal, holes, growth, iteration and
validation with PHP/native comparisons. References: STORAGE.md, tests/storage.php,
tests/ast.php and tests/model.php. PHP compiler regressions do not establish native
Storage binding. Native internals can be specialized later while preserving the
observable object-list contract, or revising it explicitly with evidence.


## Shared base and separate keyed type

The PHP surface now has Storage_Abstract with Storage<T> and Keyed_Storage<T>
subclasses. Reuse common object validation, reads, replacement/removal, traversal,
count/is_empty and reserve logic; keep numeric append versus unique string-key
rules in their concrete classes. Native internals need not copy PHP key encoding.

Keyed_Storage has add(key, record) with duplicate rejection, [] keyed insertion or
replacement, and common operations by string key. No numeric-position access or
keyless append. Preserve exact string keys and insertion order; replacement keeps
order, removal/reinsertion moves to the end. Both types hold shared_p<T> objects.
The native issue needs this updated contract before integration. Current compiler
uses include named struct types/fields, external targets and the instance registry.
Sparse integer indexes, scope overload pools and scalar arrays retain their existing
typed representation; see [collection inventory](../MODEL.md#collection-choices-during-llvm-preparation).

Keyed parity proofs must cover empty, numeric-looking and embedded-NUL string keys;
wrong key types; duplicate add; insert/replace/remove; first-insertion ordering; and
shared identity after replacement/removal. Native code must not depend on PHP's
internal key prefix. Both concrete types accept capacity=0 by default and reject
negative capacity. reserve is a hint and never changes membership. Storage_Abstract
owns common behavior in PHP; it is not instantiated by application code.

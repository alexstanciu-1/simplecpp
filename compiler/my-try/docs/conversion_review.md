# Model conversion review
Doc Status: planning

## Current status

The compiler runs in PHP; its generated programs run through Clang. The compiler
itself is not yet proved convertible. [Model](../MODEL.md),
[collections](../helpers/STORAGE.md), and [ownership](ownership.md) describe the
current source contract. Earlier diagnostics and proposals are retained in the
[historical checkpoints](conversion_review_history.md); their
[source-hashed evidence](conversion_review_evidence.json) does not describe the
current source tree.

| Area | Status / remaining work |
| --- | --- |
| Static Model fields | Declaration and literal/self access have focused PHP/native proof; see [static fields](../../../specs/portability/static_properties.md). Preserve shared roots and reset behavior. |
| Required fields | Omitted initializers are supported; assign before reading/publication. See [required fields](../../../specs/portability/required_fields.md). STAN may require constructor assignment rather than a separate populate method. |
| Nullable parameters/reset | Explicit nullable scalar/named method parameters and null defaults are supported; see [nullable parameters](../../../specs/portability/nullable_parameters.md). file.tokens now initializes/resets to null. The wider nullability audit remains pending. |
| Collection bindings | Bind Storage<T> and Keyed_Storage<T>, including typed declarations, capacity-only construction, access, mutation, count and iteration. No boolean template policies or views remain. |
| Native object identity | Start with automatic shared_p<T> elements. Reads alias records; replacement/removal preserve previously retrieved handles. Ownership tags do not implement weak references or cycle reclamation. |
| Remaining PHP boundaries | Review object-identity maps, nullsafe access, policy-map initialization, payload narrowing and host APIs individually. Historical checker failures are not a complete current support matrix. |

## Next conversion steps

1. Reconcile the native helper delivery with the [current brief](../helpers/STORAGE_NATIVE_TASK.md),
   then prove both collection bindings. The shared PHP abstract class is code reuse;
   native implementation need not copy PHP internals or string-key encoding.
2. Convert one source/token component and compare tokens, byte spans, errors and
   stage reset behavior between PHP and native. Keep host loading/reporting outside
   converted source. Do not claim compact native layout from PHP measurements.
3. Continue through direct AST children, scope/occurrence indexes and LLVM
   preparation. Keep sparse declaration/token indexes distinct from append positions.

Preserve shared graph identity, first-use external-target order, source/AST purity,
old results after worker reuse, and independent output operands. Native short-circuit
and byte-helper rules need explicit review during adaptation. No new compiler
features, rollback machinery or inline-layout redesign is part of this work.

The latest collection migration passed PHP lint for 36 files, 19 LLVM fixture
executions, 28 call executions and sample exit 9. Preparation reuse/shared-target
identity also passed. These are PHP compiler regressions, not native compiler proof.

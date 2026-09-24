# Native layout debt
Doc Status: historical

Superseded by the [current model](../MODEL.md) and [collection API](../helpers/STORAGE.md).
Views, parallel AST registries and inline-row lifetime machinery are not current
requirements. The text below records earlier exploration only; references to removed
helpers/tests and “current” behavior describe that historical checkpoint.

## AST child membership and traversal

Status: deferred until native implementation and measurement.

The implemented PHP ownership boundary is one AST node store per parsed_file,
plus file-local stores for concrete specialization payloads. Child collections
are currently position-list views into that node store. Logical AST relationships must not
force an individually allocated native object tree or one allocated child array
per node.

Compare these representations when native measurements are available:

- Ordered child-position collections/views as a straightforward baseline.
- Links such as first_child and next_sibling stored with the records.
- A compact parent_or_first encoding if its traversal rules justify the complexity.
- File-level child-reference buffers with ranges, where appropriate.

Unresolved decisions include the exact link fields and absent-value encoding,
reset/forward traversal, whole-subtree traversal using parent links versus a stack,
child-index lookup complexity, and append/link maintenance costs. No link encoding
or additional AST fields are selected now. Linked child views may need linear
ordinal access even when direct node-position lookup is constant-time.

Use representative native workloads with many small blocks/calls, wide child
lists and deep nesting. Compare total bytes (including view/container overhead),
allocation counts, construction cost, reset/forward and subtree traversal, ordinal
lookup where used, and destruction/replacement cost. Include required auxiliary
stacks, tail positions and indexes in the comparison. Preserve traversal order and
compiler output across candidates. Choose from measured memory/CPU tradeoffs.

PHP remains the small-sample logical-behavior reference. Its object/array memory
layout and execution timings do not establish native layout quality. Do not spend
this slice optimizing PHP or use PHP measurements to choose the native encoding.
The initial Storage/view helper split must not depend on resolving this debt.

## Agreed fluent operation to preserve

The proposed view operation is:

```php
$position = $children->storage_append($node);
```

It inserts the actual record into the backing Storage, includes it in the view,
and returns the Storage position. internal_append(position) can include an existing
record. These helper operations are implemented in Storage_View_Abstract; AST adoption
is implemented with position-list views; linked membership remains deferred.

This fluent surface should survive the later child-layout choice. For linked
views, inclusion will maintain traversal links rather than a per-view position
array. A failed combined operation must not be reported as success; consistency
and failure handling must be defined when implementing the helper, not postponed
as a performance experiment. Automatic rollback is not implied.

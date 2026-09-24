# Native data representation and bulk processing
Doc Status: historical

Superseded by the [current model](../MODEL.md) and [collection API](../helpers/STORAGE.md).
Views, parallel AST registries and inline-row lifetime machinery are not current
requirements. The text below records earlier exploration only; references to removed
helpers/tests and “current” behavior describe that historical checkpoint.

## Latest decision: shared records for the initial port

Default native Storage record representation is now `shared_p<T>`, with T still
the public record type in Storage/Storage_View annotations. Reads return shared
handles; field edits preserve identity; retrieved handles survive replacement or
removal. Views continue selecting current owner positions. This supersedes the
inline-row/bounded-reference direction below for the first integration; per-file
segmentation and the owner/view split remain. Inline storage/layout optimization
is future work. Shared ownership does not solve cycles; deliberate cleanup remains
necessary. The native worker was asked to update its contract and PR, not the PHP
helper. [Published request](https://github.com/alexstanciu-1/simplecpp/issues/242#issuecomment-5812738509).

## Agreed direction

PHP is the behavioral development surface. We use small samples to check compiler
logic; PHP allocation cost and execution speed are not the optimization target.
The native compiler must be fast and memory efficient. PHP object handles must
not automatically become separately allocated native objects or pointer arrays.

Separate the logical data model from its physical representation. For each record
and relationship, establish whether it needs value storage, owned nested data,
a borrowed reference, a stable logical reference, or shared lifetime. PHP's use
of an object does not answer those questions.

This is preparation for stabilizing and converting the existing compiler, not
permission to add new compiler features, a serializer or a generic ownership system.

## Distinctions to make explicitly

| Concept | Meaning for native design |
| --- | --- |
| Inline/value record | Data held directly in a collection or owner; no independent per-row allocation required. |
| Owned nested data | The parent controls a segment's lifetime, such as one file's token buffer. |
| Borrowed reference/view | Access to existing data without ownership; validity follows an explicit owner/mutation boundary. |
| Stable logical reference | Identifies a record through its owner and position/offset, even if physical storage moves. |
| Shared lifetime | Independently retained users genuinely need the same record to survive removal or replacement. Use only where the model requires it. |

These are design categories, not newly supported PHP annotations. Choose concrete
converter/runtime spelling only after inspecting the supported type and lifetime
contracts. Do not equate a stored position with a raw memory address.

Collection membership permissions are independent of all these choices.
Storage_View's ReadOnly mode does not imply a pointer layout, immutable records, shared
ownership or a snapshot. Explicit internal mutations still maintain its indexes.

## Why contiguous records matter

A contiguous buffer of records enables sequential access, fewer allocations,
better locality and bulk transfers of suitable data. A contiguous buffer of
pointers is only contiguous in its pointers; processing record fields still needs
indirection, and separately allocated records may be scattered in memory.

Pointer-based graphs can still be serialized or processed efficiently using
appropriate techniques. They do not, however, provide the same direct bulk-copy
path as a flat buffer of suitable records. Avoid importing that cost from PHP
without a real identity/lifetime requirement.

Candidates should be evaluated by actual access patterns. Dense row storage,
segmented buffers or separate field arrays may each be appropriate; no universal
layout is prescribed. Do not add per-record IDs when owner plus position already
provides identity. Keep local file/segment bounds rather than flattening everything
into one global record pool.

## Serialization and deserialization implications

Inline records alone do not make a memory dump a valid persistence format.
Strings, nested containers, pointers, padding, alignment and representation/version
differences require deliberate handling. Native strings are not automatically
inline serialized text; dumping a string object's bytes does not store its content.

A useful future direction is flat record sections plus variable-size byte/data
sections, with explicit offsets or owner-local positions for relationships.
For example, token records can describe spans into one retained source buffer
instead of each owning text. Loading can then allocate/copy sections in batches
and rebuild lookup indexes separately where appropriate.

Only a deliberately specified, compatible fixed-layout section can use direct
bulk encoding/decoding. Validate bounds, format version and references before use.
Persist logical relationships, never process addresses. Preserve historical holes
and the position limit where stable Storage identities require them. Do not claim
zero-copy loading or a portable format before proving their constraints.

## First review candidates in the existing model

- token: offset/length are natural inline-record candidates. Retained token text
  is currently a PHP convenience; the existing source-span idea could remove
  per-token native strings. Source-buffer lifetime must cover span access.
- token_list: a file-owned buffer is a logical allocation and replacement boundary.
  A reference to the buffer does not require one shared allocation per token.
- AST: classify node headers, specialization payloads and child collections.
  Existing PHP node links may become owner-local references if their lifetime and
  mutation semantics permit it. Polymorphic payloads are not automatically flat.
- collected_name: distinguish occurrence rows from scope/name indexes pointing to
  them. Stable positions can express identity without requiring scattered rows.
- module/file and cross-file links: review actual shared lifetime needs; do not
  force the same representation chosen for high-volume tokens onto these owners.

These are candidates, not implemented layout changes. Record-field edits through
PHP aliases must be reviewed: some express required shared behavior; others are
incidental to PHP. Preserve required behavior explicitly and adapt the authoring
code when value/borrowed semantics are chosen. Do not silently change observable
aliasing during conversion.

## Interaction with the existing native Storage request

Issue #242's published contract currently preserves shared record handles,
including handles retained after replacement/removal. That is stronger than some
inline/value designs need. It must not be interpreted as the final ownership
policy for every compiler record.

Before assigning native representations, reconcile each collection's required
identity and lifetime with that contract. Inline storage with bounded borrows and
shared handles surviving removal are different contracts. If the chosen model
requires a change, update the native request explicitly; do not silently implement
value copies beneath a shared-identity API. No new template parameter or ownership
framework is selected by this note. These notes were added to [issue #242](https://github.com/alexstanciu-1/simplecpp/issues/242#issuecomment-5810647782). The [agreed ownership direction](native_ownership_plan.md) now specifies per-file
node/payload stores and non-retaining row links. Updating the native helper contract
and proving invalidation remain explicit delivery debt.

## Review checklist for the conversion pass

For each high-volume record/collection, document:

1. Owner and lifetime boundary, including readers that outlive replacement.
2. Inline data versus variable-size payloads and reference fields.
3. Required aliasing, mutation and reference-invalidation behavior.
4. Index representation and the operations that maintain it.
5. Expected bulk operations and possible serialized section boundaries.
6. Native size, allocations, bytes per record and traversal/transfer measurements.

PHP tests prove logical behavior. Native tests and measurements prove layout,
lifetimes and performance. Keep those claims separate.

## Deferred layout choices

[Native layout debt](native_layout_debt.md) tracks AST child-list versus linked
traversal representations. Resolve these with native memory/CPU measurements;
keep the initial Storage/view helper split independent of the final encoding.

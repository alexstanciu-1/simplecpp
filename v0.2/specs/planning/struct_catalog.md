# Struct coverage in the v0.2 PHP bootstrap compiler

Doc Status: planning

Inventory as of 2026-11-13, scoped to records before inheritance. Priorities are proposals. The donor's Chapter 10 includes capabilities from a different compiler. Its completion labels do not establish bootstrap support.

## Working baseline

- Nominal inline records, nested inline fields, shared and weak fields, recursive handle edges, memberwise copying, weak locking and checked empty-owner access.
- One public or private constructor with required by-value parameters. Inline and shared construction use the ordinary argument binder. Access checks include implicit initialization of embedded fields. An empty shared field does not construct its payload.
- Public/private instance and static fields and methods, typed literal member constants, and lexical declaring-type qualification. Methods share ordinary supported argument conversions, trailing by-value literal defaults, writable exact-type parameters and return handling. Methods returning their enclosing record can copy the receiver by value.
- Plain field writes, the five arithmetic property compounds where admitted, and discarded prefix/postfix updates. Property updates also work in the final clause of a braced for loop. These operations preserve access checks and have native and incremental proofs.

## Suggested core delivery order

| Slice | Current boundary | Deliverable and acceptance proof |
| --- | --- | --- |
| 1. Numeric and boolean instance fields | Implemented for int/int64, bool, byte/uint8 and float/float64. | Verified zero defaults, nested copies and incremental type changes. Native float proofs cover target layout and signed zero. |
| 2. String instance fields | Implemented through the scalar field capability. String append and indexed byte writes use the ordinary typed operations. Static string properties share one definition across units. | Verify empty initialization, short and heap-backed nested copies, cross-file headers, destruction and incremental type edits. |
| 3. Inline struct parameters and returns | Implemented for free functions and ordinary methods. Signatures watch the nominal record and expose its layout to cross-unit callers. | Resolve ordinary nominal value parameters and returns in free functions and methods, including a different record as the return type. Test cross-file copy-in/return, shared member identity, layout changes and missing-type recovery. Test a caller that only sees the record through a signature. |
| 4. Typed literal instance-field defaults | Implemented with the shared scalar literal parser and typed-destination conversion. | Retain the initializer once on the declaration; validate even unused fields. Initialize each new value before the constructor body, and copy the current field value when copying a record. Test default edit/removal across units. |

## Independent follow-ups

| Area | Remaining boundary |
| --- | --- |
| Other uses of an inline value | Exact-type writable record parameters should use the ordinary named-local/parameter binder when enabled. Bare struct `this` currently works as a receiver or direct return; local copies and by-value arguments using bare `this` remain separate cases. |
| Constructor and method argument coverage | Constructors accept required by-value parameters only. Evaluate trailing literal defaults first, including whether an all-default constructor satisfies embedded default initialization. Reference modes for constructors require a separate decision. Read-only references, reference returns, variadics, named arguments and overloads belong with their common callable contracts. |
| Defaults and constants | Member constants and static property initializers are typed literals. Instance defaults from named constants would require watching the defining declaration. Inferred field types, nested defaults, and static properties with omitted initializers are distinct extensions. |
| Property operations | Property compounds cover arithmetic and flavor-specific string concatenation; other locally admitted compound groups still need property coverage. Updates and compounds are discarded-value operations. Expression-valued prefix/postfix, compound results and additional property forms in for headers need distinct tests. |
| Field syntax and lookup | PHP instance declarations currently use type then variable; the alternative variable-then-type form from the broader spec is pending. Type imports and expression-qualified static access remain separate from existing explicit type names and lexical qualifiers. |
| Containers and carriers | Vector/hash as fields, field-rooted keyed operations, application records as collection elements, and nullable/Result of an application record are different integrations. Coordinate with the collection owner. Shared/weak fields already work. |
| Data records | Explicit data-record opt-in, keyed initialization, synthesized public-property inputs and property-backed constructor parameters. Keyed input must respect private construction and authored constructor invariants. |
| Lifecycle | Native cleanup of supported fields already runs. Source-authored destructors, their access rules, ordering and nonthrowing contract are a separate lifecycle feature. |

## Boundaries and references

Keep late-bound static dispatch, protected inheritance access, parent lookup, overriding and interface dispatch with the inheritance discussion. Unique ownership and cross-thread shared counts have their own ownership contracts. Writable references directly into fields remain intentionally rejected. Value equality and hashing require a language contract; scalar field equality alone does not supply one.

Implementation anchors: [field layout](../../compiler/types/struct.php), [signature lookup](../../compiler/types/resolution.php), [member parser](../../compiler/03_parse/structs.php), [body preparation](../../compiler/04_analyze/prepare/prepare.php), [loop header parser](../../compiler/03_parse/parser.php). Semantic and donor references: [compact layout](../../../specs/compact_layout_types.md), [instance methods](../../../specs/instance_methods_v0_2.md), [static members](../../../specs/static_members_v0_2.md), [data initialization](../../../specs/data_initialization.md), [destructors](../../../specs/destructors_v0_2.md), [donor catalog](../../../compiler/my-try/docs/catalog/10_objects.md).

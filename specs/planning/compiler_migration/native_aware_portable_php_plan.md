# Native-aware convertible PHP work list
Doc Status: planning

Status: planning only. The user requested this list before implementation.
No new syntax, runtime behavior or skill capability is introduced by this document.
The stage-replacement alternative remains saved in [the migration plan](README.md#saved-option-stage-by-stage-replacement).

## Objective and limits

Keep executable PHP as the authoring surface while making native storage, copying,
aliasing and lookup choices visible. Aim for a few reusable conventions and concrete
proofs, not a complete PHP compatibility language, ownership checker or optimization
framework. The converter remains structural and does not resolve program symbols.
Do not rewrite existing compiler stages as part of establishing these conventions.

Current ordinary PHP classes convert to native classes with shared identity. They
are not compact value structs. Native PHS structs already have a separate contract
in [compact layout types](../../compact_layout_types.md); references follow
[native reference safety](../../native_reference_safety.md). Target support alone
does not prove our PHP carrier and converter support.

## Decisions already accepted

- Explicit alias intent uses `&ref` in portable PHP, not plain `ref`.
- Both source placements are accepted design directions:

```php
$second = /** &ref */ $first;
$second /** &ref Source_Span */ = $first;
```

- The typed placement is a first-declaration form. Neither spelling authorizes
  rebinding a native reference. Define repeated assignments separately; do not
  interpret them as new reference declarations.
- Initial alias scope is direct stable local records and field mutation. Container
  element aliases, escaping aliases and rebinding remain outside this first slice.
- Annotations are portable-PHP metadata; emit supported native PHS syntax rather
  than passing `&ref` through unchanged.

The proposed `/** @scpp-struct */` declaration marker and the independent-copy API
are not yet finalized. Ordinary PHP object assignment shares identity, so those
parts require a clear contract before marking any value-record form supported.

## Ordered implementation checklist

### 1. Freeze the small value-record and alias contract

- [ ] Choose the record marker and show exact PHP-to-PHS examples.
- [ ] Start with a public, typed, scalar-field record such as Source_Span; specify
      construction, initialization and supported fixed-width fields. Avoid exposing
      every target struct field type in the first slice.
- [ ] Define fresh construction, independent copying and mutable aliasing separately.
      Select one explicit copy spelling that PHP can execute and that has local,
      truthful native lowering. Do not silently treat ordinary object assignment
      as an independent value copy.
- [ ] Cover alias effects when the original is assigned again, whole-record assignment
      through an alias, return/argument passing and storing a record in a container.
      Limit unsupported uses instead of claiming general PHP/C++ reference parity.
- [ ] Specify numeric ranges and overflow expectations: PHP int annotations alone
      do not emulate fixed-width native integers. Require native boundary proofs.
- [ ] Specify how the untyped `&ref` form obtains enough local information for
      lowering. Use explicit local metadata or a supported inferred PHS reference
      form if proved; never resolve a remote declaration to guess the type.

Deliverable: one concise supporting contract under specs/portability, with a
PHP/native behavior table and explicitly unsupported forms. Any target limitation
found here is reported to the v0.1 owner rather than patched in this workspace.

### 2. Implement structural conversion and necessary PHP support

- [ ] Add the declaration marker and field metadata to their existing converter
      owners; leave ordinary class behavior intact.
- [ ] Parse both accepted `&ref` placements as explicit alias intent, keeping source
      positions for diagnostics. Reject conflicting/duplicate annotations.
- [ ] Lower supported record declarations, fresh values, copies and local aliases
      to the actual native forms established by step 1.
- [ ] Reject structurally identifiable unsupported forms: container-element or
      temporary alias sources, invalid marker placement, unsupported record members,
      and unsupported field types. Do not silently discard meaningful metadata.
- [ ] Implement only the PHP helper/attribute support the chosen contract needs.
      PHP object sharing already supplies the demonstrated field-mutation alias;
      do not build a general reference emulation runtime.
- [ ] Preserve incremental fingerprints, one-to-one files and folder layout;
      verify a changed record declaration does not leave stale dependent output.

Separate structural rejection from writer discipline. Lifetime, indirect aliases,
semantic identity comparisons and hidden rebinding cannot all be certified by a
local converter. Document that boundary explicitly, including which directly visible
violations are rejected and which remain review/native-proof responsibilities.

### 3. Prove the smallest useful vertical slice

- [ ] A fresh record, independent copy and explicit alias produce independently
      specified results in PHP and native; mutating a copy leaves the original intact,
      while alias field mutation updates it.
- [ ] A vector of value records proves insertion, reads, explicit replacement and
      retained-old-value behavior. Test PHP's reference-sharing traps on insertion
      and extraction, not just two local variables.
- [ ] Cover both annotation placements and meaningful rejection cases. Prove the
      accepted alias lowers to a real reference without patching generated output.
- [ ] Observe native layout for a small fixed-width record and demonstrate inline
      vector storage. Record sizes/allocations only through suitable native probes;
      no portable ABI size promise or guessed memory-saving percentage.
- [ ] Run fast checks plus the affected cumulative native proof on the immutable
      configured target. Do not rerun unrelated expensive suites without cause.

Do not add nested value records or records containing strings/containers merely
because the target accepts them. Their PHP copy behavior needs its own bounded
proof; shallow PHP copies can retain aliases to nested objects.

### 4. Document efficient storage and access patterns

- [ ] Record/class choice: compact records for numerous small values; classes for
      identity-bearing stores, workers and shared owners. Public fields alone do
      not make an ordinary class inline.
- [ ] Dense zero-based vectors versus sparse/arbitrary-key hashes; spell `hash<V,K>`
      value/key order and current supported key families. Guard missing keys before
      consuming values; do not assume PHP key coercion or all target APIs are portable.
- [ ] One owning vector plus name/key-to-ID indexes when appropriate; distinguish
      stable IDs from storage positions, define absent sentinels and deletion policy.
- [ ] Define mutation/read/copy costs at the source level. No native references into
      vector/hash elements under the current target contract; use proved replacement
      or owner operations instead. Do not invent unsupported map APIs.
- [ ] Prefer source spans over repeated substrings and IDs over repeated relationships
      where useful; narrow widths only with a known bound. Keep dynamic JSON at schema
      boundaries. Interning, flattened adjacency and hot/cold splits remain optional,
      measurement-led refinements.
- [ ] Record that PHP behavior tests and native storage/performance measurements
      answer different questions. Keep one representative memory probe rather than
      a new benchmark project.

Deliverable: a compact guide section with a record/store/index example and links
to supported APIs and proofs. Avoid mandatory architecture for every data structure.

### 5. Update the authoring skill after support is proved

- [ ] Add a few decision rules to simple-cpp-portable-php/SKILL.md: select value
      versus identity storage, express copy/alias intent, choose vector/hash by access
      pattern, and keep IDs/ranges explicit.
- [ ] Link the detailed record/reference contract for syntax and restrictions;
      do not duplicate its full examples or acceptance matrix in the skill.
- [ ] Correct the existing blanket class-sharing guidance to distinguish ordinary
      classes from explicitly marked records, while preserving ownership/snapshot rules.
- [ ] State the native-aware authoring requirement without promising automatic
      optimization, lifetime checking or whole-prototype conversion.
- [ ] Validate the skill with skill-creator's validator. Keep unavailable syntax
      labeled proposed until the corresponding PHP/native proof passes.

### 6. Consolidate and resume component work

- [ ] Update the authoring guide, coverage/debt links and broad adaptation record;
      save concise evidence and remove superseded instructions.
- [ ] Commit the completed slice, keeping completed capabilities distinct from
      proposed extensions. No ready-file count increase without component proofs.
- [ ] Apply the conventions to one actual compiler record/store as the next bounded
      component task. Measure whether they improve the intended native layout before
      undertaking wide representation changes.

## First implementation target

Steps 1–3 for a scalar Source_Span-style value record and its direct local aliases,
plus only the necessary documentation/skill changes from steps 4–5. This establishes
an immediately usable pattern. Expand the field and access surface only for concrete
compiler needs; do not wait for a complete mini-language before resuming development.

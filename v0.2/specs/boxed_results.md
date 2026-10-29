# Results containing mixed and dynamic

Doc Status: normative
Scope: bounded v0.2 native runtime/API composition; a prerequisite for JSON, not a JSON implementation.

## Admission and ownership

The existing carrier planner now admits `result<mixed>` and `result<dynamic>` when
the corresponding boxed family is explicitly published. Native identities are
`result_mixed` and `result_dynamic`. Either may be requested independently; scalar
cross-family conversion need not be enabled. Existing error dependencies (string,
int64) and boxed dependencies must also be present. Nothing is auto-published.

`php/families.php::carrierPayload` resolves supported published payload identities.
Boxed preparation precedes carrier admission; carrier records carry the payload's
public header and copy policy. `renderCarriers` includes those prepared dependencies.
The existing `carrier_storage` and generated lifecycle remain the sole state owner;
there is no boxed-specific result implementation or changed default policy.

Publication adds the ordinary carrier requests to a boxed publication:

```json
"carriers": {
  "header": "scpp/carriers.hpp",
  "specializations": [
    {"family": "result", "payload": "mixed"},
    {"family": "result", "payload": "dynamic"}
  ]
}
```

The bounded composition does not admit nullable/result_or_false/result_or_bool
boxed payloads, nested carriers, boxed typed hashes/vectors, or arbitrary records.
Missing payloads, duplicate requests and header conflicts reject before output.

## States and API

The [carrier contract](carrier_slice.md) applies unchanged:

- Default construction is successful with a same-family null payload and allocates
  nothing. Explicit null and boolean-false payloads are also successes.
- Explicit payload construction stores that family. Explicit `error_t` construction
  stores a failure; the no-error sentinel is rejected.
- `has_value()` and `has_error()` return bool_t. `value()` returns a copied payload;
  extraction from failure throws. `error()` returns an owned error copy and rejects
  a successful result. There is no direct condition, delegated container operation
  or interior reference.
- The metadata-selected one-layer extraction policy remains in force: implicit
  extraction by default, or explicit-only conversion when requested by policy.
  Neither policy converts payload families. There is no implicit reboxing or
  result-mixed/result-dynamic conversion.
- Copy retains the complete result state. Move transfers it and resets the source
  to its normal successful-null default. Self-copy/self-move are safe.

Mixed extraction preserves value/COW behavior: changing an extracted container does
not change the result's stored payload. Dynamic extraction preserves shared container
identity: mutation through an extracted container is visible through aliases, while
replacement/clean affects only that extracted binding. The carrier does not impose
an independent deep-copy rule on dynamic values.

Payload/error construction and copy assignment prepare before replacing the target.
Allocation failure preserves its prior state and payload. Explicit payload/error
storage and copying a materialized carrier allocate as in existing carriers; this
slice adds no new per-boxed-value allocation. Copying the boxed payload itself remains
cheap. Error strings are independently owned under the error contract.

## JSON boundary and non-goals

The existing JSON contract requires successful null/false decoding to be distinguishable
from parse failure. These results now supply that state boundary, while preserving
the decoder's selected family. A caller can guard on `has_value()` before assigning
an extraction; failure leaves its destination untouched. This is not a new `take`
helper, source lowering, parser, writer, recursive extraction or module registration.
JSON key normalization and private traversal still require a separate reviewed slice.

The generated public include closure remains template-free. Link generated core and
boxed definitions with `scpp_boxed_storage`, as in `tests/boxed_results/`.
See [validation](boxed_results_validation.md).

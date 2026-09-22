# JSON ingestion shape preservation
Doc Status: planning

Status: native limitation verified; no manifest-parser adaptation or target change
made by this preflight. The ready set remains 34 production files.

## Observed requirement

Manifest_Syntax deliberately uses PHP objects rather than associative decoding to
validate JSON shape before constructing Project_Manifest. In particular:

- `source_folders: {}` fails with `source_folders must be a list`.
- `source_folders: []` fails with `source_folders must be a nonempty list of paths`.
- `source_folders: {"0":"src"}` fails with `source_folders must be a list`.
- `source_folders: ["src"]` is accepted.

Both empty-container identity and numeric object keys therefore matter to the
algorithm. Stabilizing every table into vector<string> would change acceptance.

## Pinned native evidence

On clean `2f0d667f38a35ff02ef77e813f409189cba2d032`, strict/STAN build and native
execution succeed with the JSON module enabled, but decode/encode gives:

```text
{} => []
[] => []
{"0":"src"} => ["src"]
["src"] => ["src"]
```

The same collapse occurs nested under source_folders. Source inspection confirms
that parse_array and parse_object both return dynamic boxes over hash_t<mixed_t>;
the empty branches construct identical carriers, and numeric object keys follow
ordinary PHP-target table-key normalization. This is not merely a misleading
serializer label or a missing converter annotation.

The selected target documents its dynamic-table JSON mapping. This is a missing
lossless ingestion capability for our compiler use, not a claim that all current
JSON decoding violates its documented contract.

Evidence: `results/json-shape-preflight-01/summary.json`, native project and complete
logs, plus a PHP control invoking the actual adopted manifest parser. Keep host.php
outside the native project when rerunning; native source discovery accepts .php as
well as .phs. The native project requires runtime module `json`.

## Consequences and smallest viable approaches

Do not add a naive json_decode compatibility mapping and treat its mixed result as
a schema-preserving document. Reading just the first character also cannot recover
nested object/list identity or numeric-key distinctions.

A lossless boundary must retain node kind and original object-key spelling before
mapping fields into named compiler records. Two viable ownership choices are:

1. A v0.1 JSON document/value API with explicit node kinds and typed accessors,
   optionally separate from existing json_decode table semantics. The portability
   framework can mirror it in PHP and stabilize schema fields immediately.
2. A shared portable parser owned by the portability framework, with explicit
   typed nodes and the same PHP/native code. This avoids waiting on the native
   target but carries a real parser implementation/validation cost; it must cover
   escaping, Unicode, duplicate keys, numbers, nesting limits and failure behavior.

Either choice needs concrete shape/acceptance/error proofs. Original PHP parse
exception wording versus target error-result diagnostics is an additional boundary
to specify; this probe proves shape loss only. It does not select an API, promise
full PHP JSON compatibility or authorize new compiler language functionality.

An open-issue title search for JSON found #190/#192/#195/#196/#198 around dynamic
fields, vectors, numeric casts, encoding and stdClass. It did not establish that
this shape-preservation requirement has an existing issue. No new public issue was
filed by this audit.

A [reviewable document requirement](json_document_requirement.md) now specifies
the information and proof boundary. On 2026-09-22 the user accepted target-side
lossless document support with a PHP counterpart; implementation remains outstanding.

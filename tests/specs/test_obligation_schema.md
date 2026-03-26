# Simple C++ – Test Obligation Schema

This file defines the conceptual schema used before concrete PHP samples are generated.

## Required fields

- `obligation_id`
- `feature_area`
- `feature_name`
- `origin_type`
- `source_files`
- `source_sections`
- `obligation_type`
- `status`
- `primary_assertion`
- `expected_outcome`
- `level_hint`
- `reason_for_existence`

## Allowed origin types

- `runtime_spec`
- `generator_spec`
- `cross_spec`
- `regression`
- `spec_gap`

## Allowed obligation types

- `positive_behavior`
- `negative_rejection`
- `compile_constraint`
- `runtime_behavior`
- `integration_behavior`
- `regression`

## Allowed status values

- `required`
- `optional`
- `known_gap`
- `known_fail`

## Deduplication rule

Merge obligations only when they assert the same primary behavior or rejection boundary and no meaningful provenance is lost.


## Current feature-policy notes

When creating obligations for the current suite, apply these normalization rules:
- untyped array literals MUST create obligations of type `negative_rejection` or `known_gap` depending on intent, because they are not current vector-creation syntax
- explicit `vector<T>` array literals with element/type mismatches SHOULD typically map to `compile_constraint`
- `foreach` value and `foreach` by-reference value on `vector<T>` map to positive/runtime obligations
- `foreach` key/value and key/value-by-reference forms map to negative rejection obligations
- string interpolation obligations currently map to `known_gap` unless the implementation status changes


## Phase-1 planning note

For the current phase-1 pass, obligations SHOULD be grouped under the locked language-area tree rather than under a smaller convenience tree. In particular, type obligations should be grouped by concrete type bucket (`int`, `float`, `bool`, `string`, `nullable`, `vector`) and not collapsed into one generic scalar bucket.

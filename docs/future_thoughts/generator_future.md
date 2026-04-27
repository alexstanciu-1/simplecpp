# Generator Future Thoughts
Doc Status: planning

This document captures non-authoritative future ideas for the PHP S2S generator.

These are not promises and do not define current behavior.

## Current State (authoritative elsewhere)

- The generator is type-blind.
- It performs structural lowering.
- It does not resolve symbols or validate full program semantics.

## Possible Future Directions

These may be explored but are not committed:

- type-aware lowering
- symbol resolution
- explicit cast injection at typed boundaries
- improved non-null enforcement
- dependency and include analysis
- deeper semantic validation

## Other Candidate Areas

These may be explored but are not committed:

- typed callable parameters and broader callable-shape support
- returning closures
- owning callable containers such as `inplace_function`
- borrowed callable views such as `function_ref`
- broader non-scalar union-parameter normalization
- additional safe reference/borrow patterns if they can be proven without violating the current native-reference safety rule

Any of these features require explicit promotion into normative specs before they become part of the supported behavior.

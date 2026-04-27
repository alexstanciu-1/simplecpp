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

Any of these features require explicit promotion into normative specs before they become part of the supported behavior.

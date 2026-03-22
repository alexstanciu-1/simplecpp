# Simple C++ – Catalog Rebuild Workflow

1. Assistant provides a PHP example from the catalog.
2. User provides the expected Simple C++ target code, or marks the case as `ERROR`.
3. Assistant corrects the proposed target to comply with the general rules.
4. Assistant derives the generalized rule, not just the concrete instance.
5. The catalog stores one row per generalized rule, not one row per trivial permutation.

## Constraints

- General rules have precedence.
- Concrete examples may be corrected to fit the general rules.
- The goal is to avoid combinatorial explosion and preserve only reusable rule knowledge.

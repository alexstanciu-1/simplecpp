# Binary syntax and scoped angle matching
Doc Status: supporting

Binary_Syntax preserves the two supported binary operators, their nullable token/name
mappings and precedence. Its angle matcher pairs opening/closing token indexes within
parenthesis/bracket scopes and resets on semicolon/braces, without type lookup.

Angle_Scope owns a typed integer stack with explicit logical size and reusable
storage. The matcher owns a vector of these scope records and its active depth.
Closing a scope discards its pending openings logically; entering a reused scope
clears it. Scope reset clears root state. Pair insertion order and unmatched-token
behavior remain unchanged. Scratch work and storage remain linear in tokens; no
performance improvement is claimed, and storage may retain its high-water capacity
until the matcher returns.

This is source adaptation, not generic PHP stack/generator support. No converter
capability was added. Operator match expressions become explicit fixed comparisons.

The host oracle compares 5,000 deterministic streams against a frozen prototype
implementation (only its class name changes), using angles, grouping delimiters,
boundaries and ordinary tokens. The cumulative PHP/native fixture checks independently
expected pair order over nested/discarded/reset/reused scopes, nullable operator
mapping, precedence and unsupported-operation errors.

The structural-query generator/cursor decision is separate. This helper does not
establish whole-parser portability or resolve the source-diagnostic blocker.

Evidence: `specs/planning/compiler_migration/results/binary-syntax-01/summary.json`.
PHP/native validation passes on `2f0d667f38a35ff02ef77e813f409189cba2d032`, as do
all seventeen retained compiler fixtures. Twenty-eight production files are ready.

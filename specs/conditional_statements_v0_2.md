# Conditional statements in v0.2
Doc Status: normative

## Bounded statement bodies

An `if`, `elseif` or `else` arm accepts either a braced statement block or one
otherwise-supported statement. `else if` has the same conditional-chain meaning
as `elseif`; comments between those tokens do not change it. An unbraced body
ends after its one complete statement, including any nested conditional arms.

An `else` or `elseif` belongs to the nearest preceding unmatched `if` at that
nesting level. Braces may close an inner conditional body and thereby make the
following clause belong to an outer conditional. Indentation has no grammatical
meaning. For example:

```php
if ($outer)
    if ($inner)
        return 1;
    else
        return 2;
```

Here the `else` belongs to the inner `if`. An absent outer `else` still leaves an
unmatched fallthrough path for completion analysis.

## Scope and evaluation

Every arm is a branch-local scope whether or not its source body has braces.
Typed declarations may shadow outer locals under the existing lexical declaration
rules. Untyped writes resolve a visible binding first; a new branch-local name
cannot escape or merge with another arm at the join. Omitting braces does not
introduce PHP-style function-wide local scope or weaken declaration restrictions.

Evaluate each condition once when reached. Select at most one arm in a chain;
conditions and bodies of subsequent arms are not executed after selection. All
arms still undergo semantic preparation, including statically unreachable ones.
Existing condition conversion, return completion, exception and control-transfer
rules apply unchanged. A supported `break` or `continue` inside an unbraced
conditional arm still requires a suitable enclosing construct.

## Scope boundary

This amendment admits single-statement conditional bodies only. It does not admit
unbraced loop bodies, colon/endif alternative syntax, empty semicolon bodies,
numbered break/continue, or otherwise unsupported statement/declaration forms.
A conditional may contain an already-supported braced loop or another supported
compound statement as its one statement. Runtime and builtin availability are
independent of conditional syntax admission.

## PHP-first compiler delivery — 2026-11-04

The active `v0.2/compiler/` implements braced `if`, `elseif`, `else if`, and `else`
in all three flavors, including nested conditionals. Both conditional-chain spellings
normalize to the same AST; `elseif` is reserved in each flavor. Unbraced arms remain
an implementation debt against the broader contract above. Standalone blocks,
loops, logical operators and ternary expressions are not added by this slice.

Conditions admit bool, int/int64, float/float64 and string. Bool uses its value;
numeric zero (including negative floating zero) is false; strings are false only
when empty or exactly `"0"`. Other strings, including `"false"`, are true. This is
condition truthiness, not a grant of string-to-bool conversion. Direct null and
new distinct-byte conditions remain deferred, as do wrapper/handle conditions.

All arms are prepared once even when statically unreachable. Each arm owns a lexical
scope. Explicit declarations may shadow outer bindings; PHP bare writes select an
already-visible local or introduce an arm-local declaration. Inner names cannot
escape or merge between arms. Function storage IDs remain unique across blocks.

Completion is structural: a conditional guarantees return only if it has an else
and every arm guarantees return. A function with a value return type rejects normal
fallthrough; void functions permit it. Constant-condition reachability is not used
to bypass these checks. Value facts are conservatively discarded at alternative and
join boundaries. Types, bindings and prior occurrence snapshots remain intact; no
branch value merge or repeated preparation fixed point is performed.

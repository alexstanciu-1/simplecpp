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

# Error First-Type Discussion
Doc Status: planning

Date: 2026-05-23

Purpose:
- capture the immediate strict-sample cleanup performed on this hotfix branch
- separate that docs cleanup from the still-open design question around `error`
- start a concrete discussion about whether strict code should treat `error` as a first type in source syntax

## Current Branch Context

This note accompanies the `hotfix/strict-samples-error-type` branch.

Immediate branch work:
- replace legacy local typed-docblock forms like `$text /** string */ = "seed";`
- use the newer strict-style typed declaration form instead, for example `$text string = "seed";`

Scope intentionally excluded from the hotfix:
- changing `error` declarations from `$err /** error */;` to a different source form
- changing generator/runtime semantics for `error`
- changing quick-learn normative guidance before the design is settled

## Current Observations

As of 2026-05-23:

- strict samples still had several legacy typed local declarations for ordinary value types such as `string`, `int`, and `bool`
- strict samples already use `error` in practice, but only in the older docblock style:

```php
$err /** error */;
```

- the strict quick-learn also still demonstrates `error` using that same older form

This creates a visible mismatch:

- ordinary strict locals are moving toward first-position type syntax
- `error` still looks like a special-case annotation rather than an ordinary type

## Why This Matters

If strict mode wants to present explicit, readable typed code, `error` likely should not remain a permanently exceptional spelling if it is meant to be used directly in source.

Today that ambiguity leaks into docs and examples:

- users can reasonably read `error` as "supported enough to use"
- but the spelling still suggests "special case / not fully promoted"

That makes it harder to answer basic questions such as:

- Is `error` meant to be a normal source-facing type?
- Is it only a wrapper payload carrier?
- Should strict examples normalize around it the same way they do for `string`, `int`, and handles?

## Discussion Questions

### 1. Surface syntax

Should strict code eventually prefer a first-type declaration such as:

```php
$err error;
```

or:

```php
$err error = ...;
```

instead of:

```php
$err /** error */;
```

### 2. Semantic status

If `error` becomes a first type, what contract are we promising?

Possible interpretations:
- a normal source-visible type that may be declared, passed, and stored
- a limited carrier type mainly intended for `take($out, $err, result<T>)`
- a transitional spelling that should remain narrow until accessor/inspection rules are documented

### 3. Documentation readiness

Before promoting syntax, do we need stronger documentation for:

- how `error` values are created in source-visible flows
- whether they are assignable/storable like other types
- what accessors or inspection patterns are officially supported
- whether strict code should branch on `error` details directly or only pass them through/log them

## Proposed Next Step

Keep this hotfix branch narrowly focused:

1. modernize ordinary strict sample local declarations now
2. leave `error` examples unchanged for the moment
3. decide whether `error` should be promoted as a first type in source syntax
4. if yes, update the quick-learn and strict examples together in one follow-up change

## Recommendation

Treat `error` promotion as a small design/documentation task rather than silently changing only the examples.

That keeps the current docs hotfix safe while still making the design gap explicit.

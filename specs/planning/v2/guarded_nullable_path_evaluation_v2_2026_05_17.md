# Guarded Nullable Path Evaluation V2
Doc Status: planning

Date: 2026-05-17

Purpose:
- preserve a future-direction idea for nullable-path ergonomics without making it current language/runtime authority
- record that some visibly guard-shaped nullable-chain patterns may be candidates for better lowering in a later generator/runtime generation
- separate current v1 behavior from a possible v2 guarded-evaluation model

## Background

Current v1 direction for nullable chain safety is:

- plain access should fail with a controlled runtime exception instead of crashing
- `isset(...)` is the preferred current safe probe form for nullable paths

Examples of the current preferred shape:

```php
!isset($root->child->name)
isset($this->path["is"]->not->existing)
```

This note captures a separate future idea:

- some source patterns already visually express guarded nullable-path intent
- later generator/runtime versions may choose to recognize and normalize those patterns automatically

This document is planning only. It does not change current semantics.

## Motivating Examples

### 1. Explicit null-guard chain

```php
$a === null || $a->xxx === null || $a->xxx->yyy === null
```

This is visually close to:

```php
!isset($a->xxx->yyy)
```

The source already reads like a path guard rather than ordinary strict object traversal.

### 2. Guarded condition context

```php
if ($this->path["is"]->not->existing) {
}
```

Future guarded-path idea:

- if the path becomes null in the middle during boolean-condition evaluation
- the guarded condition would evaluate to `false`
- no runtime exception would be thrown in that specific guarded context

### 3. Guarded ternary condition

```php
$this->path["is"]->not->existing ? "yes" : "no"
```

Future guarded-path idea:

- if the path becomes null in the middle during ternary-condition evaluation
- the condition would evaluate to `false`
- result would be `"no"`

## Two Possible V2 Directions

### Direction A: pattern-recognition rewrite into `isset(...)`

The generator could recognize specific source forms such as:

```php
$a === null || $a->xxx === null || $a->xxx->yyy === null
```

and lower them to an equivalent safe probe form:

```php
!isset($a->xxx->yyy)
```

Possible benefits:

- keeps generated C++ smaller and simpler than broad guarded-evaluation machinery
- preserves the current `isset(...)`-centered safe-probe model
- only applies to patterns that already clearly look like nullable guards

Possible concerns:

- requires AST pattern recognition and normalization
- may be brittle if the source guard shape becomes more varied
- may be too narrow if later users expect broader guarded behavior

### Direction B: guarded boolean/path evaluation contexts

A broader v2 model would say:

- in selected guarded boolean contexts, a null in the middle of a path does not throw
- instead, it contributes `false` to the guarded condition

Candidate guarded contexts:

- `if (<expr>)`
- `else if (<expr>)`
- `while (<expr>)`
- ternary condition `<expr> ? a : b`
- possibly lazy logical evaluation contexts used to build those conditions

Under that model:

```php
if ($this->path["is"]->not->existing) {
}
```

would simply evaluate as false when the path breaks in the middle.

Likewise:

```php
$this->path["is"]->not->existing ? "yes" : "no"
```

would evaluate to `"no"` without throwing.

Possible benefits:

- very ergonomic for authored nullable-path conditions
- aligns with the intuitive reading of “this condition is already acting as a guard”
- broader than one specific null-check pattern

Possible concerns:

- changes semantics more deeply than a targeted `isset(...)` rewrite
- may blur the line between ordinary strict access and safe probe access
- could make it harder to reason locally about when a null path throws versus silently becomes false
- could require more generator/runtime coordination than desired

## Current Preference For V1

For current work, the preferred v1 posture remains:

- runtime hardening for plain access
- explicit `isset(...)` for safe probe intent
- docs steering users toward `isset(...)` where the source is clearly a path guard

This means examples such as:

```php
$a === null || $a->xxx === null || $a->xxx->yyy === null
```

should currently be treated as:

- not ideal style
- a good candidate for future rewrite or future guarded semantics
- better written today as:

```php
!isset($a->xxx->yyy)
```

## Questions For A Later Generation

Questions to revisit in a later version:

1. Should recognizable null-guard chains lower to `isset(...)` automatically?
2. Should guarded boolean conversion contexts tolerate mid-path null by returning `false` instead of throwing?
3. If guarded behavior is adopted, which contexts qualify?
4. Should guarded behavior apply only to nullable object/dim chains, or also to mixed/path-like chains more broadly?
5. How should docs clearly distinguish:
   - ordinary strict access
   - explicit probe access via `isset(...)`
   - future guarded condition evaluation

## Suggested Future Evaluation Order

If this is revisited later, the lower-risk path is probably:

1. first evaluate targeted source-pattern normalization into `isset(...)`
2. only then consider a broader guarded-condition semantic model if the narrower rewrite is not enough

That keeps the first future step closer to current language guidance and avoids prematurely widening semantics.

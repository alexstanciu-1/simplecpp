# JSS Private Fields Narrow Note
Doc Status: planning

Date: 2026-06-12

Purpose: define a narrow design direction for ES6-shaped `#private` class fields/methods in JSS without implementing them yet and without reopening broader visibility or runtime-model questions.

This is a planning artifact, not semantic authority.

## Scope

This note is intentionally narrow.

It is only about the possible JSS source surface:

- `#name` private field
- maybe later `#method()` private method

It is not a decision to implement now.

## Current Status

Current JSS subset:

- accepts public/default-public members
- accepts explicit `public`
- rejects `private`
- rejects `protected`

So if JSS gains a private surface, the likely candidate is the ES6-shaped `#name` form rather than PHP-style `private`.

## Recommended Direction

If we add private support later, prefer:

```js
class BankAccount {
    #balance: int = 0;

    public deposit(amount: int): void {
        this.#balance = this.#balance + amount;
    }
}
```

Do not add PHP-style source forms such as:

```js
private balance: int = 0;
protected balance: int = 0;
```

Reason:

- `#name` keeps the JSS surface modern and distinct
- it avoids reintroducing visibility keywords we already rejected
- it makes the feature clearly opt-in instead of broadening the whole visibility system

## Constraints

Any future implementation should preserve these rules:

1. No JavaScript runtime semantics
   - no prototype-private behavior
   - no dynamic reflection compatibility promise
   - no hidden JS object model

2. Strict typed member model remains intact
   - private fields still need explicit types
   - field initialization rules stay explicit

3. STAN must own the semantic side
   - visibility checks
   - allowed access sites
   - inheritance behavior
   - whether private methods participate in any callable/member classification rules

4. JSS frontend should own only syntax/lowering shape
   - tokenization/parsing of `#name`
   - AST representation
   - emission only after STAN-valid classification/visibility answers exist

## Open Questions

1. Does `#name` lower to ordinary private PHS members, or do we need a narrower intermediate restriction first?
2. Do we want to allow private methods at the same time as private fields, or phase fields first?
3. How should `this.#name` classification integrate with STAN’s member-role machinery?
4. What inheritance rules do we want:
   - class-local only
   - no descendant access
   - explicit diagnostics for shadowing/redeclaration

## Not For Immediate Implementation

Do not implement yet if it would require:

- JSS-local visibility semantics
- frontend-only member-access policing
- separate private-member resolution outside STAN

That work should wait until STAN is ready to own the semantic side cleanly.

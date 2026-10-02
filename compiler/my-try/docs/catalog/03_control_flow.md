# 03. Conditions and control flow
Doc Status: planning

> **Important: LLVM is deferred by default. Work on the direct LLVM backend only when the user explicitly requests it. The v0.2 default scope is Frontend + C++ S2S.**

[Catalog and workflow](README.md) · [Source merge ledger](source_merge.json)

Requires expressions. Start with if/else, then joins, loops and control transfer.

Order is a discussion sequence, not a claim that every row is a prerequisite. Split combined examples before implementation.

## Handoff from chapter 02 — 2026-10-02

Chapter 02 is [concluded for its positive pass](02_expressions.md#positive-pass-conclusion--2026-10-02).
Continue with working bounded functionality, sound model/ownership and focused proof;
keep peripheral hardening as explicit debt. Chapter 03 semantics and new structures
still require discussion. The imported rows below are not implementation approval.

Start the discussion with block environments and `if`/`else`:

- Decide condition admission/conversion explicitly. Existing boolean operators and
  explicit casts provide reusable owners; ordinary PHP truthiness is not an agreed
  implicit condition contract.
- Decide child-local visibility, shadowing, assignment to outer storage and what
  bindings survive a branch join. The current `preparation_context.locals` is one
  body-local table; internal blocks currently share it. Source block parsing remains
  deferred under [NOTE-033.b](01_literals_locals.md#note-033b--nested-statement-blocks).
- Extend completion composition before accepting branches. `Body_Preparation` currently
  reports straight-line fallthrough and composes internal blocks. It checks every
  statement, including those after a return, and rejects non-void fallthrough before
  body work settles. Merely finding a return in one branch cannot prove completion.
- Preserve lazy branch execution and prepare conditions in the semantic owner; C++
  generation consumes the selected facts. Do not loosen existing eager-effect
  restrictions merely to make a control-flow fixture compile.

Useful implementation owners: [body preparation](../../04_analyze/prepare/semantics/bodies.php),
[contexts/facts](../../04_analyze/prepare/data/structures.php),
[conversion preparation](../../04_analyze/conversions/preparation.php),
[scope lookup](../../03_parse/scopes/lookup.php) and the
[incremental scheduler](../../04_analyze/prepare/worker.php).
Existing [fallthrough](../../tests/fallthrough.php),
[internal-block dispatch](../../tests/preparation_dispatch.php) and
[recovery](../../tests/preparation_recovery.php) proofs provide starting controls.
Agree the first vertical slice before introducing a general control-flow graph.

Carry forward PE-03 duplicate-declaration debt, conditional runtime-template
copyability, bracket overload/lowering deferral and the preserved runtime arithmetic
contracts. Reopen only dependencies of the selected slice. Check the
[portability status](../portability/conversion_review.md): the latest chapter 02
repairs have PHP/generated-C++ proof, not a new native compiler checkpoint.

## Agreed first slice — 2026-10-02

CTRL-IF-001/002/003 and SCOPE-VAR-001 share one implementation slice, including
ordinary nested braced blocks. Conditions use the existing conversion owner with
`conversion_context::condition`: canonical bool is accepted; other supported
scalars need an explicit bool cast. Conditions execute once when reached, later
arms are lazy, and every arm is semantically prepared, including unreachable syntax.

`statement_body_node` is the shared abstract statement-body owner. It owns ordered
statements, spans and common traversal/dispatch. `block_node` is an empty final
subclass. `function_body_node` adds executable-unit scope/work identity and its
specialized dispatch. An `if_node` owns a required block body, an arm-kind enum,
an optional condition (absent only for else), and an optional owning `next_arm`
edge (absent for else). Only the initial arm belongs to the enclosing sequence.
This is a construct-specific syntax chain, not a general sibling list or CFG.

Preparation uses transient parent-linked local environments. Reads and untyped
writes resolve the nearest visible declaration; an untyped first write introduces
a local only if none is visible. Typed declarations belong to the current block,
reject current-block duplicates and may shadow outer locals. Their names hide outer
bindings during initialization; self-reads/writes reject. Branch-local names never
escape or merge at joins. Function parameters share the function's root environment.
The existing lexical-resolution contract and proof in
`specs/portability/lexical_resolution.md` and `compiler/tests/lexical_resolution/`
establish the explicit declaration/self-initialization behavior.

Statements report a transient `statement_completion` through typed dispatch.
Sequences compose normal fallthrough; conditional chains combine arm outcomes,
including an unmatched fallthrough path when else is absent. Non-void functions
reject normal exit. Each return uses the enclosing return-type contract. No
signature-dependent flag/backlink is retained on nested blocks. Constant-condition
reachability, definite-assignment analysis and branch value merging are deferred.

Target C++ is direct `if / else if / else`, braced bodies, and the prepared bool's
native value. Existing storage/conversion lowering handles all branch statements.
No extra runtime helper, condition temporary or lambda is needed for arm selection.
This straightforward form introduces no new compilation dependency family; output
partitioning and compilation benchmarks remain separate work.

Legacy review: `Generator::renderIfStatement` and `renderNestedStatements` already
emit lazy C++ branches and restore local visibility after each body. The retained
control-flow fixtures and `variables_003_inner_scope_shadow_basic` prove outer
assignment (despite that fixture's historical name). Legacy annotation/declaration
handling does not establish explicit typed-shadowing semantics for my-try; the
lexical-resolution evidence above does. Legacy condition truthiness/hints are not
imported into this bool-only slice.

First-slice non-goals: loops, switch/match, ternary, unbraced and colon/endif syntax, CFG, LLVM,
general effect analysis and the unrelated PE-03 duplicate-declaration debt.

Proof: [focused suite](../../tests/control_flow.php) and
[shared-pool runner](../../tests/control_flow.py). The PHP suites cover prepared
binding identity, condition facts, non-escape, duplicates/self-initialization,
return/fallthrough, unreachable checking, unchanged-body dependencies, no-edit
retries and clean/incremental recovery. Generated C++ programs prove branch choices,
side effects, condition evaluation count/laziness, shadowing and function returns
with `-Werror=return-type`. Final evidence: `/tmp/my-try-control-flow-20261002-final/`
(10 PHP suites and 24 generated programs passed).
This is PHP-host/compiler and generated-program proof, not a native compiler rebuild.

## Agreed loop slice — 2026-10-02

`loop_node extends breakable_node` owns one `block_node` body and shared preparation
dispatch. `while_node` and `do_while_node` own their condition; `for_node` owns ordered
initialization statements, condition expressions and update expressions. Named edges
drive maintenance and lazy inspection. `foreach_node` is an abstract reserved
specialization with a Chapter 06 TODO, not an accepted syntax form. Future iteration
must reuse this body/scope/transfer protocol; iterable/binding facts remain undecided.

`Loop_Preparation` establishes a loop-local environment and nearest break/continue
targets, then restores both on every exit. Body blocks have their own child environment.
For initialization supports ordinary first writes and explicit typed declarations,
including ordered comma lists; bindings do not escape the loop. Conditions/updates
cannot introduce locals or see body-local declarations. The final condition expression
uses the existing `condition` boolean conversion; earlier expressions are evaluated
and discarded in order. An omitted for condition is unconditional. Braces are required.

`Completion_Preparation` composes normal fallthrough, return, break and continue.
Sequences discard unreachable exits (but still validate syntax); branches join exits;
loops consume their own bare transfers. A do-while whose body must return cannot fall
through. An omitted-test for has no normal exit unless its body can break. Explicit
constant conditions remain conservative; there is no CFG or constant reachability pass.
Return checking stays on the enclosing function, not a flag/backlink on loop blocks.

C++ uses native while/do/for and prepared native boolean tests. A surrounding block
owns for initialization, preserving ordinary declaration lowering. Native for updates
ensure continue executes the update; do-while continue reaches the test; break skips
both. Comma prefixes/updates are explicitly discarded to preserve built-in sequencing.
Transfers retain weak prepared target identities and emission checks those identities.
Breakable and continuable targets stay distinct for future switch support.

Legacy review: renderWhileStatement, renderDoWhileStatement, renderForStatement and
their clause helpers already use native loops and last-expression tests; one-level
break/continue fixtures establish their target behavior. This slice retains the shared
preparation boundary, canonical-bool policy and lexical binding rules, not legacy
truthiness inference or its separate declaration-rendering shortcuts.

Non-goals: executable foreach, switch, numbered transfers, unbraced loops, CFG,
constant reachability, LLVM and new expression capabilities. Foreach continuation and
iteration lifetime need Chapter 06-specific facts/lowering, not a second loop family.

Proof: `tests/control_flow.php` / `tests/control_flow.py` cover execution order,
header/body scope, nearest transfer identity, lazy inspection, completion, moved body
identity, failed/no-edit retries and clean/incremental repair. Generated programs use
`-Werror=return-type`. These are PHP-host and generated-program proofs, not a native
compiler rebuild.

Final loop evidence: `/tmp/my-try-loops-20261002-final/` — 10 focused PHP suites
(shared FPM pool) and 43 generated-C++ programs passed.

## Progress

Edit these rows as work proceeds. Imported source support is recorded below, independently of this progress.

| Entry | Status | PHP input example | Frontend | C++ S2S | LLVM | Proof / blocker |
| --- | --- | --- | --- | --- | --- | --- |
| [CTRL-IF-001](#ctrl-if-001) | agreed | `$a bool = true; if ($a) { $b = 1; }` | proved | proved | deferred | [First slice](#agreed-first-slice--2026-10-02), control_flow.php |
| [CTRL-IF-002](#ctrl-if-002) | agreed | `$a bool = false; if ($a) { $b = 1; } else { $b = 2; }` | proved | proved | deferred | [First slice](#agreed-first-slice--2026-10-02), control_flow.php |
| [CTRL-IF-003](#ctrl-if-003) | agreed | `if (false) { } elseif (true) { } else { }` | proved | proved | deferred | [First slice](#agreed-first-slice--2026-10-02), control_flow.php |
| [CTRL-WHILE-001](#ctrl-while-001) | agreed | `while ($a) { $b++; }` | proved | proved | deferred | [Loop slice](#agreed-loop-slice--2026-10-02), control_flow.php |
| [EXPR-TERNARY-001](#expr-ternary-001) | pending-discussion | `$a = $b ? $c : $d;` | unverified | unverified | deferred | — |
| [CTRL-SWITCH-001](#ctrl-switch-001) | pending-discussion | `switch ($a) { case 1: break; default: break; }` | unverified | unverified | deferred | — |
| [CTRL-MATCH-001](#ctrl-match-001) | pending-discussion | `$a = match ($b) { 1 => 10, default => 0 };` | unverified | unverified | deferred | — |
| [CTRL-DOWHILE-001](#ctrl-dowhile-001) | agreed | `do { $b++; } while ($a);` | proved | proved | deferred | [Loop slice](#agreed-loop-slice--2026-10-02), control_flow.php |
| [CTRL-FOR-001](#ctrl-for-001) | agreed | `for ($i = 0; $i < 10; $i++) { }` | proved | proved | deferred | [Loop slice](#agreed-loop-slice--2026-10-02), control_flow.php |
| [CTRL-BREAK-001](#ctrl-break-001) | agreed | `break;` | proved | proved | deferred | [Loop slice](#agreed-loop-slice--2026-10-02), control_flow.php |
| [CTRL-CONTINUE-001](#ctrl-continue-001) | agreed | `continue;` | proved | proved | deferred | [Loop slice](#agreed-loop-slice--2026-10-02), control_flow.php |
| [SCOPE-VAR-001](#scope-var-001) | agreed | `$x = 1; if (true) { $x = 2; } return $x;` | proved | proved | deferred | [First slice](#agreed-first-slice--2026-10-02), control_flow.php; output 2 |
| [NOTE-017](#note-017) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
## CTRL-IF-001

**v0.2 decision / target C++:** [Agreed first slice](#agreed-first-slice--2026-10-02).

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:122](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
if ($a) { $b = 1; }
```

**Existing C++ lowering / result**

```cpp
if (a) { auto b = static_cast<int_t>(1); }
```

**Category:** Control flow

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** condition must be boolean-compatible

**Normalized pattern:** `if (<expr>) <block>`

**General rule:** `if` statements are emitted directly. The condition must be boolean-compatible. The body is emitted as a block, and all inner statements must follow standard normalization rules (including `auto` only on first assignment in scope and literal casting).

**Diagnostics:** Error if condition is not boolean-compatible, declaration rules are violated, or literals are not normalized.

**Notes:** Scope rule applies inside the block.


## CTRL-IF-002

**v0.2 decision / target C++:** [Agreed first slice](#agreed-first-slice--2026-10-02).

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:123](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
if ($a) { $b = 1; } else { $b = 2; }
```

**Existing C++ lowering / result**

```cpp
if (a) { auto b = static_cast<int_t>(1); } else { auto b = static_cast<int_t>(2); }
```

**Category:** Control flow

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** condition must be boolean-compatible

**Normalized pattern:** `if (<expr>) <block> else <block>`

**General rule:** `if/else` statements are emitted directly. Each branch is its own scope. Inner assignments follow normal scope rules, including `auto` only on first assignment in that branch scope, and literal normalization.

**Diagnostics:** Error if condition is not boolean-compatible, branch declaration rules are violated, or literals are not normalized.


## CTRL-IF-003

**v0.2 decision / target C++:** [Agreed first slice](#agreed-first-slice--2026-10-02).

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:124](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
if ($a) { } elseif ($b) { } else { }
```

**Existing C++ lowering / result**

```cpp
if (a) { } else if (b) { } else { }
```

**Category:** Control flow

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** each condition must be boolean-compatible

**Normalized pattern:** `if / elseif / else chain`

**General rule:** PHP `elseif` chains are emitted as C++ `else if` chains. Conditions must be boolean-compatible.

**Diagnostics:** Error if a condition is not boolean-compatible or `elseif` is emitted literally into C++.


## CTRL-WHILE-001

**v0.2 decision / target C++:** [Agreed loop slice](#agreed-loop-slice--2026-10-02).

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:127](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
while ($a) { $b++; }
```

**Existing C++ lowering / result**

```cpp
while (a) { b++; }
```

**Category:** Control flow

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** condition must be boolean-compatible; body statements valid in scope

**Normalized pattern:** `while (<expr>) <block>`

**General rule:** `while` loops are emitted directly. The condition must be boolean-compatible. The loop body follows normal statement and scope rules.

**Diagnostics:** Error if condition is not boolean-compatible or body uses undeclared variables.


## EXPR-TERNARY-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:88](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = $b ? $c : $d;
```

**Existing C++ lowering / result**

```cpp
auto a = php::ternary_eval([&]() -> decltype(auto) { return b; }, [&]() -> decltype(auto) { return c; }, [&]() -> decltype(auto) { return d; });
```

**Category:** Expression

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** condition and branch combination must be covered by the runtime ternary matrix

**Normalized pattern:** `<var> = <cond> ? <expr> : <expr>`

**General rule:** The ternary operator lowers through `php::ternary_eval(...)` so condition truthiness and branch normalization are resolved centrally in the runtime instead of ad hoc in the generator.

**Diagnostics:** Error if the runtime ternary matrix does not define the branch pair.

**Notes:** This keeps direct expressions and assigned locals consistent.


## CTRL-SWITCH-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:125](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
switch ($a) { case 1: break; default: break; }
```

**Existing C++ lowering / result**

```cpp
switch (a) { case static_cast<int_t>(1): break; default: break; }
```

**Category:** Control flow

**Rule kind:** generation

**Source support status:** supported-with-known-incompatibility

**Preconditions:** switch expression and case values must be compatible

**Normalized pattern:** `switch (<expr>) { case ... }`

**General rule:** `switch` is emitted directly as C++ `switch`, with normalized literals.

**Diagnostics:** Error if literals are not normalized.

**Notes:** PHP `switch` uses loose comparison; C++ `switch` uses strict matching.


## CTRL-MATCH-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:126](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = match ($b) { 1 => 10, default => 0 };
```

**Existing C++ lowering / result**

```cpp
int_t tmp; if (php::identical(b, static_cast<int_t>(1))) { tmp = static_cast<int_t>(10); } else { tmp = static_cast<int_t>(0); } auto a = tmp;
```

**Category:** Control flow

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** all match arms must produce compatible types; default arm must exist

**Normalized pattern:** `<var> = match (<expr>) { <cases> }`

**General rule:** PHP `match` must be lowered to an `if / else if / else` chain using strict comparison (`php::identical`). A temporary variable must be introduced to preserve expression semantics.

**Diagnostics:** Error if no `default` arm, result types differ, or `match` is emitted directly.

**Notes:** Preserves PHP `===` semantics.


## CTRL-DOWHILE-001

**v0.2 decision / target C++:** [Agreed loop slice](#agreed-loop-slice--2026-10-02).

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:128](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
do { $b++; } while ($a);
```

**Existing C++ lowering / result**

```cpp
do { b++; } while (a);
```

**Category:** Control flow

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** condition must be boolean-compatible; body statements valid in scope

**Normalized pattern:** `do <block> while (<expr>)`

**General rule:** `do-while` loops are emitted directly. The body executes first, then the condition is evaluated. All inner statements must follow normalization and scope rules.

**Diagnostics:** Error if condition is not boolean-compatible or body uses undeclared variables.


## CTRL-FOR-001

**v0.2 decision / target C++:** [Agreed loop slice](#agreed-loop-slice--2026-10-02).

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:129](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
for ($i = 0; $i < 10; $i++) { }
```

**Existing C++ lowering / result**

```cpp
for (auto i = static_cast<int_t>(0); i < static_cast<int_t>(10); i++) { }
```

**Category:** Control flow

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** loop variable declared in init; condition boolean-compatible

**Normalized pattern:** `for (<init>; <cond>; <step>) <block>`

**General rule:** `for` loops are emitted directly. Initialization follows normal declaration rules (`auto` on first assignment). Condition and step follow expression normalization rules, including literal casting.

**Diagnostics:** Error if literals are not normalized, declaration rules are violated, or condition is not boolean-compatible.

**Notes:** Loop variable scope is limited to the `for` statement.


## CTRL-BREAK-001

**v0.2 decision / target C++:** [Agreed loop slice](#agreed-loop-slice--2026-10-02).

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:134](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
break;
```

**Existing C++ lowering / result**

```cpp
break;
```

**Category:** Control flow

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** must be inside a loop or `switch`

**Normalized pattern:** `break`

**General rule:** `break` is emitted directly.

**Diagnostics:** Error if used outside of loop or `switch`.


## CTRL-CONTINUE-001

**v0.2 decision / target C++:** [Agreed loop slice](#agreed-loop-slice--2026-10-02).

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:135](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
continue;
```

**Existing C++ lowering / result**

```cpp
continue;
```

**Category:** Control flow

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** must be inside a loop

**Normalized pattern:** `continue`

**General rule:** `continue` is emitted directly.

**Diagnostics:** Error if used outside of a loop.


## SCOPE-VAR-001

**v0.2 decision / target C++:** [Agreed first slice](#agreed-first-slice--2026-10-02).

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:316](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$x = 1; if (true) { $x = 2; } echo $x;
```

**Existing C++ lowering / result**

```cpp
auto x = static_cast<int_t>(1); if (...) { x = static_cast<int_t>(2); } php::echo_one(x);
```

**Category:** Scope

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** variable resolves to a declaration in the current block or an enclosing block

**Normalized pattern:** `block-local variable visibility`

**General rule:** Safe v1 uses block-local visibility. The first write in a block declares a new variable only when no visible variable of the same name exists in an enclosing block. Otherwise the write is an assignment to the visible outer variable. A variable is visible only in its declaring block and child blocks.

**Diagnostics:** Emit an explicit generator error when a variable is used outside the block where it was declared.

**Notes:** This intentionally rejects PHP-style block escape for locals and closures.


## NOTE-017

**Source:** [generators/php/specs/rules.md:249](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## 9. Control Flow
>
> ### Supported
> - `if / else / elseif`
> - `while`
> - `do-while`
> - `for`
> - `switch` (known mismatch remains documented separately)
>
> ### Rejected
> - `foreach` over `vector_t` lowers to an indexed C++ `for` loop
>
> ---

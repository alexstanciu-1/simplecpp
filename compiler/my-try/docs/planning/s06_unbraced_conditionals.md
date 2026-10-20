# S06 single-statement conditional bodies
Doc Status: supporting

S06 is implemented and verified on dev002. `if`, `elseif` and `else` accept a
braced block or one supported statement. Nearest-unmatched-if binding and existing
branch-local scope are preserved. No runtime implementation or retained model
structure changed.

The unchanged original strict fixture now reaches **S09: unresolved `strpos`**.
That is a remaining whole-program blocker, not an S06 parser failure. This report
does not claim an end-to-end pass for that fixture.

## Contract and implementation

The [normative conditional contract](../../../../specs/conditional_statements_v0_2.md)
defines clause binding, scope and lazy evaluation. In nested unbraced conditionals,
the inner recursive parse consumes its own `elseif`/`else` before the outer arm can
claim a clause. Braces can close the inner body and change which conditional owns
a subsequent clause. `else if` continues to normalize to an elseif arm, including
comments between tokens.

`Parser_Run::conditional_body` creates the existing `block_node` for one statement,
with its real statement token bounds. It does not invent source brace tokens.
`body_statement` shares the existing local-declaration guard with braced blocks.
The retained if/block model, semantic preparation, branch environments, completion
analysis and C++ emission remain unchanged. This is a parser-owned syntax extension,
not a new semantic traversal or a backend special case.

Each arm retains its own lexical environment. Typed declarations shadow within the
arm; untyped writes update the nearest visible binding or introduce a branch-local
name. Names do not escape or merge across arms. Reached conditions execute once,
later arms remain lazy, and all arms are still semantically prepared. Returns,
exceptions and unnumbered transfers use the existing owners and restrictions.

## Verification

Host: `alex-ai@dev002.travelfuse.ro`; snapshot
`/home/alex-ai/unbraced-conditionals/20261020-01`; starting commit `bfa9f25d`.
PHP 8.5.11 uses CLI fallback because FPM differs. Clang 18.1.3 compiles native
fixtures. Shared runners use twelve refilled slots with independent suites
overlapping. No full operator matrix or native compiler rebuild ran.

- Baseline replay reproduces `Expected '{'` for the original fixture at byte 121
  and a builtin-free conditional at byte 10.
- **46 dedicated native programs** prove simple and mixed arm forms, nearest-if
  binding, nested elseif chains, both inner and outer else attachment, comments,
  one-statement extent, branch shadowing, outer writes, return completion,
  lazy/once-only conditions, skipped and selected errors, and conditional transfers
  inside existing braced loops.
- **23 rejection controls** retain malformed clauses, empty semicolon bodies,
  colon/endif syntax, unbraced loops, invalid transfers, local declaration guards,
  branch-local name isolation, self-initialization, incomplete returns and semantic
  validation of skipped arms.
- Three AST span checks verify that single-body blocks cover exactly their nested
  statement, with inner else ownership retained in the AST.
- Four retained source variants change braces, clause attachment and elseif
  spelling. **Eight recovery cycles** cover parser and preparation failures, retries,
  output withholding, repaired/fresh equivalence and no-op stability. The four
  edited programs execute natively, including the changed result when braces
  redirect else attachment.
- The existing control-flow regression suite passes **12 PHP suites and 137 native
  programs**, covering AST/invariants, completion, conversions, tokenizer,
  incremental C++, token generations, preparation and specialization dispatch.

The original source remains byte-identical to pinned v0.1 and was not rewritten
with braces or substituted API calls. Current parsing succeeds, then preparation
reports unresolved `strpos`. S09 remains open, including any later API blockers
that this first failure still masks.

Acceptance records **440 expected command exits** across baseline, dedicated and
regression runs. All 6,272 remote source hashes match; all 218 runtime files remain
unchanged. The 183 native programs are distinct from PHP preparation assertions.

## Evidence and corrections

[Results](s06_results.json), [all attempt logs](s06_evidence.json.gz),
[initial manifest](s06_source_initial.json), [first candidate manifest](s06_source_candidate.json)
and [final manifest](s06_source_final.json) preserve provenance. The
[static closeout audit](s06_audit.json) checks exact source hashes, command exits,
unchanged runtime files, original fixture bytes and issue status. Documentation
closeout follows the tested snapshot.

The first dedicated run passed preparation but two native observers failed because
the generated entry boundary caught the expected runtime exceptions. Adding the
existing test-only entry-observation marker corrected those observers. No compiler
exception behavior changed. The older control suite contained two expectations
that brace-free conditionals reject; they are now positive cases, while malformed
and empty-body negatives remain. Failed attempts are retained rather than replaced.

## Closure and non-goals

S06 closes the syntax issue. Overall original-issue closure becomes **25/64
(39.0625%)**, an issue count rather than an effort estimate. Original S06 failure
records remain historical evidence; the original fixture still has the separately
tracked S09 API blocker.

Unbraced loop bodies, empty semicolon bodies, colon/endif syntax, numbered
break/continue and other unsupported statement forms remain separate. S09, runtime
helpers, condition conversion semantics and broader control-flow redesign were not
part of this implementation. No new implementation blocker was found in S06's
approved scope.

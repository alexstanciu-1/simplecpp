# Accounting application: my-try compatibility audit
Doc Status: planning

Date: 2026-10-09. Checkout HEAD observed: `9fda49a7e1ec86e92ee5312449d6ec1b133d023c`.
This is a live working-tree checkpoint while another agent develops the compiler,
not a claim about a frozen release. No compiler or application source was edited.

## Outcome

The current 15-file accounting app does not compile unchanged with my-try. The
first failure is tokenization of an apostrophe inside a line comment. After only
temporary source adaptations, parsing reaches preparation and exposes six distinct
diagnostic families. Focused probes establish additional missing builtin binding
and isolate which similar forms work. The application's concrete value-capturing
closure **does generate, compile natively, and execute successfully** in isolation.

No native build or execution of the full accounting application was reached.
The legacy 998-check run remains the baseline, not a my-try result. This audit
does not rebuild the compiler itself natively.

## Confirmed gaps

### Follow-up — 2026-10-10

- Compound RHS fields/calls and ordinary value effects now use snapshot-before-RHS
  lowering, with shared field-owner retention and preserved compound intent.
  `/tmp/compound-effects-20261010-1` passes four PHP tasks and three generated
  programs. The latest unchanged-source retry at `/tmp/accounting-compound-20261010`
  reports only indexed compound mutation; full application emission is still blocked.

- Boolean strict identity now reuses the same-type scalar identity path; all truth
  pairs and ordered value snapshots pass alongside string/boxed regressions in
  `/tmp/scalar-identity-20261010-final`. The latest unchanged accounting preparation
  at `/tmp/accounting-bool-20261010/logs/prepare.stderr` reports only compound RHS
  restrictions and indexed compound mutation. No full emission/build/run yet.

- Same-type string `===`/`!==` now passes preparation and generated execution,
  including embedded NUL and ordered snapshots. Evidence:
  `/tmp/string-identity-20261010-final`. Accounting retry and retained-owner
  inspection in `/tmp/accounting-string-20261010` identify the remaining exact-type
  error as boolean identity in `GeneralLedger::validate`:
  `($line->debit === 0) === ($line->credit === 0)`. This is a separate scalar
  admission gap, not a reason to expand the string slice silently. Compound RHS
  fields/calls and indexed compound updates also remain blocked.

- Full unchanged-source preparation was retried at `ca5aed84`, exposing string
  strict identity, class `$this` container admission, compound RHS fields/calls,
  and indexed compound updates. Isolated probes are retained in
  `/tmp/accounting-mytry-20261010-current/probe-logs/probes.stdout`.
- The class `$this` receiver repair removes that diagnostic group in
  `/tmp/accounting-this-20261010/logs/prepare.stderr`; the other three remain.
  Both field-container suites pass PHP checks and ten generated programs.
  The full application has not reached C++ emission/build/execution.

- Builtin bindings resolved: focused generated-native execution proves
  `count($entry->lines)`, `$this` vector/hash fields, UTF-8 byte-counting `strlen`
  and empty strings. Catalog/generic checks pass in `/tmp/accounting-builtins-20261010`.
- Container field writes/appends now reuse the generalized indexed receiver and
  ordinary property/place facts, retaining shared owners across key/RHS effects.
  Tests: `compiler/my-try/tests/field_container_writes.py`. Writable instance fields,
  inline field chains and existing nested index paths are covered.
- Keyed field probes/removal now reuse that storage admission and owner retention:
  `isset`, collection-only `empty`, and associative `unset`, preserving lazy keys.
  Focused proof: `/tmp/field-probes-20261010-1` and
  `compiler/my-try/tests/field_container_probes.py`. Fields reached through indexed
  objects remain a separate follow-up.
- Eager binary fields/calls now use ordered converted values, including
  `$this->debits - $this->credits` and `value() === 2`. This is the approved
  `specs/expression_evaluation.md` amendment, not a numeric-promotion change.
  Fixed-width mixing remains a separate gap.
- Plain indexed shared-object property writes now work through the normal selected
  property contract, retaining the object across RHS effects. Inline element mutation,
  compound updates and native reference exposure remain gated. Focused runner:
  `compiler/my-try/tests/indexed_properties.py`.
- The table below preserves the original audit observations; it is not a current
  assertion that these repaired gaps still fail. No full accounting run is claimed.

| Area | App example | Observed my-try result | Owner / possible response |
| --- | --- | --- | --- |
| Line comments | `src/money.phs:9`, apostrophe in `company's` | Tokenizer treats comment text as tokens; ordinary `// account comment` also rejects | `02_tokenize/tokens.php`; comment/trivia handling |
| Property type placement | `public $lines vector<JournalLine> = [];` in `src/core.phs:55` | Expected semicolon; `public vector<int> $items = [];` emits | Record parser; source spelling adaptation is plausible |
| Conditional spelling | `} else if (...) {` in `src/assets.phs` | Expected opening brace; `elseif` emits | Conditional parser; small source spelling adaptation |
| Output statements | `echo "...";`, also multi-argument output in checks | Even single-argument `echo` rejects | Parser plus output lowering/runtime binding; CH14 area |
| Standard source call bindings | `count($entry->lines)`, `strlen($supplier_code)` | Both unresolved; `count` also fails for a local typed vector | Shared callable registration/preparation and backend binding; existing runtime functions alone do not establish source support |
| Container mutation rooted at a field | `$this->lines[] = $line`, `$this->accounts[$code] = $record` | Indexed assignment requires a direct local or parameter root | Shared indexing/place preparation; crucial for all subledgers |
| Keyed probes/removal rooted at a field | `isset($this->accounts[$code])`, `unset($this->matches[$key])` | Property-path probes/removal deferred; local-hash `isset` emits | Shared keyed-path preparation |
| Eager binary operands containing fields/calls | `$this->debits - $this->credits`, `$a->value === 2`, `value() === 2` | Binary operation requires order-independent operands | Operator effect/sequencing admission; stabilizing operands into locals passes in isolation |
| Field writes through an indexed handle | `$entry->lines[1]->credit = 100` in rejection checks | Assignment requires stable storage | Assignment/place preparation; direct `$object->field = value` and `+=` emit |
| Fixed-width operands with canonical int | `AccountingDate` has `int64` fields, used directly in comparisons/arithmetic | Integer binary operation requires canonical int operands; isolated `int64` local comparison reproduces it | Integer operator contract; explicit `(int)` stabilization passes in isolation |

These are scoped observations, not blanket claims that objects, containers,
operators or closures are unsupported. In particular, ordinary field assignment
and field compound assignment emit, while container indexing rooted at a field
does not. Field arithmetic fails even when both field and literal are canonical
`int`; fixed-width mixing is a separate issue.

Do not bypass field-root mutation by blindly copying a vector/hash to a local:
typed containers have value semantics. Such a rewrite requires truthful mutation
and writeback behavior, especially around aliases and failure paths. A shared
property-root path implementation is preferable to many accounting-specific
workarounds. Similarly, source rewrites that lift calls from short-circuit
expressions must preserve lazy evaluation and source order.

## Evidence and reproduction

The audit used a copied source tree under `/tmp/accounting-mytry-audit/source`.
All compiler output and native cache paths were also under that temporary root.

```sh
php8.5 compiler/my-try/main.php \
  --s2s /tmp/accounting-mytry-audit/source --entry main.phs \
  --output /tmp/accounting-mytry-audit/emitted
```

Stages, whose diagnostics are preserved in the evidence directory:

1. Exact source copy: comment apostrophe tokenization failure.
2. Blank full-line comments while preserving line count: property declarations
   and output syntax reject.
3. Move property container types before variable names; split echo operands:
   single-argument echo still rejects, and `else if` is exposed.
4. Change `else if` to `elseif`; replace output with a temporary no-op typed sink:
   preparation reports order independence, field-root probes, unresolved `count`,
   indexed write roots, stable assignment storage, and canonical integer operands.
   The temporary entry also checks the expected 998 passing checks and returns the
   failure count; it never reached emission/execution and supplies no behavior proof.

Twenty-four isolated probes were run through the shared bounded task pool. Each
probe's source, exit code, and complete diagnostic are in
[probes.json](results/accounting_mytry_2026_10_09/probes.json). Eight emit C++ and
sixteen reject. Pool task success only means evidence was collected; it does not
mean the probe's source was accepted. The canonical application hashes are in
[source-manifest.json](results/accounting_mytry_2026_10_09/source-manifest.json).

The accepted closure probe was also published with `--output`, built with
`compiler/my-try/tools/build_cpp.php OUTPUT CACHE --no-pch -O0`, and executed.
It returned zero, testing a captured integer and an explicitly typed bool-returning
closure invocation. See [native result](results/accounting_mytry_2026_10_09/closure-native-result.json).
The other accepted probes establish emission only, not native execution.

## Suggested order

### Fixed-width follow-up — 2026-10-10

The agreed same-signedness integer-domain slice now covers the `int64` field plus
canonical `int` gap for ordinary arithmetic and numeric comparisons. Wider types
win; canonical `int` wins when present. Signed/unsigned combinations reject,
including unsigned values with ordinary signed literals. Power, strict identity,
bitwise/shifts and fixed-width compound/unary updates retain their prior bounds.
See [the language contract](../compact_layout_types.md#7-v02-integer-arithmetic-and-numeric-comparisons)
and [CH15 debt](../../compiler/my-try/docs/catalog/15_blocking_debt.md#ch15-integer-domains-01--integer-interoperability-and-overflow).
`tests/integer_widths.py` passed focused preparation/regressions and two generated
programs in `/tmp/integer-widths-20261010-final`; this is not a full accounting
workload run or a native compiler rebuild. The observation table above is historical.

### Indexed compound closeout — 2026-10-10

Existing-element indexed compound updates now reuse the shared indexed destination
model and ordinary compound computation. Keys execute once; the old leaf is copied
before RHS effects; complete path validation precedes write-back, without creating
missing entries. Compound append, indexed increment/decrement and fixed-width
compound arithmetic remain deferred.

The unchanged accounting sources now prepare, emit, compile and execute through
my-try: `CHECKS passed=998 failed=0` and `ACCOUNTING_WORKLOAD_PASS`.
Evidence: `/tmp/accounting-index-compound-20261010`, cold artifact build 75.082s,
19 compiled units and one PCH. This is generated-application validation, not a
native compiler rebuild. Focused indexed compound and field-write regression
programs also pass; see the CH06 checkpoint for commands and results.

### Original suggested order

1. Agree the shared property-root model for reads, indexed writes, keyed probes,
   and assignments through indexed handles. This is the largest practical blocker.
2. Review binary-expression sequencing/admission for field and call operands.
3. Register the bounded `count` and `strlen` source contracts; add output support.
4. Handle source trivia and settle property/conditional spelling compatibility.
5. Adapt or extend fixed-width integer operations according to the language contract.
6. Retry the complete source after these owners are ready, then compile and execute
   its C++ bundle against the legacy 998-check baseline.

The list is a confirmed lower bound: early failures prevent exhaustively testing
every later expression, preparation path, generated artifact and runtime behavior.

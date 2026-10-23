# SOURCE-01 uint32 boundary review
Doc Status: planning

Review of explicit uint32 annotations in stage/core PHP at d3c2768a4, with the source
repairs below. These are authored-PHP algorithm boundaries, not converter checks or
changes to independent Simple C++ numeric contracts. Synthetic fixtures are separate.

| Stored domain | Existing owner / disposition |
| --- | --- |
| Token byte offset/length | Initial tokenizer lacked a capacity gate. Check source byte length once before scanning; every nonempty token span and token count is then bounded by input bytes. Do not repeat checks per token. |
| Appended token offsets | Token_Buffer already bounds combined content bytes and token count before writes. Compute offset addition as int, then assign into uint32 storage; no additional check needed. |
| AST half-open token indexes | ast_node::set_span checks nonnegative/order/maximum before narrowing. |
| Global/module/source revisions | Compiler_Lifecycle clears participants at maximum, then increments. Compare and add in int; retain uint32 storage. |
| Collection/declaration revisions | Parser computes revision in int, clears occurrences on rollover, then assigns. Existing strict comparisons cast compact revisions to int. anonymous_callable_identity.revision is already ordinary int, so its comparison to a cast collection revision needs no new cast. |
| Semantic/concrete type IDs | Type_Identity validates positive uint32 IDs; allocator exhaustion flags preserve the last ID without incrementing past maximum. Fix allocator local comparison/addition to int. |
| Source exposure and C++ binding IDs | Constructors use Type_Identity::require_valid before storing compact IDs. |
| Integer width / floating storage and precision | Constructors validate positive widths, maximum and precision <= storage. Getters expose int. |
| Template minimum arity | Computed from the validated ordered parameter list, never an untrusted arbitrary numeric cast. No per-write guard pattern added. |
| Applied-type count | Increments only after allocation of a checked canonical ID; count cannot exceed successful allocated IDs. Perform addition in int before compact storage. |
| C++ specialization reference entry ID | Specialization interner bounds storage count before assigning the one-based reference; zero remains the explicit empty reference. |

## Evidence and findings

The extracted production allocator baseline on /tmp/type-identity-bounds-before-01
passed PHP but legacy emitted 0 after maximum; v0.2 rejected narrow integer arithmetic.
After normalization, /tmp/type-identity-bounds-after-01 passes both allocators across
PHP, legacy and both v0.2 modes. Expanded lifecycle proof passes in after-04, including
reset of retained module/source revisions. Supporting fixture records are explicit
stubs; this is not a native type registry or whole-compiler build.

/tmp/token-buffer-width-02 passes production Token_Buffer with retained token records
on all routes. First fixture missed the strings module in v0.2; corrected module
selection, not production workaround. /tmp/tokenizer-capacity-01 tests oversized input
through a host-only byte-length shim without allocating 4 GiB, confirms rejection and
reuse recovery, and passes tokenizer/publication/AST checks. This is not a real 4-GiB
native file test. PHP lifecycle/tokenizer/publication regressions also pass.

The expanded rollover fixture initially used a static uint32 literal initializer;
prelowered output emits a namespace-scope `[&]` conversion lambda, rejected by Clang.
Record this as a distinct backend bug. The final method proof assigns its endpoint at
runtime; production Model's static initialization is not claimed proved. Also,
type_catalog.php fails its C++ binding count assertion (26). An unchanged HEAD overlay
of all four changed production files reproduces that failure in
/tmp/source-numeric-catalog-baseline-01. Regression expectation reconciliation remains
open; do not call the full type-catalog suite green. Initial fixture type/local-name
collisions and static-index assignment were corrected before final execution.

The static-initializer backend repair now passes the restored literal-initializer
fixture across routes, plus ordinary/prelowered scalar/nullable/string/type-default
execution (see static_initializer evidence). Regression reconciliation remains open. This audit does not expand source uint32 checks into a blanket policy
or claim native execution of all production numeric consumers.

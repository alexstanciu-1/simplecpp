# LOCAL-01 completion integration audit
Doc Status: planning

This is an implementation map for the accepted LOCAL-01 contract, not additional
language authority. Required class locals may be declared before assignment; reads
require assignment on every reachable incoming path. Struct default construction
and explicit initialization for non-defaultable structs remain unchanged.

## Reused owner and identified gaps

`04_analyze/prepare/semantics/completion.php` already composes sequence, alternatives,
selection, loop and finally exits. Its transient statement_completion currently
carries only fallthrough/return/break/continue booleans. Assignment facts belong to
these distinct reachable exits, not a retained AST cache or a new CFG pass.

`bodies.php::prepare_local_storage` prepares the initializer before publishing a
new binding, and validates conversion before completing assignment. Reuse this
ordering: an assignment only establishes initialization after its RHS succeeds.
Use resolved storage identity rather than spelling, preserving lexical shadowing.

`expressions.php::prepare_reference` resolves both reads and one write-only use:
nested assignment resolves its destination before evaluating the RHS. A read check
inserted indiscriminately here would reject legitimate first assignments. Separate
storage lookup from read admission and preserve the existing prohibition on nested
writes introducing a declaration. Indexed/property writes still read their receiver.
Reference binding, take and explicit captures consume initialized values; inspect
arrow capture lookup independently because it bypasses ordinary expression reads.

`loops.php::prepare` currently prepares every header before its body, including
 do-while. Definite assignment must follow execution order: do-while condition facts
come from body fallthrough and continue paths. Body-local declarations must still
remain out of header scope. For updates also consume body fallthrough/continue facts.
A zero-trip loop retains its entry path; breaks carry their own assignment facts.

`exceptions.php::prepare_protected` prepares body then sibling catch. The catch must
start with conservative pre-try facts, not successful assignments from the protected
body. Its binding is initialized by the runtime despite being prepared with a null
initializer. Foreach bindings need the same explicit runtime-initialized treatment.
Finally entry must include exceptional paths; no whole-program throw/effect analysis
is planned. Its successful assignments can affect resumed normal/transfer paths,
but the current boolean finalized() alone cannot carry these facts.

## Required vertical proof

Test both ordinary and prelowered output: assignments in both if branches, one branch
returning, missing else rejection, self-read RHS rejection, write-only nested targets,
shadowing, receiver/reference/capture reads, zero-trip loops, do-while conditions,
break/continue/update paths, switch direct entry and fallthrough, catch binding and
failed RHS paths, finally writes and conservative finally reads. Preserve unreachable
semantic checking without inventing reachable initialization failures. Include struct
default/direct-construction controls and retained edit/reject/revert/reset recovery.

Implementation checkpoint: the mapped completion integration is now present, including
write-only lookup, expression branch isolation, do-while ordering and runtime binding
entry. Focused execution/rejection and retained recovery proofs pass in both modes.
See the execution ledger and local_assignment results for measurements and limits.

LOCAL-01 continuation: the first measured bootstrap exposed runtime-facade outputs
initialized by successful `take`. Output binding now uses write-only lookup, but
truth-conditioned assignment facts are not implemented yet. Do not mark LOCAL-01
complete: integrate the already documented nullable/Result success rules, including
negation/lazy conditions and early failure exits. In particular, result_or_bool true
sentinels do not assign the payload. No implicit ordinary out-parameter protocol or
general boolean-variable correlation should be inferred. The file-lock empty factory
is a separate runtime ownership/default-contract audit item.

## Next integration: conditional extraction

The measured facade usage is `if (!take($out, $failure, operation())) { throw ...; }`
followed by a required `$out` read. Existing `prepared_take::operation` distinguishes
nullable/falseable/structured-error success from boolean-sentinel results. The output
lookup must remain write-only. Add invocation-local true/false path facts through
condition preparation and existing expression preparation, not a second retained
AST analysis. For nullable/result_or_false/result, true establishes the payload;
false leaves it unchanged. For result_or_bool, true may be a sentinel and must not
establish payload assignment. Negation swaps condition paths; lazy &&/|| prepare
the RHS from the path that executes it; if/loop/conditional branches consume those
facts. Store no interprocedural effects and infer no relationship through a saved
boolean variable. Reuse registry result semantics and prepared operations, not type
names. Generic nullable payload admission remains the separate NULL-02 consumer.

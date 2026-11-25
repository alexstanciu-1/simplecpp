# Node-owned preparation facts
Doc Status: supporting

Recorded 2026-09-26. Binding, integer-literal and variable-reference nodes own their
prepared records. The C++ emitter follows syntax and reads those facts directly.
Reverse syntax links, binding initializer aliases and token-keyed fact maps are
removed. Cleanup traverses owned AST children and invokes each node's own method.

Verification: `python3 compiler/my-try/tests/run.py --results
/tmp/scpp-node-facts-php-01` passed: 71 PHP files linted, style passed for 72 sources
including the driver template, 19 LLVM fixture executions, 28 call executions,
sample exit 9 and all nine generated-C++ executions. `git diff --check` passed.
The focused S2S test also passed before the full regression run.

Lifecycle proofs cover direct node access and canonical type/declaration identity,
regeneration with fresh records, cleanup of retained old nodes during an update,
partial preparation failure, direct worker failure, emission failure, nested-node
cleanup and cleanup before syntax roots are replaced. Serialized syntax matches
its original state after derived facts are cleared.

Native compilation of the compiler itself was not run, per the user's explicit
instruction. Local AGENTS.md now makes that verification opt-in. This result does
not establish native behavior of the new cleanup overrides or weak declaration
fields; the prior native proof predates this change. No LLVM or validation feature
work is included. Production-source fingerprints are in source_hashes.json.

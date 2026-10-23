# Callable construction through explicit owned inputs
Doc Status: planning

Base abf1afc40 plus candidate hashes. User approved a source-only adaptation using
promoted signature/scope inputs and factories, with both routes proved before the
measured bootstrap. No converter, runtime or Simple C++ semantics changed.

Required signature_syntax and owned_signature_scope move from the trait declarations
to promoted properties on the two consuming classes. closure_node additionally
promotes owned_body_scope. The shared helper now initializes collections and marks
an already-owned scope; it no longer allocates required object fields. Concrete
fresh_node factories allocate fresh inputs before constructing each node. Parser
creation sites use them only where a new node was previously allocated; retained
node reuse is unchanged. Existing direct-construction tests use those factories.

The three small factories intentionally name concrete types; no late static binding
or reflective construction. They are named fresh_node because v0.1 lowers new through
an unqualified create<T> helper, which an authored create method hid. The first
native attempt also caught a reserved test-local name, explicit; renamed supplied.
These were source/fixture adaptations, not generator repairs.

## Proof boundary

callable_construction.php exercises actual production classes/scopes/storage: fresh
signature, scope and collection identities; function-boundary flags; closure body
parent link; and preservation of explicitly supplied objects. Seven focused host
tests pass, including closure_model retained syntax, scope reuse and recovery.
Existing closures.php failed an obsolete emitted-text expectation for std::function
both with baseline HEAD declarations/parser and with the candidate (correct output
argument supplied in both comparison runs). This is recorded, not repaired or
counted as passing. Initial invocation also omitted its required output argument.

tests/portability/callable_construction.py extracts the production constructors,
shared initialization/scope accessor and concrete factories into a dependency-bounded
fixture. Scope/signature dependency doubles isolate this construction protocol;
they do not prove complete native scope implementation. Real Storage is used and
actual host scope behavior is covered above. PHP and legacy/ordinary/concrete native
routes agree on freshness, mutation independence, scope linkage/marking and exact
input identity. Native task times 21.81/23.13/23.83 s; FPM socket startup unavailable,
so the shared runner used its normal CLI fallback. No whole native compiler claim.

## Bootstrap and stop boundary

81.76 s wall / 354308 KiB peak RSS (346.00 MiB) / 324 MiB PHP heap.
Removes signature_syntax initialization diagnostic, adds none. Preparation first
fails at constant_declaration_node::initializer. Parser_Run::constant_declaration
creates/reuses and collects the declaration before parsing the initializer, then
assigns or retains the expression. Changing this field to a constructor input
would affect parser/collector ordering and retained identity. Do not silently
reorder that flow or weaken the required field. Discuss the existing staged-storage
approach or a bounded source construction/publication redesign first. No fix to
this next blocker, and no full native compiler build, in this slice.

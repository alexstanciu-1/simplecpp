# Weak AST parents and retained source roots

Doc Status: supporting

This is a bounded executable lifetime proof, not a translated compiler or a new
ownership feature. The [bootstrap plan](../../specs/planning/v03_bootstrap_implementation_plan.md)
records the scope. Existing class/shared storage, weak fields, weak()/lock(), shared
upcasts and virtual dispatch suffice; compiler and runtime structures are unchanged.

The source fixture defines Node, Literal, Addition and Root, plus an independently
owned source snapshot. It constructs 1+2+3 with separate literal values and half-open
source ranges. The ordinary inherited setRange method assigns start/end fields;
attachment and range handling introduce no special language semantics. Addition
implements Evaluable, so the same tree is evaluated through a Node handle and through
a capability test followed by a checked interface cast. Literal takes the negative
capability path. Addition owns two child slots. Node observes its parent through
weak<Node>. External attachment passes the parent through an existing shared Node view
before creating the weak observer, so weak covariance and owning-this are unnecessary.
Attachment is a fixture convention, not compiler-enforced single assignment.

An operation holds the source root while it needs the entire ancestor chain. A child
can survive its tree and then find its parent expired. Locking one parent retains only
that parent's subtree, not its ancestors. Unchanged trees are reused intact; syntax
parents do not point to compilation sessions. A retained old root allows an old reader
to finish while a replacement tree is current. A source snapshot can survive independently
without keeping its former AST alive. None of this freezes old semantic facts or stable
symbol entries; their existing invalidation rules still apply.

The native harness simulates source-slot replacement with ordinary root handles and
checks expiry, inherited dispatch, identity, snapshot lifetime and abandoned candidates.
The candidate receives children through the source attachment helper before a native
exception abandons it. A retained child first locks that live parent, then observes
expiry after failure, while published root identities remain unchanged. Source
exception syntax is not added.
Weak observers keep allocation storage after payload expiry until those observers die.
Valgrind checks eventual release and invalid accesses across repeated lifetimes.

Separately, the resident PHP compiler edits the fixture's source files. Tests compare
retained and fresh artifacts/diagnostics, verify no-op reuse and body-only rebuilds,
and require native depfile rebuilding after layout edits with unchanged caller C++.
Required declaration deletion, repeated failure and restoration retain existing recovery
behavior. The full native lifetime probe runs initially, after both layout changes and
after deletion/recovery, including Valgrind when requested; native checks therefore
exercise parent locking after incremental rebuilding, not just tree evaluation. Manifest header inventory is compared with the actual files and fresh output.
Existing shared-view adapter and generation-retirement regressions remain responsible
for their publication and cleanup protocols; the fixture adds no generation manager.

Run from the repository root:

```sh
XDEBUG_MODE=off python3 -B compiler/tests/ast_parent_lifetime_native.py --memcheck
```

The harness checks cpp/php/js on Clang and GCC. Production PHP parent references and
GC policy are unchanged. Raw borrows, weak conversions, arenas, unique, compiler-enforced write-once
attachment/range semantics, historical semantic snapshots and native
threading remain deferred. This proof makes no performance-improvement claim.


## Checked staged writes and weak symbol entries

The [staged ownership example](../examples/staged_ownership/declarations.php.scpp)
uses ordinary private fields and checked operations to stage a parent link and
complete a function. The compiler rejects writes to those private fields from
other classes. The operation checks repeat writes at runtime.

```php
// Simple C++ PHP flavor. This operation is inside Syntax_Node.
if (!$child->parent->is_empty()) {
    throw Exception::make("Parent already attached");
}
// Validate the ancestor chain before storing the backlink.
$child->parent = weak($parent);
```

An empty backlink and an expired backlink both lock to an absent handle.
An expired backlink still has a control block, so `is_empty()` returns false.
The attachment operation leaves the backlink set for the rest of the child's
lifetime.

A function header can exist before its body. The completion operation validates
the declaration kind and range before attempting attachment. It records the
owning body handle, end position and completion flag after attachment succeeds.
An abstract declaration completes its range without a body. The PHP-hosted
compiler enforces the same transition with its existing end-position field and
rejects incomplete functions at file completion.

```php
$declaration = new Function_Declaration(4, false);
$body = new Function_Body(4, 16);
Function_Declaration::complete_body($declaration, $body);
$entry->publish($declaration);
```

The index owns its stable entry. That entry stores a weak reference to the
completed declaration. The source generation owns the declaration and the
declaration owns its body. Reparsing publishes a replacement in the existing
entry. A reader can retain the old source generation until it finishes.
Withdrawing a declaration clears the entry's weak reference and leaves the
entry available for later publication.

The [native proof](../examples/staged_ownership/proof.php.scpp) checks rejection
of cycles, repeated completion and reattachment after expiry, and publication
of unfinished functions. The [test harness](../tests/staged_ownership_native.py)
checks that direct private writes reject and that repair reuses unaffected
units, with entry sources in all three flavors. Caller-specific permission
to invoke completion, compiler-enforced write-once fields, and raw index
borrows remain separate language decisions.

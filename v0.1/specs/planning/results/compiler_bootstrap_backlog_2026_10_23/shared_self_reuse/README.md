# SELF-01 reuse audit and prelowering blocker
Doc Status: planning

This is experimental evidence, not implemented feature support. Starting revision:
5951486e7. The temporary production edits were removed after the concrete build
failed; the patch and runner remain under `/tmp/shared-self-reuse-audit-01`.
`probe.phs` records the exact source witness and `evidence.json` records the outcomes.
No bootstrap rerun is claimed because no production behavior change remains.

## What can be reused

Method_Preparation already resolves the current receiver separately from ordinary
variable lookup. Its prepared_receiver retains the originating method, including
through borrowed receiver captures. This permits direct constructor/destructor and
static-context rejection without a new callable-effect analysis. Ordinary shared
value boundaries can transport an acquired class handle; no new source ownership
family or automatic inline/unique promotion is needed.

A local prepared variable-reference operation can distinguish storage, borrowed
receiver access and shared self acquisition, avoiding the backend's existing
implicit collected_method-to-raw-this interpretation. The emitter can reuse the
runtime-operation specialization engine for a concrete acquisition helper. No new
source syntax, converter intrinsic or independently generic method is required.

The ordinary experiment reused runtime shared_self::retain_self and its existing
weak control block. It passes returned self through an interface/base view,
self arguments, local self assignment, mutation identity and retention after the
original handles are cleared (output 9:9:12). This does not establish all lifecycle,
copy, alias, borrowed-capture or invalid-acquisition cases.

## Verified missing owner

runtime/include/scpp/shared_self.hpp derives from std::enable_shared_from_this and
has a templated retain_self method. Adding this base to generated source classes
exposes template tokens through transitive application headers. The concrete build
fails the existing native token gate, even with acquisition itself moved behind a
concrete helper. Opaque payload wrappers alone cannot fix this: std::shared_ptr
initializes enable_shared_from_this only when it is an accessible actual base of
the managed type, not when it is stored inside an opaque member/base wrapper.

This requires a coordinated runtime allocation/observer representation change,
not another emitter spelling or relaxed template-free guard. The guard remains
unchanged. Existing v0.1 behavior must continue to use the same managed owner.

## Proposed bounded redesign, awaiting approval

1. Introduce a template-free shared-self base facade backed by a native weak-owner
   state. Preserve one shared observer per object across inherited/base/interface
   views, without a strong ownership cycle.
2. Bind that observer to the existing control block after successful complete
   shared construction and before publishing the handle. Audit shared/create and
   native shared_ptr adoption paths; conversions/aliasing must not create or replace
   ownership. Inline and unique instances remain unbound.
3. Copy/move construction of a new object starts unbound; assignment must preserve
   the destination object's owner identity. Shared allocation then binds fresh
   constructed copies. Constructor/destructor acquisition continues to fail.
4. Ordinary and prelowered retain operations alias the original owner to the actual
   adjusted receiver address. The generated concrete helper hides templates; the
   application-visible base does not expose template tokens.
5. Keep method/closure receiver borrowing and struct self-copy semantics unchanged.
   No call-graph effect inference, ownership promotion, shared-struct retention,
   new source ownership syntax or exact PHP weak cleanup emulation.

Owners touched: runtime self/handle/allocation support and its build/export boundary;
compiler prepared reference operations and class layout; ordinary/concrete transport;
legacy generator compatibility. This is wider than the expected local reuse slice.
Risks: copy/move owner identity, multiple-inheritance address adjustment, registration
order and exception safety, ABI/layout changes, added state/allocation cost, and
accidentally bypassed shared adoption paths. Validation must cover all of these,
retain the template-free gate, execute PHP/legacy/both-v0.2 fixtures, and measure
bootstrap time/memory after implementation. No exact workload multiplier is justified
until the runtime state representation and adoption audit are settled; SELF-01 is now
larger than the previous direct-runtime-reuse estimate.

Smallest alternatives: approve the coordinated facade/registration redesign, or
leave class self pending and continue independent SELF-02/nullability/source work.
An ordinary-only implementation or disabling the template-free gate would not
satisfy the accepted goal.

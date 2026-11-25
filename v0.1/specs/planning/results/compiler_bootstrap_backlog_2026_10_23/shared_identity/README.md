# Shared object identity admission checkpoint
Doc Status: supporting

Reuse audit: runtime/specs/spec.md section 7a and the profile matrix already define
compatible shared-object identity, including base-pointer adjustment and absent
handles. runtime/include/operators/identity/strict_identity.hpp implements it. The
v0.2 frontend previously fell through to canonical integer checking. Shared_Identity
now validates shared roles, optional class/interface payloads and observed ancestry.
Named prepared operations preserve the original operands and use existing ordinary
and prelowered binary transport/runtime helpers. No runtime change or new equality
contract is introduced. Unique/weak identity and structural comparison stay separate.

/tmp/shared-identity-01 passes eight tasks: both native modes and six pair rejections.
It covers same/distinct objects, compatible class/interface views, optional absence,
explicit shared structs, left snapshot before RHS clearing, and single evaluation.
Native ordinary 24.62 s, concrete 23.72 s. /tmp/shared-identity-retained-01 passes
both-mode ancestry edit/reject/revert/reset with output withholding and restoration.
/tmp/shared-identity-operators-01 passes the existing operator preparation suite.

/tmp/shared-identity-portable-02 includes the complete exception framework and runs
same/distinct exception helpers through PHP, legacy native and both v0.2 modes.
Ordinary 23.63 s, concrete 21.18 s, legacy 21.63 s. This removes the prior fixture's
exception declaration subset. It does not claim full string/collection/framework
coverage or a native compiler rebuild.

Bootstrap: 78.92 s wall time / 341,820 KiB RSS (333.8 MiB) / 312 MiB
PHP peak allocation. Preparation still fails first on required initialization of a
file field. The aggregated diagnostic texts are unchanged from the preceding source
adaptation checkpoint: the integer-operation diagnostic remains at other sites.
The focused exception-helper execution proves this repair; aggregate text alone
cannot identify repaired owners. No fatal/OOM, emission or native compiler rebuild.
Required fields, local flow and the remaining backlog remain open.

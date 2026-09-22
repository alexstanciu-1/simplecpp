# Template definition permission checking
Doc Status: supporting

Caller: Concrete_Preparation::init(), after accepted name resolution and before
concrete demand processing. This phase owns permissions under declared generic
contracts; it does not select concrete operations, assign layouts or build IR.

```text
Template_Checker                              main_check_templates.php
  init() -> select() [full rebuild or stale definition/dependency snapshots]
  run() -> Template_Worker::check() [each fixed definition_task]
             fields(); locals(); statements() body.php
             expression(); call() -> provider_call() [metadata member]; Terms    terms.php
  finalize() -> Template_Join::join()          join.php
  result() => Template_Set                    data/result.php

Terms::annotation(); field(); method()
  -> [action] read source/provider declarations and signatures; preserve symbolic parameter identity
  -> [action] capture exact declaration/binding dependencies

Instance_Set -> Template_Set::require_definition() [instantiation/body consumers]
```

Workers read fixed symbols, bindings and catalog. They traverse ordinary branches
and expressions iteratively, read callee signatures without entering callee bodies,
and own temporary type terms/locals. Definitions include inherited-parameter methods.
Concrete specialization cannot grant new permissions to a previously bare parameter.

Joins accept complete selected batches independently of result order, reject stale
provenance, reconcile current membership and retain unchanged rows. Results retain
proof dependencies/work counts, not symbolic scratch or copied ASTs. One ordinary
body increment reuses unaffected definitions; template/schema changes keep the
existing full-rebuild fallback. Storage/ABI limitations remain downstream.

Shared baseline eligibility belongs to
[type_model/Generic_Contracts](../type_model/generic_contracts.php); argument workers
and joins query it only after concrete lifecycle facts are available. See the
[implementation scope](../../../docs/details/generic_contract_implementation_plan.md).

Provider methods use the same symbolic receiver-owner lookup as source methods.
`Terms::provider_type()` interprets declared formal/self references and explicitly
mapped primitive names; `Template_Worker::provider_call()` checks the signature with
its receiver removed from the argument list at the declared position. Required
capabilities must fit the family's formal baseline before import. Const and mutable
passing remain semantic facts; no ABI or concrete substitution grants permission.

`Terms::forwarded_type()` prevents element guarantees from being mistaken for the
container's own copy/assignment guarantees. Nested dependent provider arguments and
forwarding a provider application as bare generic T remain unsupported until those
whole-type guarantees are modeled. `family_argument()` checks supported forwarding
under the default baseline; mutable borrowing of bare T remains outside that contract.

Gate 2 establishes member permissions, including dependent result forwarding.
`Terms::provider_value_use()` rejects whole dependent-container copying, assignment
and by-value parameters/results until their own lifecycle contracts are modeled.
Local dependent-family construction is still deferred with native demand preparation;
concrete family demands fail explicitly in Application_Worker. This phase does not
claim execution of provider members or lifecycle operations.

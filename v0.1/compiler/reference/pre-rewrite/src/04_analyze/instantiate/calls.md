# Instantiation workers and registry
Doc Status: supporting

Caller: [resolve_types/Concrete_Preparation](../resolve_types/calls.md), which owns
concrete readiness before signature/local selection. Original ASTs and bound
definitions remain unchanged.

```text
Concrete_Preparation                            ../resolve_types/main_prepare_concrete.php
  -> Constant_Worker::resolve(); Constant_Join::join()     constants.php
  -> Application_Worker::run(); Instance_Join::join()      applications.php; join.php
  -> Member_Worker::run(); Member_Join::join()             members.php; member_join.php

Instance_Join / Member_Join
  -> Instance_Store::allocate() -> Instance_Identities::allocate()
  -> Instance_Store::accept(); bind()                     data/store.php

Bindings::type(); value(); literal()                      bindings.php
  -> [action] read bound declarations, accepted applications and context arguments
  -> Integer_Literals::resolve() [literal decoding/range only]
```

Workers read `Instance_View`, unchanged during a selected batch, and return private
arguments, member targets or missing prerequisites. Joins validate the complete
batch before accepting it into the coordinator's private `Instance_Store`. Only
joins allocate exact identities and materialize argument types. The store updates
its record index as records are accepted and hands newly introduced contexts to
selection. One immutable `Instance_Set` is published after preparation; retained
snapshots are never changed or repeatedly reconstructed during discovery.

Member tasks describe either an ordinary concrete declaration or a demanded call.
Ordinary methods are checked independently of use. Dependent template methods
remain demand driven; their receiver becomes an ordinary borrowed parameter.
Record workers and joins remain in resolve_types and share the structural contract.

`Instantiation_Policy::load()` in policy.php reads the configured limit before
session cache selection. The coordinator supplies that fixed value to preparation;
standalone stage tests may load the default during init().

The registry retains exact keys and an allocation watermark; current membership
contains required concrete instances, including ordinary methods. Type-store
lineage separates numeric type IDs from other caches. `instance_context` owns
ordinary/instance context-ID encoding. See
[contracts and proof](../../../docs/details/explicit_instantiation.md).

Declaration member tasks also cover implicit constructor, destructor, copy and assignment body demands for
concrete template records. Member_Worker resolves the receiver from that fixed
record context; Member_Join validates ownership and concrete arguments before
allocating/reusing the method instance. Explicit lifecycle calls are source errors.

Provider storage families and operation definitions bind one explicit type argument
through Application_Worker. Instance_Join materializes their concrete descriptor
using resolve_types/Storage_Definitions; the registry owns `concrete_types`, with
`instance_type()` and `type_context()` queries shared by source and provider inputs.
These are provider definitions, without fabricated source ASTs or source bodies.

Source application acceptance first requires `Template_Set::require_definition()`.
`Application_Worker::run()` and `Instance_Join` use
`Generic_Contracts::missing()` after argument lifecycle prerequisites are ready.
The full baseline applies even to unused type parameters. `Instance_Set` retains
the accepted definition set; `Instance_View::template_checks()` exposes it to fixed
workers. See [definition checking](../check_templates/calls.md).

`Application_Worker::family_arguments()` handles ordered native family formals with
the same binding and generic-eligibility checks. `Instance_Join` revalidates those
arguments and allocates/reuses the semantic instance before storage exists.
`Concrete_Preparation` then schedules native preparation outside these workers.
Repeated occurrences share an instance; no layout guess or native tool invocation
makes an application ready here.

Member ownership now follows the retained type context for both source records and
prepared provider instances. Provider methods allocate ordinary method contexts;
these introduce no source body tasks. Native operation coverage is prepared once
after the concrete registry snapshot, before signature work. `Source_Lifecycle`
applies reserved source lifecycle spellings only to source-owned declarations.

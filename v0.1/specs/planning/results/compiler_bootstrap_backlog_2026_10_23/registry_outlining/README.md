# Registry-driven default outlining
Doc Status: planning

Candidate: 627458e34 plus CPP_Default_Constructors plan-based eligibility.
Reuse: settled prepared_sequence_construction/prepared_empty_construction and existing
property_default emission. Removed vector/hash identity gate; no new registry cache,
source admission, construction expression, or lifetime protocol. Other eligibility
exclusions remain intact. O2 output stays inline; O0 outlines accepted empty plans.

/tmp/registry-default-outlining-01: initial nine checks pass at O0.
/tmp/registry-default-outlining-02: all thirteen checks pass, preserving O2 coverage
and adding O0, ordinary/concrete modes, unfamiliar provider and Storage controls.
Two provider-holder creations call the factory twice, produce independent contents,
and preserve parameter-default factory totals. Checks assert outlining only at O0.
/tmp/registry-default-policy-01: retained policy-switch/default-edit/eligibility/fresh
reconciliation and four native optimization-level executions pass (51.70 s).

Bootstrap: 77.17 s GNU wall, 342424 KiB peak RSS, 312 MiB PHP heap. Preparation still
stops at syntax_node; diagnostic set unchanged. No OOM/fatal or native compiler rebuild.
This closes only the default-constructor name gate, not the blocking registry audit.

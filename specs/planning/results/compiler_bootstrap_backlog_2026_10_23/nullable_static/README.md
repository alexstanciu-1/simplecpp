# Nullable static qualifier checkpoint
Doc Status: supporting

Parent fa950bedf plus the nullable static-member lookup patch; source_hashes.json
identifies measured compiler sources. Reuse Type_Queries::receiver_payload for
nominal selection, retaining original qualifier evaluation. No new semantics.

nullable_static_qualification.py executes inherited static reads, constants, calls,
updates and ordered qualifier/RHS effects from absent nullable class values in
ordinary and concrete output (18.54/18.36 seconds). Qualified/static member retained
checks pass (1.85/2.18 seconds), including edit/recovery/reset. PHP's own null member
qualification behavior is not being changed or claimed as portable source behavior.

Bootstrap conversion succeeds; preparation reports the same diagnostic set and
first syntax_node constructor-initialization blocker. 74.47 seconds wall,
342944 KiB peak RSS, 312 MiB PHP heap. No native full-compiler build claimed.

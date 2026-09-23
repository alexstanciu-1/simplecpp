# Concrete member instance preparation
Doc Status: planning

28 PHP/native scenarios and 20 host invariants pass. Native build one passed without
correction. Final production bytes match native-pass source hashes.

Local/parameter/this receivers, ordinary/template declarations, provider-family
methods, pending inputs, exact identity reuse and complete-batch rejection are covered.
Final host purity also reuses the cloned-receiver rejection from the retained
concrete_preparation.php test. Whole-program preparation/body/backend execution remains
unproved; receiver definitions are explicit prepared fixture inputs.

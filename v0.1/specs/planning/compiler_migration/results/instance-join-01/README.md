# Instance application acceptance
Doc Status: planning

32 PHP/native scenarios and 16 host invariants pass on native build two, after renaming
a fixture factory that shadowed the generated create helper. Three cheap checker corrections preceded the PHP-ready checkpoint.
Final production bytes match native-pass source hashes.

Complete-batch validation, ordered allocation, exact identity reuse, source/provider
prerequisites, storage publication and concrete-context provenance are covered.
The retained whole-program explicit-instance test still depends on unfinished
preparation/body/backend stages. No complete pipeline is claimed.

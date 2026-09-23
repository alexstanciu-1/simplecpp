# Lifetime records
Doc Status: supporting

Temporary consumption, local exits, active-local rows and cleanup obligations now
have portable typed records. The preserved string-backed enums become explicit
numeric tags plus stable boundary-name codecs. Value_Lifetime preserves the exact
consumer-ID requirements for call arguments, conversions, operations and indices;
statement-boundary uses retain consumer zero. Local initialization zero continues
to identify incoming parameters. Local_Lifetime remains producer data: complete
range/body validation still belongs to the future Analyzed_Body owner.

The records remain immutable shared objects where their original constructor
contracts or worker stack/index identity matter. The new initialization facts use
compact value records separately. Neither choice claims that PHP readonly alone
establishes deep native immutability. The prototype's allocation_analysis carrier
is deferred to the resource-state migration, where its nested lane maps need an
explicit owner; it has not been dropped or claimed ready with these records.

A 1,105-case constructor/output matrix agrees in PHP/native with the actual retained
record classes, covering all 14 temporary end reasons, consumer and ID boundaries,
both cleanup subjects, local exits and active-local values. This does not prove
complete lifetime analysis or cleanup execution. The first native attempt stopped
at STAN on two terminal-throw return paths; a common explicit return preserves the
codec behavior. The first actual C++ build then passed.

Run `python3 compiler/tests/lifetime_records/run.py --results FRESH --target-checkout TARGET`.
Evidence: `specs/planning/compiler_migration/results/lifetime-records-01`.

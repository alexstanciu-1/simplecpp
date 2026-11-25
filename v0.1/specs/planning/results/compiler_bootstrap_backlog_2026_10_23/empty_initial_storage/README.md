# Temporary empty required-field storage
Doc Status: planning

Date: 2026-10-25. Baseline: 6ab5f0fde plus this committed slice. Source hashes
record the converted candidate. User approved initial storage only and a native
compiler plus second-generation acceptance target; that target remains incomplete.

Declaration preparation retains a field-only empty-storage fact. Constructor,
inherited-constructor and record-default checks share the existing construction
owner. No nullable conversion, parameter default, converter or C++ emission change.
Required frontend types remain distinct; assign-before-use is an author obligation.

Proofs:
- required_fields.py: 24/24 ordinary/prelowered native and negative tasks pass.
- portability/empty_initial_storage.py: executable PHP plus v0.1 and both v0.2
  native modes pass; shared identity/mutation survive staged assignment.
- Explicit required null field defaults are PHS-only. Executable PHP omits the
  initializer because PHP rejects nonnullable null property defaults.
- No claim of all uninitialized reads being checked or full compiler execution.

Attempts: first required-field run exposed unsupported authored template syntax
and an expected-diagnostic mismatch; second exposed the inherited constructor
consumer and another deferred template form. Removed unsupported authored-template
fixtures and integrated inherited field-storage admission. Third run passes.
Portable run 1 predates inheritance fix (v0.1 passes, v0.2 rejects); run 2 passes.
Removed unused extraction helpers from test harness after the passing run.

Measured bootstrap: 184.33 seconds elapsed, 511908 KiB peak RSS (499.91 MiB),
486.5 MiB PHP heap. Conversion: 5.84 seconds, 199144 KiB peak RSS.
Input: 270 PHS files / 2002215 bytes. CLI fallback: FPM socket not permitted.
Native fixture builds overlapped the beginning; no isolated speed comparison.

Preparation still fails: 24 distinct messages, previously 96. Required shared-field
initialization errors clear. Remaining enum initialization and operation failures
are listed in bootstrap.json; newly reachable errors are not necessarily regressions.
Native compiler build/run and second-generation proof have not been reached.

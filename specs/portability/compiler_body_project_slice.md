# Project body checking
Doc Status: supporting

Body_Plan selects current source callables from accepted type signatures. It owns unique
fixed tasks and current publication order. Body_Validity is shared by selection and join:
body owner/instance, names/local association, canonical type rows, retained signature
identities and parameter IDs must remain current. Missing dependencies invalidate reuse.

Body_Join accepts exactly one current result per selected callable, then combines results
with reusable bodies in current signature order. Its inputs stay unchanged on success
or failure. Body_Set owns copied membership and shares immutable body rows. for_symbol
excludes concrete instance IDs; for_callable accepts either identity domain.

Body_Checker::check returns Body_Update with either a complete result or an attributed
semantic failure. Internal stale-state failures remain exceptions. No partial result set
is published. This synchronous facade follows the migrated template stage; the session
Step lifecycle adapter and complete pipeline coordinator remain to integrate.

```sh
python3 compiler/tests/body_project/run.py --results /tmp/body-project-NEW --target-checkout /tmp/scpp-json-240-probe
```

23 PHP/native scenarios prove fresh/full/warm agreement, identity reuse, reversed
completion, independent membership, dependency invalidation, malformed batch rejection,
semantic failure isolation and concrete method/template membership. Dependency-change
fixtures construct changed candidate associations deliberately; complete source-edit
orchestration is not claimed. Fixture plan comparison covers its literal/call/plain-record
shapes, not the complete debug-export contract.

Evidence: `specs/planning/compiler_migration/results/body-project-01`.
Body debug serialization and remaining storage/provider/managed paths stay separately
tracked. No lifetime or lowering readiness is implied by successful body publication.

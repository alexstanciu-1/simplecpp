# Body checking context
Doc Status: supporting

`check_bodies\Body_Context` validates a Callable_Input against accepted source name
bindings, its resolved signature, local-type association and template permission.
It owns only per-worker retained dependency maps; it does not mutate the canonical
snapshot or check statements. An absent local association is valid only for zero
local bindings. Signature reads capture exact representation/provider/storage rows
and canonical parameter IDs, retaining their return and parameter type dependencies.

`retain_type` follows fixed-array and storage element edges with a dense iterative
queue. Seen IDs terminate cycles and duplicate work. Struct fields are not implicitly
visited. Pending/unknown definitions fail; discard the context after error. Returned
maps copy membership and share immutable rows. Final Checked_Body receives those
retained maps, not this mutable context or its full source snapshot.

Run:

```sh
python3 compiler/tests/body_context/run.py --results /tmp/body-context-NEW --target-checkout /tmp/scpp-json-240-probe
```

23 PHP/native scenarios exercise input identity, template permissions, provider
signature identity, local reads, snapshot isolation, closure and store purity.
Four host cases run the prototype's real closure method. Its constructor is bypassed
only to isolate that algorithm; this is not an end-to-end source-checking proof.
Evidence: `specs/planning/compiler_migration/results/body-context-01`.

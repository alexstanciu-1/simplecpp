# CURSOR-01 first integration checkpoint
Doc Status: supporting

Parent 73f52393e plus the shared-conversion metadata/factory slice. Source hashes
identify measured compiler input. Shared relation requirements are checked after
inference using existing ownership and observed ancestry. Generic owner factories
retain exact owner return contracts. No Storage covariance or cursor conversion.

Ten tasks pass in tests/storage_cursor.py: ordinary/concrete native widening,
identity/mutation/key/valid/advance and four negative families in each mode.
Ordinary native fixture 14.89 s; concrete 20.13 s. Core runtime-catalog and type-catalog
regressions pass. Additional interface, renamed-provider, metadata validation,
missing-contract diagnostic, retained ancestry and portable-route proofs remain open.

Bootstrap 78.28 s wall / 343704 KiB RSS / 312 MiB PHP heap. First syntax_node
constructor-initialization blocker remains. The unresolved Storage_Cursor template
diagnostic disappears; preparation now also reports non-void read_next reaching the
end. That newly exposed blocker is recorded, not repaired in this slice. This is
not a successful full compiler build or completed CURSOR-01.

Follow-up provider proofs: class/base and interface cursors execute with original and
renamed Storage/Storage_Cursor source exposures in ordinary and concrete output.
All 15 tasks pass; native fixture ranges 19.39–24.97 seconds. Unknown relation names,
missing destination and unknown relation fields reject during catalog ingestion.
The retained two-file test independently passes (0.52 seconds): removing ancestry
invalidates admission and output on both initial and no-change retry; restoring it
recovers byte-identical output, and preparation reset agrees in both output modes.
Both runners are now assembled into storage_cursor.py for future use. Tests only,
so the preceding production bootstrap measurement remains current.

Remaining CURSOR-01 work: explicit missing-contract/source-target diagnostics,
portable PHP/legacy parity, and any unproven metadata/ownership boundary cases.

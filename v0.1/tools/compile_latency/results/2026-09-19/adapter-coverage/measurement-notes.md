# Measurement notes
Doc Status: planning

A read-only generated-tree snapshot audit ran during the first `f1d3a846`
measurement (the native log ended at 17:36:47 UTC; snapshot files were copied
at 17:36:44 UTC). Snapshot verification rejected the changing input tree and
no type-publication transformation was applied. The copy did not mutate the
active candidate, but its filesystem load overlaps timing. Preserve the initial
three trials separately and repeat this case after the serial batch, with no
snapshot work running. Use only that controlled repeat for the final table.

Controlled repeat completed successfully: native median 3.240 seconds, three
trials. The final coverage table uses this repeat. Initial trials are retained
under `excluded-overlapping-filesystem-audit/f1d3a846/` and are not counted twice.

# Shared symbol integration evidence
Doc Status: planning

All 68 registered PHP/native stages pass on the current 156 production files.
`cumulative-native-summary.json` records the exact target and audited source hashes.
The final integrated symbol-origin proof contains 24 scenarios (`summary.json`).

The first 27 stages were retained after checking their staged production bytes.
The provider-family harness dependency failure is preserved separately; it was
corrected and rerun before completing the remaining stages. Per-stage summaries
and native outputs are under `cumulative-native/`. Phase timings and correction
counts are in `timing.json`.

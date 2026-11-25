# Nested nullable Result checkpoint
Doc Status: planning

Reuse: nullable wrapper registration supplies result_payload; the payload's copyability
still controls copies. No conditional result_payload dependency is needed. Runtime
result_core and recursive CPP_Wrapper_Transport already own storage/transport. The
prelower erased-handle shortcut now applies only to nullable conversions, and plain
wrapper probes publish the existing outer-presence facts beyond enum payloads.

Evidence: /tmp/result-nullable-04 passes 13 tasks (ten native programs, two rejection
cases, registry). Covers three Result families, success containing absence, failure
preserving output, true sentinel without write, unfamiliar tray without inner Result
capability, class identity and direct extraction. The shared test provider's index
writer now accepts generic integer keys, matching its reader and concrete ABI.
/tmp/result-nullable-retained-01 passes changed payload/reject/nochange/revert/reset
in both modes. /tmp/result-nullable-read-final passes ordinary/concrete/legacy native
output_read plus the real PHP fs_output_read helper. Absent, empty, present and invalid
path outcomes agree. The legacy fixture uses a typed function because top-level legacy
integer returns otherwise become invalid native main returns. No converter extension.

The full output suite has no obsolete read exclusion. Legacy passes; v0.2 stops on the
separate required lock passed before initialization. This slice does not claim that
combined publication flow passes or that the compiler itself was rebuilt natively.

Bootstrap: 74.34 s GNU wall time / 341424 KiB peak RSS (333.4 MiB) / 312 MiB PHP peak.
Stops first on required file-field set: initialization. Removes the aggregate Result
payload-capability diagnostic and adds none. No fatal/OOM or emission. Early-stop
measurements are not full compiler compilation costs. Snapshot hashes are included.

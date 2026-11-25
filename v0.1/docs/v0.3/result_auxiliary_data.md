# Optional Result auxiliary data
Doc Status: planning

Recorded 2026-10-04. Proposed for v0.3; not a current language/runtime contract.

A Result could optionally reference an extra structure carrying debug information,
captured output, benchmarking/timing data, and results at intermediate checkpoints.
The reference would be absent (null) by default so ordinary calls need not allocate
auxiliary data.

Decide in v0.3 whether to extend the existing Result or introduce another Result
family. Representation, pointer ownership/lifetime, copying/sharing, allocation cost,
thread safety, access API and interactions with take remain open. This proposal does
not reserve a field or change current Result layout, success/failure states or take
semantics. Auxiliary data must not accidentally become the success/failure discriminator.

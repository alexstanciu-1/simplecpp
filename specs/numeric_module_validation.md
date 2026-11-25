# Numeric/byte and generated-output validation

Doc Status: supporting
Date: 2026-10-30

- Release configure/build succeeded; all 55 CTests passed. This includes existing
  scalar, string, carrier, vector, ownership, packed, mixed/dynamic and JSON/datetime
  checks, plus new byte consumer, module-admission and cleanup contracts.
- The focused UBSan byte consumer, numeric contracts and cleanup tests passed.
- Source-only production profile proof passed core, numeric, all three optional
  modules, datetime+JSON, datetime alone, returning to core, and JSON alone. It
  verifies generated type sets, removed module files, demo execution, separated
  library symbols, and unchanged-build stability.
- PHP compiler tokenizer, parser and CLI tests passed unchanged.

Byte proofs cover one-byte representation, explicit integer casts and range
failure without destination modification, bitwise behavior, invalid shift counts,
and absent arithmetic/float/bool conversions. Every optional integer width requires
numeric activation; activation does not emit unrequested widths. Uint8 and byte
remain separate requested identities. Unsupported floating widths and char8 reject.

Cleanup proofs cover obsolete files and empty directories, extension filtering,
read-only checks, legacy/malformed/version-mismatched manifests, full regeneration,
path validation and non-traversal of symlinks. Rendering/admission failure precedes
filesystem cleanup. Filesystem failure during emission is not transactional.

Wide-number regression inputs now live in an explicit numeric publication fixture,
independently of the default core surface. Reviewed frozen artifact changes are
limited to inventory hashes for the versioned manifest and input fingerprints for
numeric activation. Existing generated C++ and prepared-model hashes remain intact;
no whole-baseline recapture was used to accept implementation drift.

PRJ selection/enforcement, source-level byte lowering, byte string helpers, more
floating widths and atomic/concurrent publication are outside this proof boundary.

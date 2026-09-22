# External process service
Doc Status: supporting

This component has no compiler-stage dependencies. Both application bootstraps
load it directly; the preparation tool can move with this component independently
of compiler lowering or backend preparation.

```text
LLVM_Toolchain; Layout_Preparation; Clang_Toolchain
  -> Tool_Process::__construct()               process.php
  -> ready(); result()
  -> close() / __destruct()
```

One instance owns one process group, private input/output streams and a deadline.
Callers supply command arguments, input, launcher and timeout. The service starts,
polls, collects and cleans up tools; it makes no language or ABI decisions. Runtime
preparation includes this service's source in its implementation fingerprint.

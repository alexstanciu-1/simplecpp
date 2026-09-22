# Source diagnostics
Doc Status: supporting

Shared support, not a separately scheduled compiler stage.

```text
Parser / semantic worker [invalid source]
  -> Source_Error::__construct()              diagnostics.php
    -> [action] attach file/span and format line/column message

main.php [catch Throwable]
  -> [action] print diagnostic to stderr and return failure
```

Stages throw; the coordinator preserves accepted snapshots on failure. Internal
contract and I/O errors may use other exception types. Source_Error carries
source context, not a recovery or logging engine.

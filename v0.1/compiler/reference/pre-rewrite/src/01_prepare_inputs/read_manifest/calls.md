# Read manifest call map
Doc Status: supporting

Compiler_Session prepares a disk or virtual manifest first. utilities/manifest_syntax.php owns JSON
validation; data/result.php owns the shared normalized snapshot. A .phs input supplies only
an explicit source selection and entry, without reading source bytes. No filesystem work occurs in the
constructor.

The following are ordered lifecycle calls, not calls between siblings.

```text
Manifest_Reader                                  main_read_manifest.php
  init() -> [action] resolve project input path
  run() -> [action] prepare explicit source selection [if .phs]
        -> [action] read JSON; Manifest_Syntax::parse() [otherwise]
  finalize() -> [action] complete Project_Manifest
  result() => Project_Manifest
```

status() and supports_run() are always available. Result/store access requires
finished status; invalid timing throws without changing the state.

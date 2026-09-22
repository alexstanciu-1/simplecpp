# Console calculation
Doc Status: supporting

Prepare the configured runtime once, then compile the example from the repository
root:

```sh
php src-runtime-preparation/main.php
php src/main.php examples/runtime_console/main.phs --runtime-package generated-runtime --output /tmp/scpp-console
printf '41\n' | /tmp/scpp-console
```

Output:

```text
answer=42;bytes=9
```

The source uses metadata-exposed calls for input, strict parsing, integer
formatting, concatenation and byte length. Invalid integers or empty EOF stop with
a clear error. LF/CRLF is removed; a final unterminated line is accepted.
See the [contracts and proof](../../docs/details/runtime_console.md).

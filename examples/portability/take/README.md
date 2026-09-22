# Portable PHP take sample
Doc Status: supporting

A small executable PHP source converted file-to-file into a strict PHP++ project.
The shared function policy supplies a tool-maintained import block in every file.
The source uses bare calls such as `take_nullable(...)`; imports are not selected
manually per file. The PHP bootstrap loads the namespaced implementations.
It demonstrates nullable extraction, preserving an output on absence, accepting
zero as a falseable success value, and boolean-result extraction.

From the repository root:

```bash
# Execute the PHP approximation.
php -d auto_prepend_file="$PWD/tools/php_portability/runtime/bootstrap.php" examples/portability/take/php/main.php

# Synchronize the project-wide imports and generate/update PHP++ source.
php tools/php_portability/sync_imports.php examples/portability/take/php
php tools/php_portability/convert.php examples/portability/take/php examples/portability/take/phpp

# Compile and run the PHP++ project.
(cd examples/portability/take/phpp && php ../../../../bin/scpp.php run)
```

Expected output from both programs:

```text
Nullable value: 42
Absent; previous value preserved: 42
Zero is a successful result: 0
Boolean success
```

Edit [php/main.php](php/main.php) and regenerate. The generated `phpp/main.phs`
and conversion manifest are ignored derived files. The checked-in
[project configuration](phpp/prism.json) selects the strict PHP profile.
It uses the current CLI's legacy configuration filename until the v0.2 naming
migration is implemented. Native build artifacts remain in the ignored `.prism/`.

# Getting Started

## 1. Install the CLI

Use the platform installer:

- Windows: `install\windows.cmd`
- Ubuntu/Debian: `./install/ubuntu.sh`
- macOS: `./install/macos.sh`

Then open a new terminal and verify:

```bash
scpp --version
scpp --doctor
```

## 2. Transpile one file

```bash
scpp input.php
```

The current CLI prints generated C++ to stdout.

## 3. Example

Create `hello.php`:

```php
<?php

echo "Hello from Prism++\n";
```

Run:

```bash
scpp hello.php
```

## 4. Current boundary

This first CLI milestone gives you a stable repo-based user-local binary.
It does **not** yet solve the deliberate multi-file model.

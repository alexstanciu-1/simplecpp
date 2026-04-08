# Prism++

<sub><i>Too old to compromise. Still young enough to build it.</i></sub>

Prism++ compiles simple, high-level languages into controlled, deterministic C++.

It is designed for developers who want:
- the simplicity of scripting languages
- the performance and ecosystem of C++
- and fine-grained control over memory, data, and execution

without being exposed directly to the complexity and risks of raw C++.

---

## Overview

Prism++ is not a virtual machine, not a JIT, and not a full reimplementation of existing languages.

Instead, it defines a **constrained execution model** that maps high-level code into a predictable subset of C++.

This model focuses on:
- explicit and controlled data types
- well-defined memory behavior
- predictable runtime semantics
- gradual access to low-level capabilities

The result is code that remains easy to write and reason about, while compiling down to efficient native binaries.

---

## Goals

- Provide a simple and expressive surface syntax
- Preserve access to C++ capabilities (memory, threads, system APIs)
- Reduce exposure to undefined behavior through controlled abstractions (ongoing effort, continuously improving)
- Enable gradual refinement from high-level code to low-level control
- Keep runtime behavior predictable and inspectable

---

## Non-Goals

Prism++ is intentionally not:

- a virtual machine
- a JIT compiler
- a drop-in replacement for PHP or any other language runtime
- a full safety guarantee system

---

## Safety Model

Prism++ improves safety by constraining how programs interact with C++:

- Memory is accessed through controlled abstractions
- Type interactions are explicit and predictable
- Undefined behavior is minimized by design (ongoing effort, continuously improving)
- Runtime checks are used where compile-time guarantees are not possible

This results in a system that is **safer than raw C++**, while still allowing low-level control when needed.

---

## Example

```php
<?php

function add(int|float $a, int $b): float {
	return $a + $b;
}

echo add(10, 20), "\n";
```

Compiled C++ output is structured around:
- explicit types
- controlled conversions
- predictable execution

---

## Philosophy

Prism++ is built on a simple idea:

> High-level code and low-level control should not be mutually exclusive.

Developers should be able to start simple and progressively gain control,
without rewriting everything or switching ecosystems.

---

## Status

Prism++ is under active development.

The current focus is:
- defining the execution model
- refining type and memory semantics
- improving correctness and predictability
- establishing a deliberate project-rooted Ninja build path

---

## Installer Scripts

Starter installer scripts are available in:

- `install/windows.cmd`
- `install/macos.sh`
- `install/ubuntu.sh`

They install the required toolchain, create a normal user-local launcher install (`%LOCALAPPDATA%\Programs\scpp\bin` on Windows, `~/.local/bin` on Linux/macOS), and run the repo-based installer. The installed CLI command is `scpp`. On Windows, the installer now applies user PATH updates through PowerShell and falls back to `setx` if needed.
When `sccache` is installed, Prism++ build and test flows use it automatically as a compiler launcher.

## Name

“Prism” reflects the idea of transforming simple input into structured, precise output,
while preserving clarity and control.

---

## License

[To be defined]

## Installation

Platform installers now provision Ninja as part of the first-binary setup and attempt to provision `sccache` as well.
On Windows, the installer skips Git installation or upgrade when `git` is already available on PATH to avoid unnecessary conflicts with open Git shells.


## Current project build direction

Prism++ now has an explicit project-mode staging contract:

- `scpp init` creates `prism.json` and a local `.prism/` work tree
- `scpp build` is the first public build command
- build output is rooted under `.prism/build` and `.prism/generated`
- Ninja is the default backend
- the first public build target is one configured entrypoint -> one executable

The full deliberate multi-file model is still in progress, but the command shape and project layout are now fixed.

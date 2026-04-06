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

---

## Installer Scripts

Starter installer scripts are available in:

- `install/windows.cmd`
- `install/macos.sh`
- `install/ubuntu.sh`

They install the required toolchain, create the user launcher directory (`~/.d-app` or `%USERPROFILE%\.d-app`), and run the project installer.

## Name

“Prism” reflects the idea of transforming simple input into structured, precise output,
while preserving clarity and control.

---

## License

[To be defined]

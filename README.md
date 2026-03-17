## 1. Overview

Simple C++ S2S is a **source-to-source (S2S) multi-language compiler**.

It consists of:
- A **runtime library (C++)** responsible for:
  - type management
  - operator handling
  - casting
  - memory abstraction
- A **transcoder** that converts source languages into C++:
  - PHP (**in progress**)
  - Simple C++ (**planned**)
  - JavaScript (**planned**)
  - Python (**planned**)

The transcoder normalizes input languages into a unified intermediate representation before generating C++ code.

The generated C++ code is then compiled using a standard C++ toolchain.

---

## 2. Language Definition

Simple C++ is a **C++-inspired intermediate programming language** defined as:
- a **restricted subset of C++ syntax**
- with **additional safety and usability constraints**

It is intended as a **controlled intermediate representation**, not a full C++ replacement.

---

## 3. Design Goals

### Core Principles
- Simplicity over completeness
- Readability over expressiveness
- Predictability over flexibility

### Objectives
- Maintain **low cognitive overhead**
- Be accessible to **junior developers**

### Safety
- No user-visible pointer semantics (only managed abstractions)
- Memory is **automatically managed**
- Out-of-bounds access is **checked**
- **Partial memory safety**, aiming to improve over time

### Interoperability
- Compatible with **C and C++**
- Leverages existing **C/C++ ecosystem**

### Language Constraints
- No templates (user-level)
- Limited and well-defined type system

---

## 4. Rationale

Simple C++ is designed to:
- remain easy to read and understand
- avoid complex ownership or lifetime systems
- avoid garbage collection
- leverage the existing C/C++ ecosystem

---

## 5. Implementation Strategy

### 5.1 Runtime Library

The Simple C++ runtime uses modern C++ features to enforce constraints.

### 5.2 Memory Model

- No use of raw pointers
- `new` is disallowed
- All heap allocations are wrapped in managed constructs (e.g. `shared_ptr`)
- No user-visible pointer control

### 5.3 Data Passing Rules

The following types are passed by **const reference (`const &`)**:
- string
- array
- map (`std::map`)
- unordered_map (`std::unordered_map`)

### 5.4 Safety Guarantees

- Array indexing is **bounds-checked**
- Memory management relies on shared ownership semantics

#### Limitations (Explicit)
- Does **not prevent reference cycles**; `weak_ptr` must be used correctly where needed
- Full memory safety is **not formally proven** at this stage

---

## 6. Status Notes

- Ownership model: **work in progress**
- C/C++ interoperability details: **work in progress**

---

## 7. Scope of this Document

This document provides a **high-level introduction**.

Formal definitions (grammar, AST, semantics) are defined separately in:
- `SPECIFICATIONS.md`
- Additional implementation documents
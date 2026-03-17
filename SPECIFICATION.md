# Project Specification: Simple C++ (scpp)

## 1. Project Overview
**Simple C++** is a high-performance S2S compiler designed to convert a
strictly-typed subset of PHP into modern, memory-safe C++20. The project
aims to provide the ease of PHP’s syntax with the execution speed and
resource management of C++, creating a "managed" C++ environment.

## 2. Core Goals
* **Performance:** Generate C++ code significantly faster than interpreted 
  PHP while remaining readable.
* **Type Safety:** Enforce strict typing at the PHP level to ensure 
  predictable and optimized C++ generation.
* **Memory Safety:** Implement a hybrid memory management strategy mimicking 
  PHP’s garbage collection without full GC overhead.
* **Traceability:** Maintain a strict 1:1 link between requirements, 
  library implementation, and unit tests using REQ-IDs.

## 3. Key Architectural Choices & Rationale

### A. Namespacing: The `scpp` Root Presumption
* **Choice:** All generated code is wrapped in a base `scpp` namespace.
* **Rationale:** To prevent collisions with the global C++ namespace and 
  allow the project to "own" common type names (like `string`). It 
  establishes a "Clean Room" where `scpp::string` can behave differently 
  than `std::string`.

### B. The "Smart Wrapper" Type System
* **Choice:** Use custom classes (`int_t`, `float_t`, `bool_t`) instead of 
  raw C++ primitives.
* **Rationale:** This allows the library to enforce PHP-specific behaviors 
  (like truthy/falsy string evaluation) while blocking unsafe implicit 
  conversions that lead to silent bugs.

### C. Explicit Casting & Conversion [REQ-001]
* **Rule:** Implicit string-to-numeric assignments are forbidden.
* **Mechanism:** Constructors for string-to-numeric conversion are marked 
  `explicit`, and string assignment operators are `delete`-ed.
* **PHP Syntax:** Requires explicit casts: `$i = (int)"10";`.

### D. Strict Null Safety [REQ-002, REQ-003]
* **Rule:** Arithmetic or assignment from a `null` value to a non-nullable 
  type must fail immediately.
* **Mechanism:** `scpp::optional<T>` throws `std::runtime_error` on 
  conversion to `T` if the value is `null`. No silent defaults to `0`.

## 4. Traceability Matrix (Requirement IDs)

| ID | Category | Description |
| :--- | :--- | :--- |
| **REQ-001** | Casting | Explicit `(type)` cast required for string conversions. |
| **REQ-002** | Null | Assignment of `null` to non-nullable type throws error. |
| **REQ-003** | Math | Arithmetic operations on `null` values throw error. |
| **REQ-004** | Boolean | Boolean casts handle PHP strings ("true", "1", "on"). |

## 5. Technical Constraints
* **Strict Typing:** All parameters and returns must have type hints.
* **C++ Version:** Target is **C++20**.
* **AST Version:** Utilizes **php-ast version 85**; `AST_CAST` detection 
  relies on node `flags`.

## 6. Future Roadmap
* **`scpp::array`:** Implementing associative arrays with `foreach` support.
* **String Concatenation:** Overloading `.` operator logic.
* **Standard Library Bridge:** Curated PHP-to-C++ function mappings.

# Project Specification: Simple C++ (scpp)

## 1. Project Overview
**Simple C++** is a high-performance S2S compiler designed to convert a strictly-typed subset of PHP into modern, memory-safe C++20. The project aims to provide the ease of PHP’s syntax with the execution speed and resource management of C++, creating a "managed" C++ environment.

## 2. Core Goals
* **Performance:** Generate C++ code that is significantly faster than interpreted PHP while remaining readable.
* **Type Safety:** Enforce strict typing at the PHP level to ensure predictable and optimized C++ generation.
* **Memory Safety:** Implement a hybrid memory management strategy that mimics PHP’s garbage collection without the overhead of a full GC.
* **Interoperability:** Ensure the generated code can be seamlessly integrated into existing C++ projects without namespace collisions.

## 3. Key Architectural Choices & Rationale

### A. Namespacing: The `scpp` Root Presumption
* **Choice:** All generated code is wrapped in a base `scpp` namespace.
* **Rationale:** To prevent collisions with the global C++ namespace and to allow the project to "own" common type names (like `string` and `vector`) for the purpose of operator overloading. It establishes a "Clean Room" environment where `scpp::string` can behave differently than `std::string`.

### B. Memory Management Strategy
We have implemented a four-tier parameter and return strategy to balance performance and safety:
* **Small Primitives (int, bool, float):** Passed by **Value**.
    * *Reason:* Faster than reference/pointer overhead for data under 64 bits.
* **Complex Value Types (strings, arrays):** Passed by **Const Reference (`const &`)**.
    * *Reason:* Prevents expensive deep copies of large buffers while maintaining a "read-only" contract.
* **Objects/Classes:** Managed via **`std::shared_ptr`**.
    * *Reason:* Provides reference counting that closely mimics PHP’s object handles, ensuring objects stay alive as long as they are needed.
* **Explicit PHP References (`&`):** Converted to **C++ References**.
    * *Reason:* Supports PHP’s native `by-ref` functionality for mutable parameters.

### C. The "Composition" Wrapper Approach
* **Choice:** Using class composition (wrapping `std` types) rather than `using` aliases.
* **Rationale:** C++ does not allow adding operators to types in the `std` namespace. By wrapping `std::string` inside `scpp::string`, we can overload the `+` or `.` operators to provide PHP-style concatenation and mixed-type comparisons.

### D. Strict Shadowing Prevention
* **Choice:** Compilation with `-Wshadow -Werror`.
* **Rationale:** Since we pass strings by `const &`, the S2S compiler explicitly forbids "accidental" modification of parameters. This forces developers to be aware of their memory usage and intent, aligning with the "Strict Type Awareness" goal.

## 4. Technical Constraints
* **Strict Typing:** Every function parameter and return type must have a PHP type hint; untyped code will trigger a `S2S compiler Error`.
* **C++ Version:** Target is **C++20** to utilize modern features like `auto` return type deduction and improved move semantics.
* **AST Version:** Utilizes **php-ast version 85**, which optimizes built-in types into node flags for faster processing.

## 5. Future Roadmap
* **Instantiation:** Transitioning `new` to `std::make_shared`.
* **Resource Management:** Wrapping C pointers (like `FILE*`) into `shared_ptr` with custom deleters for automated resource closing.
* **Standard Library Bridge:** Creating a curated list of PHP-to-C++ function mappings within the `scpp` namespace.

# Runtime Design Note

## Goal
Provide a controlled semantic backend for Simple C++.

## Public Surface
- null_t
- bool_t
- int_t
- float_t
- string_t
- nullable<T>
- vector<T>
- shared_p<T>
- weak_p<T>
- unique_p<T>
- create<T>()
- shared<T>()
- unique<T>()
- weak(x)

## Internal Surface
Uses std:: internally (string, vector, optional, shared_ptr, unique_ptr, weak_ptr, etc.)

## Codegen Contract
Generated code:
- stays inside scpp
- wraps values explicitly
- uses `create<T>()` for default ownership
- may use `shared<T>()` or `unique<T>()` when an explicit ownership form is required
- derives non-owning references through `weak(x)`
- avoids raw C++ features

## Wrapper Contract
Each wrapper defines:
- storage
- constructors
- allowed operators
- forbidden operations

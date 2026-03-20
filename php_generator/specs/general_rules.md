# Simple C++ Runtime & Conversion Assumptions

## 1. Namespace
All generated C++ code must be emitted inside:

```cpp
namespace scpp {
	// generated code
}
```

## 2. Provided Runtime Types (inside `scpp`)

### Primitive-like types
- int_t -> signed 8-byte integer
- bool_t -> C++ bool
- float_t -> signed 8-byte floating point

### Wrapper / heavy types
- string_t -> wrapper around std::string
- vector_t -> wrapper around std::vector

### Null type
- null_t -> custom type
- null -> inline constexpr null_t null {};

## 3. Runtime Functions
- create<T>()
- shared<T>()
- weak<T>()
- unique<T>()

## 4. Runtime Classes
- nullable<T>

## 5. Literal Conversion

- int → static_cast<int_t>(...)
- float → static_cast<float_t>(...)
- bool → static_cast<bool_t>(...)
- string → string_t("...")

## 6. Assignment Rules

- First assignment → auto
- Reassignment → no auto
- All literals always cast

## 7. Null Rules

- $a = null → error
- nullable allowed:
  - nullable<int_t> a = null;

## 8. Nullable Mapping
- ?T → nullable<T>

## 9. Functions

- Parameters MUST have types
- Missing return type → auto

## 10. Type Mapping

- int → int_t
- float → float_t
- bool → bool_t
- string → string_t
- vector → vector_t
- void → void

## 11. Passing Rules

### by value
- int_t, float_t, bool_t
- nullable<int_t>, nullable<float_t>, nullable<bool_t>

### by const &
- string_t, vector_t
- nullable<string_t>, nullable<vector_t>

## 12. Return Rules

- primitives → by value
- heavy:
  - return by value unless safe reference

## 13. Operators

- . → +
- echo → std::cout <<

## 14. Null coalescing

$b ?? 1 → (b != null) ? b : static_cast<int_t>(1)

## 15. Errors

- stop + report location


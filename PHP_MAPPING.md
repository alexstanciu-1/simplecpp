# PHP_MAPPING.md

## 1. Purpose

This document defines how **Simple C++ PHP** source code is mapped into the
Simple C++ semantic model.

Simple C++ PHP is:
- PHP-like in syntax
- stricter in semantics
- not intended to be fully PHP-compatible
- normalized to the Simple C++ model during S2S transformation

Anything not explicitly defined in this document or in the core Simple C++
specification must be rejected by the Simple C++ toolchain.

Rejection may occur either during S2S transformation or during generated C++
compilation, depending on where the rule is enforced in the current implementation.

The intended long-term direction is to move source-language-visible errors earlier into
S2S diagnostics where practical.

---

## 2. Position in the Project

The first implemented source frontend is:

    Simple C++ PHP → C++

Simple C++ remains the target semantic model.
Simple C++ PHP is a source language that maps into that model.

This means:
- source syntax may resemble PHP
- runtime semantics follow Simple C++
- compatibility with normal PHP is not a goal by default

---

## 3. General Mapping Principles

### 3.1 No PHP fallback semantics

The transpiler must not silently preserve PHP semantics where they conflict with
Simple C++.

Examples of behaviors that must not be inherited automatically:
- PHP loose comparisons
- PHP implicit coercions
- PHP truthiness rules outside explicitly defined mappings
- dynamic typing behavior not defined by the Simple C++ model

If a construct is not explicitly supported:
- the Simple C++ toolchain must reject it

### 3.2 Typed target model

Simple C++ PHP maps into a typed target language.

That means:
- declarations are normalized into typed Simple C++ declarations
- missing source-level types are normalized to `auto`
- explicit source annotations are treated as declarations, not hints

---

## 4. Variable Declarations

### 4.1 Untyped declaration

Source:

    $a = 12;

Normalized form:

    auto a = 12;

Generated C++ form:

    auto a = (int_t)12;

### 4.2 Explicit typed declaration

Source:

    $a /** int */ = 12;

Normalized form:

    int a = 12;

Generated C++ form:

    int_t a = (int_t)12;

The inline annotation is treated as:
- an explicit declared type

It is not treated as:
- a hint
- an assertion
- a post-inference check

### 4.3 Nullable typed declaration

Source:

    $a /** ?int */ = 10;

Normalized form:

    nullable<int> a = 10;

Generated C++ form:

    nullable<int_t> a = (int_t)10;

In Simple C++ PHP:
- `?T` maps to `nullable<T>`

It is not treated as:
- a dynamic union
- loose PHP nullability

---

## 5. Function Signatures

### 5.1 Nullable parameter and return types

Source:

    function my_func(?string $x): ?string { ... }

Normalized form:

    nullable<string> my_func(nullable<string> x) { ... }

Generated runtime-oriented form:

    scpp::nullable<scpp::string_t> my_func(scpp::nullable<scpp::string_t> x) { ... }

### 5.2 Type meaning

Function annotations in Simple C++ PHP are:
- explicit declared types

They are not optional metadata.

### 5.3 Missing types

Where a declaration does not specify a type and the mapping rule allows it:
- `auto` is injected during normalization

Function-specific defaulting rules should be defined separately when needed.

---

## 6. Object Creation

### 6.1 Source-level object creation

Simple C++ PHP uses PHP-like syntax:

    $my_obj = new My_Class();

### 6.2 Semantic normalization

This maps to the Simple C++ object-creation model:

    auto my_obj = create<My_Class>();

`new` in source code is:
- a semantic construct
- not raw C++ allocation

Generated code must not emit raw C++ `new` for language-level object creation.

### 6.3 Explicit ownership helpers

The runtime also exposes explicit ownership helpers:

    auto a = shared<My_Class>();
    auto b = unique<My_Class>();

These helpers do not change the source-level meaning of `new`.
The default lowering of source-level `new` remains:

    create<T>()

unless a later source-language rule explicitly requires a different ownership form.

`weak_p<T>` is not a primary allocation result.
Weak references are derived from an existing owning value:

    auto w = weak(a);

---

## 7. Nullability

### 7.1 Source nullability

Nullable source types written as:

    ?T

must map to:

    nullable<T>

### 7.2 Null value

Source null-like values must map into the Simple C++ `null_t` model.

They are then interpreted according to:
- `SPECIFICATIONS.md`
- `CASTING.md`
- `OBJECT_COMPARISON.md`

---

## 8. Code Generation Rules

### 8.1 Namespace

Generated C++ code must remain inside:

    namespace scpp

### 8.2 Explicit runtime entry

Generated values must enter the runtime model explicitly.

Examples:

    auto x = (int_t)12;
    auto s = (string_t)"text";

### 8.3 Managed object creation

Generated object creation must use:

    create<T>()

for default ownership,
or:

    shared<T>()
    unique<T>()

when an explicit ownership form is required by the language rule being lowered.

Weak references must be derived from an existing owning object:

    weak(x)

Raw C++ allocation must not be emitted for language-level object creation.

---

## 9. Toolchain Rejection Policy

The following must be rejected by the Simple C++ toolchain:
- unsupported PHP constructs
- unsupported coercions
- unsupported dynamic behavior
- any construct not explicitly defined by the mapping rules or core spec

Rejection may occur either:
- during S2S transformation
- or during generated C++ compilation

This depends on where the rule is enforced in the current implementation.

The intended long-term direction is to move source-language-visible errors earlier into
S2S diagnostics where practical.

## 9.1 Generated C++ Compilation Check

The Simple C++ PHP toolchain must also perform a generated-C++ compiler check.

If the generated C++ does not compile, the toolchain must treat the overall transformation as failed.

This allows the current implementation to keep the PHP frontend lighter while still enforcing
Simple C++ semantic constraints through the hardened runtime and C++ compiler where needed.

The goal remains:
- predictability
- semantic clarity
- full compliance with the Simple C++ model

---

## 10. Notes

Simple C++ PHP is intended to be:
- familiar to PHP developers
- stricter than PHP
- aligned with the Simple C++ language design

It is not intended to be a copy-paste-compatible PHP replacement.

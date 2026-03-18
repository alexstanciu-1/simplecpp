# SEMANTIC_MATRIX.md (Normalized --- Authoritative)

## 1. Purpose

This matrix defines all **allowed and forbidden operations** in Simple
C++.

If an operation is not explicitly listed → it is **forbidden**.

This document must align with: - SPECIFICATIONS.md (normative rules) -
tests (executable behavior)

------------------------------------------------------------------------

## 2. Result Type Convention

-   Arithmetic → returns value type (`int_t`, `float_t`)
-   Comparison → returns native C++ `bool`
-   Assignment → returns assigned type

------------------------------------------------------------------------

## 3. Arithmetic Operators

### 3.1 int_t

  LHS  RHS   int_t     float_t     bool_t   null_t   string_t   pointer
  ---------- --------- ----------- -------- -------- ---------- ---------
  int_t      ✔ int_t   ✔ float_t   ❌       ❌       ❌         ❌

------------------------------------------------------------------------

### 3.2 float_t

  LHS  RHS   int_t       float_t     bool_t   null_t   string_t   pointer
  ---------- ----------- ----------- -------- -------- ---------- ---------
  float_t    ✔ float_t   ✔ float_t   ❌       ❌       ❌         ❌

------------------------------------------------------------------------

### 3.3 bool_t

  LHS  RHS   any
  ---------- -------------------
  bool_t     ❌ all arithmetic

------------------------------------------------------------------------

### 3.4 string_t

  LHS  RHS   any
  ---------- -------------------
  string_t   ❌ all arithmetic

------------------------------------------------------------------------

### 3.5 null_t

  LHS  RHS   any
  ---------- -------------------
  null_t     ❌ all arithmetic

------------------------------------------------------------------------

### 3.6 pointer types

  LHS  RHS   any
  ---------- -------------------
  pointer    ❌ all arithmetic

------------------------------------------------------------------------

## 4. Comparison Operators

### 4.1 Numeric

  LHS  RHS   int_t    float_t
  ---------- -------- ---------
  int_t      ✔ bool   ✔ bool
  float_t    ✔ bool   ✔ bool

------------------------------------------------------------------------

### 4.2 bool_t

  LHS  RHS   bool_t
  ---------- --------
  bool_t     ✔ bool

------------------------------------------------------------------------

### 4.3 string_t

  LHS  RHS   string_t
  ---------- ------------------------
  string_t   ✔ bool (equality only)

------------------------------------------------------------------------

### 4.4 null_t

  LHS  RHS   null_t
  ---------- ------------------------
  null_t     ✔ bool (equality only)

------------------------------------------------------------------------

### 4.5 pointer types

  LHS  RHS   same wrapper      other wrapper
  ---------- ----------------- ---------------
  pointer    ✔ bool (==, !=)   ❌

------------------------------------------------------------------------

### 4.6 Forbidden comparisons

-   pointer relational (\<, \>)
-   cross-type comparisons not listed
-   numeric vs string
-   numeric vs null

------------------------------------------------------------------------

## 5. Assignment Operators

### 5.1 Basic

  Type   Assignment
  ------ ------------
  all    ✔ allowed

------------------------------------------------------------------------

### 5.2 Compound (int_t)

  Operator   Allowed
  ---------- ---------
  +=         ✔
  -=         ✔
  \*=        ✔
  /=         ✔

------------------------------------------------------------------------

### 5.3 Compound (float_t)

  Operator   Allowed
  ---------- ---------
  +=         ✔
  -=         ✔
  \*=        ✔
  /=         ✔

------------------------------------------------------------------------

## 6. Conversion Matrix

  From  To   int_t                 float_t      bool_t   string_t   null_t   pointer
  ---------- --------------------- ------------ -------- ---------- -------- ---------
  int_t      ---                   ✔ explicit   ❌       ❌         ❌       ❌
  float_t    ✔ explicit (to_int)   ---          ❌       ❌         ❌       ❌
  bool_t     ✔ explicit            ❌           ---      ❌         ❌       ❌
  string_t   ❌                    ❌           ❌       ---        ❌       ❌
  null_t     ❌                    ❌           ❌       ❌         ---      ❌
  pointer    ❌                    ❌           ❌       ❌         ❌       ---

------------------------------------------------------------------------

## 7. Conditional Usage

  Type     Allowed in condition
  -------- ----------------------
  bool_t   ✔
  others   ❌

------------------------------------------------------------------------

## 8. Enforcement Notes

-   Forbidden = must not compile
-   Missing operator = forbidden
-   Explicit conversions only

------------------------------------------------------------------------

## 9. Completeness Rule

Every valid operation must: - appear in this matrix - be tested - be
implemented or rejected

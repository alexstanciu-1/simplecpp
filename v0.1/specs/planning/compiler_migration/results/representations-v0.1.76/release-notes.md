# v0.1.76 release notes
Doc Status: historical

Copied from the verified release checkout CHANGELOG.md.

## 0.1.76 - 2026-09-19

### Fixes

- Fixed `new Struct()` to construct a value for typed and inferred uses, including cross-file and imported declarations, while preserving shared ownership for ordinary classes (#229). Struct construction arguments and ownership-wrapper construction now produce source diagnostics.

### Additions

- No unrelated additions; this patch clarifies and implements no-argument struct value construction.

### Breaking Changes

- Inferred `new Struct()` expressions no longer acquire accidental shared ownership. Copies now follow the documented struct value semantics. Ordinary class construction is unchanged.

### Migration Notes

- Update the compiler and rebuild affected projects to regenerate C++. Use `new Struct()` without arguments; use the existing typed/keyed initializer form to initialize fields explicitly. Custom struct constructors are not supported.
- If shared object identity is required, use an ordinary class rather than relying on the previous struct-lowering bug.
- Existing advisory STAN limitations for imported aliases and ambiguous short names remain; this patch does not change the broader STAN name-resolution model.

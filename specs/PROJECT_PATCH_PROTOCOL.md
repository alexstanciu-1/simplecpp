# Project Patch Protocol (MANDATORY)

## 1. Source of truth
- Always use the full attached project
- Do NOT reconstruct files from memory

## 2. Scope
- Modify ONLY required files
- No unrelated refactors
- Preserve style (tabs = 4 spaces)

## 3. Output
- Always return FULL project as ZIP
- No large code in chat
- No partial diffs unless requested

## 4. Integrity
- No truncation
- Verify includes, symbols, namespaces
- Be careful with:
  - generator templates
  - escaping
  - nested expressions

## 5. Validation (MANDATORY)
- Syntax check (PHP)
- Compile check (C++)
- Test the provided failing case

## 6. Consistency
- Follow existing naming and patterns
- No new abstractions unless requested

## 7. Reporting
- List:
  - files changed
  - what was fixed
  - what was tested
- Keep it short

## 8. Uncertainty
- Ask BEFORE implementing if unclear
- Do NOT guess behavior

## 9. Delivery guarantee
- ZIP must work without manual fixes

## 10. Double-check
- Re-check modified files twice
- Focus on:
  - generator output
  - reference semantics

## 11. No silent behavior changes
- Any semantic change must be explicitly stated
- If behavior differs from previous version → highlight it

# NetBeans Theme Match Notes

Purpose: record the desired target look for matching the user's NetBeans PHP editor style inside the VS Code PHS extension workflow.

This is a working reference document for theme tuning and grammar-scope tuning.

## Source Context

- NetBeans profile export: `tools/vscode_phs_extension/export_nb_theme/`
- VS Code base theme currently used for approximation: `Amphirion`
- Primary target editor family: NetBeans PHP dark editor

## Font Target

- font family target from NetBeans profile label: `Monospaced`
- preferred VS Code font stack after manual tuning: `"Courier New", "Monospaced", Consolas, monospace`
- font size: `13`

## Color Targets

Colors are recorded in RGB order first, with hex equivalents for easier VS Code mapping.

### 245, 246, 255

- hex: `#F5F6FF`
- use for:
  - identifier in namespace declaration
  - identifier in trait declaration
  - identifier in class declaration
  - identifier in method declaration
  - identifier in function declaration
  - identifier after `->`, for example `$var->prop`
  - identifier in `const`
  - name of a constant when used
  - operators
  - punctuation and delimiters such as `;` `{}` `[]` `()` `!` `\`

### 138, 186, 210

- hex: `#8ABAD2`
- use for:
  - keywords such as `trait`, `public`, `private`, `protected`, `return`, `isset`, `unset`, `echo`

### 255, 205, 153

- hex: `#FFCD99`
- use for:
  - variables
  - argument names

### 160, 204, 133

- hex: `#A0CC85`
- use for:
  - string literals

### 255, 153, 255

- hex: `#FF99FF`
- use for:
  - numbers

### 255, 153, 153

- hex: `#FF9999`
- use for:
  - `<?php`
  - `?>`
  - `<?=`

## Notes For VS Code Mapping

- Matching this look depends on both:
  - VS Code theme/token override settings
  - the PHS extension's TextMate grammar scopes
- If a token still looks wrong after palette tuning, the likely cause is incorrect scope classification in the extension grammar.
- The goal is to keep this document as the stable reference and tune settings/grammar against it incrementally.

## Likely Scope Families To Map

- identifiers and declarations
- operators and punctuation
- keywords
- variables and parameters
- string literals
- numbers
- PHP open/close tags when relevant

## Review Process

When adjusting the theme or grammar, compare the result against this file and update:

1. the RGB/hex target when the desired color changes
2. the token-group list when a category is split or clarified
3. the font target if the preferred editor font changes

## Saved VS Code Config

The current saved VS Code approximation lives here:

- `tools/vscode_phs_extension/docs/settings.json`

Treat that file as the repo-side snapshot of the preferred settings state after manual tuning.

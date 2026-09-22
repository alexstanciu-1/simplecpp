# 03 parse call map
Doc Status: supporting

Caller: Phases::run_parsing(). parse_file.php owns File_Parser, composed with handlers/ traits.
utilities/ owns syntax access/comparison. join.php assembles file results; data/ owns ASTs and
Frontend_Set. Empty selection still finalizes; full selection uses the same workers.

The following are ordered lifecycle calls, not calls between siblings.

```text
Parser                                  main_parse.php
  init() -> Parser_Selection::select(); create Frontend_Join
  run() -> File_Parser::parse() [each task]
  finalize() -> Frontend_Join::join()
  result() => Frontend_Set
```

status() and supports_run() are always available. Result/store access requires
finished status; invalid timing throws without changing the state.

Frontend_Join also accepts segments through merge()/finish(); join() provides the
common batch entry. Construction only captures inputs; validation starts on use.

Expression_Parsing preserves string_literal spans. Statement_Parsing::echo_statement()
retains ordered operand children; no runtime helper selection occurs during parsing.

Declaration_Parsing::struct_definition() preserves public typed fields. Expression_Parsing
uses complete_construction() and field_suffix(); semantic type contracts decide construction
and field eligibility after parsing.

Struct syntax: `Syntax_Access::struct_parts()` and `field_declaration_parts()`
(utilities/syntax_access.php) own name/field child roles. Collection, definition
preparation and record-result validation share these read-only views. Empty parsed
structs remain representable; semantic preparation rejects unsupported empty values.

## Template and compile-time syntax

```text
File_Parser::parse() -> statements()                parse_file.php; handlers/statements.php
  statements() -> declaration() [each item]         handlers/declarations.php
               -> statement() [if no declaration]  handlers/statements.php
  declaration() -> template_definition() [if template]     handlers/metaprogramming.php
                -> evaluated_function() [if constexpr/consteval]
                -> constant_definition() [if file constant]
  statement() -> constant_definition() [if local constant]
              -> control_statement() [if conditional]     handlers/control_statements.php

File_Parser::type_syntax() / expression()           handlers/expressions.php
  -> expression_operand(); application_suffix(); complete_expression() [as syntax requires]
```

All handler methods above execute on File_Parser through its composed traits.
Declarations and statement handlers request type/expression parsing as needed;
these are shared entries, not unconditional siblings of declaration dispatch.

Expression parsing uses one private iterative continuation stack for type syntax,
applications, calls, grouping and construction. The tokenizer's angle tokens
introduce ordered arguments; the parser never consults a family registry.

Syntax_Access composes utilities/metaprogramming_syntax.php for type roots,
applications, template parameters/declarations, evaluation wrappers and constants.
Existing annotation roles expose type_syntax_id. control_parts() uses condition=0
for if consteval and permits conditional alternatives where the grammar allows.
Syntax_Comparer and File_Frontend exports preserve all new kinds and spellings.
[Scope and shapes](../../docs/details/metaprogramming_parsing.md).

Reference parameter syntax uses `Type &$name` / `const Type &$name`. The declaration
handler retains logical variable/type children and an optional reference marker;
`Syntax_Access::parameter_parts()` validates and exposes it. The type process owns
its interpretation. Existing value parameters retain `$name Type` spelling.

`File_Parser::parse()` first builds a private angle-pair index through
`Binary_Syntax::angle_ends()` (utilities/binary_syntax.php). `expression()` reduces
postfix syntax and then binary operators by precedence through `binary_expression()`.
Balanced name suffixes remain template syntax; ordinary `<` shares the iterative
expression path with addition. No name resolution runs in parsing.

## Streaming member traversal

Syntax_Access::struct_members() constructs Struct_Member_Cursor in
utilities/struct_member_cursor.php. First advance() calls underlying_declaration()
and struct_parts(); subsequent advances follow matching siblings. current() exposes
only a positioned node ID. Semantic consumers stream via advance/current, except
record-result validation, which retains its existing materialized field vector.
Construction does not validate; exhaustion/failure closes traversal. No tree copy
or mutation occurs.

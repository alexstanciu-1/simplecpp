# Analysis
Doc Status: supporting

[collect/](collect/) records canonical names supplied by the frontend during parsing
and registers declarations in file-local scopes after a successful parse. Serialized
global publication belongs to `compiler/publication.php`.

[prepare/](prepare/) contains file preparation, literal preparation, attached-fact
access and cleanup, together with their data records. Specialized AST nodes own
prepared facts; completed-file records contain no token-keyed fact maps.

Shared [type definitions](../compiler/types) live at the application level and language
definitions are installed when Model initializes its language scope. Shared
[scope representation and lookup](../03_parse/scopes/) live with parsing.
[The C++ backend](../05_backend/cpp/) traverses syntax and reads the prepared facts.

`prepare/names.php` contains the earlier Name_Preparation experiment.
`prepare/templates.php` and template_check_context remain backend-coupled checking
debt. Their behavior is unchanged and further LLVM/validation work remains deferred.

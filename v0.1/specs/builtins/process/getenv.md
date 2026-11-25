# Builtin contract: getenv
Doc Status: normative

```php
getenv(string $name): result_or_false<string>
```

Returns a copied string when the target process environment contains the name,
including an empty string for a present empty value. An absent name returns the
false sentinel. The returned bytes are owned independently of later environment
changes. Name lookup and case sensitivity follow the target platform's environment
implementation; no Unicode or case normalization is applied.

An embedded NUL in the name throws `scpp::ValueError` before lookup. It must never
truncate the name and query its prefix. Other names are passed to the target's
`std::getenv`; no additional spelling restrictions are imposed. Ordinary argument
evaluation and value snapshots apply, with each name expression evaluated once.
This API does not synchronize concurrent process-environment mutation.

The core metadata binds `getenv` to `scpp::php::getenv` in
`lang/php/php_process.hpp`. Wider process APIs and shell behavior are separate.

The concrete core public binding is `scpp/process/getenv.hpp`, returning
`result_or_false_string`. The PHP adapter and generated public binding share the
private payload-factory provider in `scpp/detail/process_strings.hpp`. This preserves
the existing source contract and constructs the appropriate owned string directly;
no public native template or callback is exposed.

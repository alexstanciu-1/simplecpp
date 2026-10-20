# Builtin contract: microtime
Doc Status: normative

The strict compatibility surface admits both free calls and the explicit `php`
qualifier:

```php
microtime(): string
microtime(bool $as_float): float
php::microtime(): string
php::microtime(bool $as_float): float
```

The zero-argument form returns wall-clock epoch time as a fractional seconds field
with eight decimal places, a space, and whole seconds (`0.dddddddd seconds`).
The bool form with `true` returns wall-clock epoch seconds as a float. The runtime
samples the system clock at microsecond resolution; neither precision nor monotonic
elapsed-time behavior is promised. Clock corrections can move the value backwards.
The bool form with `false` throws `std::logic_error`; use the zero-argument form for
text. Its static return type remains float, including when the argument is false.

The two qualified spellings are explicit runtime callable aliases, not members of
a language class `php`. A visible source type named `php` takes precedence, even
when it lacks `microtime`; that missing method is a preparation error. Free source
functions named `microtime` follow ordinary function shadowing independently.
No other `php::` aliases or general namespace behavior is implied.

The provider is `scpp::php::microtime` in `lang/php/support/php_string.hpp`, exposed
through the core runtime metadata and the public `scpp/lang/php.hpp` umbrella.

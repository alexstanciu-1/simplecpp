# Builtin contract — `strcmp`

Doc Status: normative

`strcmp(string $left, string $right): int` is available in the PHP flavor.

Compare unsigned bytes in order, including embedded NUL. At the first differing byte return a negative or positive integer according to its order. For equal prefixes, the shorter string sorts first. Equal strings return zero. Only the sign of the result is guaranteed.

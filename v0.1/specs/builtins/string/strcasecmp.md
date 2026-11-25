# Builtin contract — `strcasecmp`

Doc Status: normative

`strcasecmp(string $left, string $right): int` is available in the PHP flavor.

Fold ASCII bytes A–Z to a–z in both operands. All other bytes retain their unsigned values, independently of locale. Compare unsigned bytes in order, including embedded NUL. At the first differing byte return a negative or positive integer according to its order. For equal prefixes, the shorter string sorts first. Equal strings return zero. Only the sign of the result is guaranteed.

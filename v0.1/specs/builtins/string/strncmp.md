# Builtin contract — `strncmp`

Doc Status: normative

`strncmp(string $left, string $right, int $limit): int` is available in the PHP flavor.

Compare the first `$limit` bytes of each operand using the ordering of [`strcmp`](strcmp.md), including embedded NUL. A limit larger than an operand uses its full length. Only the sign of the result is guaranteed.

A zero limit returns zero. A negative limit throws `ValueError`, including for equal or empty strings.

# Builtin contract — `strripos`

Doc Status: normative

The PHP flavor publishes `strripos(string $subject, string $needle[, int $offset]): result_or_false<int>`.

Use the byte positions, signed offset bounds, empty-needle behavior and miss result of [`strrpos`](strrpos.md). Compare ASCII A–Z as a–z in both operands. Every other byte, including NUL, retains its value. A match at byte zero returns the integer payload zero.

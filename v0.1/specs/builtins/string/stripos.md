# Builtin contract — `stripos`

Doc Status: normative

The PHP flavor publishes `stripos(string $subject, string $needle[, int $offset]): result_or_false<int>`.

Use the byte positions, signed offset bounds, empty-needle behavior and miss result of [`strpos`](strpos.md). Compare ASCII A–Z as a–z in both operands. Every other byte, including NUL, retains its value. A match at byte zero returns the integer payload zero.

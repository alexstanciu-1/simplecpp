# Substring match count

Doc Status: normative

| Flavor | Call |
| --- | --- |
| PHP | `substr_count(subject, needle[, offset[, length]])` |
| JS | `subject.countMatches(needle[, offset[, length]])` |
| C++ | `subject.count_matches(needle[, offset[, length]])` |

Both operands are strings. The result is an int64 count of case-sensitive, literal byte matches wholly inside the selected byte window.
Counting starts at the window start and resumes immediately after each complete match.
Embedded NUL is an ordinary byte. For example, counting `"aa"` in `"aaaaa"` returns 2.

An empty needle raises `ValueError`, including when the subject is empty.
A nonempty needle with no match returns zero. Both optional bounds are int64. With neither bound, the window is the whole subject.

For a subject of N bytes, a nonnegative offset selects that byte position and a negative
offset selects N + offset. The start must be in [0, N]. Omitted length extends to the end.
A nonnegative length specifies the number of bytes from the start. A negative length
places the exclusive end at N + length. The end must be in [start, N].
Invalid bounds raise ValueError. An empty window is valid and contains zero matches
for a nonempty needle. Windows may start or end inside a UTF-8 sequence.

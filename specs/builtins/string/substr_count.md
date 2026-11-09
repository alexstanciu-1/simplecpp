# Substring match count

Doc Status: normative

| Flavor | Call |
| --- | --- |
| PHP | `substr_count(subject, needle)` |
| JS | `subject.countMatches(needle)` |
| C++ | `subject.count_matches(needle)` |

Both operands are strings. The result is an int64 count of case-sensitive, literal byte matches in the whole subject.
Counting starts at byte zero and resumes immediately after each complete match.
Embedded NUL is an ordinary byte. For example, counting `"aa"` in `"aaaaa"` returns 2.

An empty needle raises `ValueError`, including when the subject is empty.
A nonempty needle with no match returns zero. Offset and length arguments are deferred.

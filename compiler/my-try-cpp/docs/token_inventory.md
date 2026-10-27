# Old tokenizer inventory and compact-token budget
Doc Status: supporting

Inspected 2026-10-26. This inventories the authored my-try frontend, not the
separate runtime tokenizer API. This historical inventory guided the now-implemented [compact tokenizer](tokenization.md).
Its explicit tag enum assigns values 1–125; EOF uses the token count.

## Agreed representation

Eight bytes per token: four-byte source offset, three-byte byte length, one-byte
tag. Tag zero is reserved, leaving 255 usable values. Lengths above 16,777,215
must produce an error. The source-size limit must also be checked for 32-bit
spans. Two 32-bit storage words can provide this representation without relying
on implementation-defined C++ bitfields; middleware should assert its size.

## Existing representation

[structures.php](../../my-try/02_tokenize/structures.php) stores uint32-annotated
offset and length plus a copied spelling string in each token. There is no kind
enum or kind field. The parser recognizes spelling and shape. The token list
retains source bytes and an explicit half-open token range; no EOF token is emitted.

## Scanner output

Source: [tokens.php](../../my-try/02_tokenize/tokens.php), particularly token_end,
quoted_end and numeric_end; ASCII rules are in
[text.php](../../my-try/02_tokenize/text.php).

Six useful classification categories can be assigned without adding token boundaries:

1. Identifier: ASCII letter or underscore, followed by ASCII letters/digits/underscore.
2. Variable: dollar sign followed by that identifier; the dollar sign is part of its span.
3. Decimal integer.
4. Decimal floating spelling: decimal point and/or exponent; classification currently
   happens later through Source_Text::floating().
5. Single-quoted string, including delimiters.
6. Double-quoted string, including delimiters.

Integer/float and the two quote kinds are proposed tags, not existing emitted enums.
The old scanner accepts decimal forms such as 12, 12., .5, 12.5 and 1e-3. Unary
leading signs remain separate tokens. It rejects incomplete exponents and unsupported
numeric suffixes, including hex/binary prefixes and digit separators. Escape pairs
are skipped during quote scanning; decoding and interpolation are later work.
A complete double-quoted interpolation remains one scanner token.

### Single-character punctuation: 25

```text
; ( ) { } ? : , & [ ] < > + - * / % . ! ~ | ^ \ =
```

Quotes and dollar signs do not produce separate character tokens. Hash starts a
comment except in #[. A standalone @, backtick, or non-ASCII punctuation such as
curly quote is not admitted as a punctuation token. Backslash is a separate name
separator. Whitespace and comments remain in source bytes, not token storage.

### Multi-character tokens: 30

```text
#[  ...
**=  <<=  >>=  ===  !==  <=>
==  !=  <=  >=  =>  ->  ::  ??  &&  ||
++  --  **  +=  -=  *=  /=  %=  .=  &=  |=  ^=
```

The scan uses this longest-match ordering. #[ is an attribute opener, not a
comment. Some entries are delimiters rather than expression operators.

Crucially, << and >> are NOT single scanner tokens. operator_text_at() combines
two equal angle tokens only when their byte spans are adjacent. The parser then
consumes both for a shift expression. This preserves nested generic delimiters.
Do not replace this with ordinary maximal-munch shifts without agreeing the parser
contract. <<= and >>= ARE single tokens. ?-> and ??= have no dedicated token rule;
recognition of constituent tokens does not imply those operators are supported.

Trivia: spaces, tabs, CR and LF; # comments except #[; // comments; non-nesting
/* ... */ comments. Unfinished block comments and quoted strings are errors.

## Parser-recognized words

The scanner emits all these as identifier-shaped spans. Direct spelling checks in
[parser.php](../../my-try/03_parse/parser.php) recognize these 64 words:

```text
abstract and as async await break case catch class clean const continue
data default do echo else elseif empty enum extends false final finally
fn for foreach function if implements instanceof interface isset lock
match move namespace new null object_cast or private protected public
readonly return set static struct switch take template throw trait true
try typename union unset use void weak while xor
```

This is a parser fast-classification inventory, NOT a declaration that all 64 are
reserved keywords in every position. It includes literal words, contextual syntax,
type spelling and specially parsed helpers. Matching is case-sensitive in the
existing checks. A tagged word must still be usable wherever the current grammar
accepts it as an identifier; changing tags must not accidentally reserve names.

Four other special names occur in direct checks: Override and Virtual for method
attributes, and __construct / __destruct for special methods. They can remain
identifiers. Ordinary type names and runtime function names need no dedicated tag.

Word operators include and/or/xor, instanceof, and await; they are not extra entries
in the punctuation counts above. Operator precedence and meaning remain parser and
semantic concerns rather than tokenizer responsibilities.

## Tag budget proposal

| Group | Tags |
| --- | ---: |
| Identifier, variable, integer, float, single/double-quoted string | 6 |
| Single-character punctuation | 25 |
| Multi-character tokens | 30 |
| All 64 parser-recognized words, if individually classified | 64 |
| Total usable values consumed | 125 |
| Usable values remaining out of 255 | 130 |

A separate nonzero EOF tag, if desired, would make that 126 used and 129 remaining.
Keeping the current end index requires no EOF tag. The four special names would
cost four more only if we choose to classify them. Neither choice is required now.

Recommendation: retain the eight-byte format, classify the six basic categories
and existing punctuation, preserve the adjacent-angle rule, and optionally add
word tags without imposing new keyword restrictions. There is ample space; a
union or secondary tag table is unnecessary for this inventory. Numeric tag
assignments are now recorded in token_kind.hpp; EOF uses the token count.

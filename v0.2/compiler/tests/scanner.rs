use scpp_compiler::{
    inputs::source::SourceMap,
    tokenize::scanner::scan,
    tokenize::token::{Token, TokenKind as K},
};
use std::sync::Arc;

fn scanned(bytes: &[u8]) -> scpp_compiler::tokenize::token_sequence::TokenSequence {
    scan(
        SourceMap::default()
            .add("test.phs".into(), bytes.into())
            .unwrap(),
        0,
    )
    .unwrap()
}

#[test]
fn phs_local_has_exact_byte_spans_and_source_identity() {
    let source = SourceMap::default()
        .add("local.phs".into(), b"/*hi*/$x int = -12 + .5e+2;".to_vec())
        .unwrap();
    let tokens = scan(Arc::clone(&source), usize::MAX).unwrap();
    assert!(Arc::ptr_eq(tokens.source(), &source));
    let actual: Vec<_> = tokens
        .tokens()
        .iter()
        .map(|t| (t.offset(), t.length(), t.kind()))
        .collect();
    assert_eq!(
        actual,
        vec![
            (6, 2, K::VARIABLE),
            (9, 3, K::IDENTIFIER),
            (13, 1, K::ASSIGN),
            (15, 1, K::MINUS),
            (16, 2, K::INTEGER),
            (19, 1, K::PLUS),
            (21, 5, K::FLOATING),
            (26, 1, K::SEMICOLON)
        ]
    );
}

#[test]
fn comments_quotes_and_arbitrary_literal_bytes_remain_distinct() {
    let tokens =
        scanned(b"# comment\r\n// second\n/* third */ #[A] 'a\\\'b' \"$x//\\\"\" '\0\xff' tail");
    let kinds: Vec<_> = tokens.tokens().iter().map(|t| t.kind()).collect();
    assert_eq!(
        kinds,
        [
            K::ATTRIBUTE,
            K::IDENTIFIER,
            K::RBRACKET,
            K::SINGLE_STRING,
            K::DOUBLE_STRING,
            K::SINGLE_STRING,
            K::IDENTIFIER
        ]
    );
    let token = tokens.tokens()[5];
    assert_eq!(
        tokens
            .source()
            .slice(token.span(tokens.source()).unwrap())
            .unwrap(),
        b"'\0\xff'"
    );
    assert!(scanned(b" \t\r\n# eof").is_empty());
    assert!(scanned(b"").is_empty());
}

#[test]
fn maximal_punctuation_keeps_generic_angles_separate() {
    let tokens = scanned(b"<< >> <<= >>= === !== <=> **= ... #[ -> :: ?? && || ++ -- ** <= >= == != => += -= *= /= %= .= &= |= ^= ?-> ??=");
    let kinds: Vec<_> = tokens.tokens().iter().map(|t| t.kind()).collect();
    assert_eq!(
        kinds,
        [
            K::LESS,
            K::LESS,
            K::GREATER,
            K::GREATER,
            K::LEFT_ASSIGN,
            K::RIGHT_ASSIGN,
            K::IDENTICAL,
            K::NOT_IDENTICAL,
            K::SPACESHIP,
            K::POWER_ASSIGN,
            K::ELLIPSIS,
            K::ATTRIBUTE,
            K::MEMBER,
            K::SCOPE,
            K::COALESCE,
            K::LOGICAL_AND,
            K::LOGICAL_OR,
            K::INCREMENT,
            K::DECREMENT,
            K::POWER,
            K::LESS_EQUAL,
            K::GREATER_EQUAL,
            K::EQUAL,
            K::NOT_EQUAL,
            K::ARROW,
            K::PLUS_ASSIGN,
            K::MINUS_ASSIGN,
            K::STAR_ASSIGN,
            K::SLASH_ASSIGN,
            K::PERCENT_ASSIGN,
            K::CONCAT_ASSIGN,
            K::AND_ASSIGN,
            K::OR_ASSIGN,
            K::XOR_ASSIGN,
            K::QUESTION,
            K::MEMBER,
            K::COALESCE,
            K::ASSIGN
        ]
    );
    let tokens = scanned(b";(){}?:,&[]<>+-*/%.!~|^\\=");
    assert_eq!(
        tokens
            .tokens()
            .iter()
            .map(|t| t.kind().tag())
            .collect::<Vec<_>>(),
        (7..=31).collect::<Vec<_>>()
    );
}

#[test]
fn names_are_ascii_case_sensitive_and_words_are_classifications_only() {
    let tokens = scanned(b"if iffy IF $if _x9 null true false int string vector object_cast");
    assert_eq!(
        tokens.tokens().iter().map(|t| t.kind()).collect::<Vec<_>>(),
        [
            K::KW_IF,
            K::IDENTIFIER,
            K::IDENTIFIER,
            K::VARIABLE,
            K::IDENTIFIER,
            K::KW_NULL,
            K::KW_TRUE,
            K::KW_FALSE,
            K::IDENTIFIER,
            K::IDENTIFIER,
            K::IDENTIFIER,
            K::KW_OBJECT_CAST
        ]
    );
}

#[test]
fn numbers_keep_signs_separate_and_do_not_decode_or_range_check() {
    let tokens = scanned(b"0 012 12. .5 1e-3 2E+4 -99 +42 99999999999999999999999999999999999");
    assert_eq!(
        tokens.tokens().iter().map(|t| t.kind()).collect::<Vec<_>>(),
        [
            K::INTEGER,
            K::INTEGER,
            K::FLOATING,
            K::FLOATING,
            K::FLOATING,
            K::FLOATING,
            K::MINUS,
            K::INTEGER,
            K::PLUS,
            K::INTEGER,
            K::INTEGER
        ]
    );
}

#[test]
fn malformed_input_reports_first_error_and_preserves_old_generations() {
    let old = scanned(b"return 1;");
    for (bytes, offset, reason) in [
        (&b"x; $"[..], 3, "Expected variable name"),
        (&b"$9"[..], 0, "Expected variable name"),
        (&b"x; /*"[..], 3, "Unterminated block comment"),
        (&b"'x\\"[..], 0, "Unterminated quoted string"),
        (&b"1e+"[..], 0, "Expected exponent digits"),
        (&b"0xff"[..], 0, "Unsupported numeric literal"),
        (&b"1_000"[..], 0, "Unsupported numeric literal"),
        (&b"1..2"[..], 0, "Unsupported numeric literal"),
        (&b"x;\0"[..], 2, "Unsupported token"),
        (&b"\xff"[..], 0, "Unsupported token"),
        (&b"`"[..], 0, "Unsupported token"),
    ] {
        let source = SourceMap::default()
            .add("bad.phs".into(), bytes.to_vec())
            .unwrap();
        let error = scan(Arc::clone(&source), old.len()).unwrap_err();
        assert_eq!(error.span().start(), offset);
        assert_eq!(error.message(), reason);
        assert!(Arc::ptr_eq(error.source(), &source));
    }
    assert_eq!(old.len(), 3);
    assert_eq!(old.source().bytes(), b"return 1;");
    let source = SourceMap::default()
        .add("lines.phs".into(), b"\r\n\r @".to_vec())
        .unwrap();
    assert_eq!(
        scan(source, 0).unwrap_err().to_string(),
        "lines.phs:2:3: error: Unsupported token"
    );
}

#[test]
fn all_single_bytes_and_short_prefixes_terminate_with_contained_spans() {
    for first in 0..=255u8 {
        for suffix in [&b""[..], &b"a"[..], &b"'"[..], &b"/*"[..]] {
            let bytes = [&[first], suffix].concat();
            let source = SourceMap::default().add("bytes".into(), bytes).unwrap();
            match scan(Arc::clone(&source), 0) {
                Ok(tokens) => {
                    for token in tokens.tokens() {
                        assert!(token.span(&source).is_ok());
                    }
                }
                Err(error) => assert!(source.slice(error.span()).is_ok()),
            }
        }
    }
}

#[test]
fn token_length_boundary_is_checked_without_truncation() {
    let bytes = vec![b'a'; Token::MAX_LENGTH as usize];
    assert_eq!(scanned(&bytes).tokens()[0].length(), Token::MAX_LENGTH);
    let mut bytes = bytes;
    bytes.push(b'a');
    let source = SourceMap::default().add("long".into(), bytes).unwrap();
    assert_eq!(
        scan(source, 0).unwrap_err().message(),
        "Token length exceeds 24-bit capacity"
    );
}

#[test]
fn entire_adopted_word_vocabulary_retains_compact_tags() {
    let tokens = scanned(b"abstract and as async await break case catch class clean const continue data default do echo else elseif empty enum extends false final finally fn for foreach function if implements instanceof interface isset lock match move namespace new null object_cast or private protected public readonly return set static struct switch take template throw trait true try typename union unset use void weak while xor");
    assert_eq!(
        tokens
            .tokens()
            .iter()
            .map(|t| t.kind().tag())
            .collect::<Vec<_>>(),
        (62..=125).collect::<Vec<_>>()
    );
}

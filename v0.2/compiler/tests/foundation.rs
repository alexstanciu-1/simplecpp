use scpp_compiler::diagnostic::{Diagnostic, Severity};
use scpp_compiler::inputs::source::{Location, SourceError, SourceMap};
use scpp_compiler::tokenize::token::{Token, TokenError, TokenKind};
use std::sync::Arc;

#[test]
fn source_snapshots_retain_bytes_and_identity() {
    let mut input = vec![b'A', 0, 0xff, b'Z'];
    let retained;
    {
        let mut sources = SourceMap::default();
        let first = sources.add("one.phs".into(), input.clone()).unwrap();
        input[0] = b'B';
        let second = sources
            .add("one.phs".into(), first.bytes().to_vec())
            .unwrap();
        assert_ne!(first.id(), second.id());
        assert!(Arc::ptr_eq(sources.get(first.id()).unwrap(), &first));
        assert!(!Arc::ptr_eq(&first, &second));
        assert_eq!(first.byte_at(0), Some(b'A'));
        assert_eq!(first.byte_at(1), Some(0));
        assert_eq!(first.byte_at(2), Some(255));
        assert_eq!(first.byte_at(4), None);
        assert_eq!(first.byte_at(u64::MAX), None);
        retained = Arc::clone(&first);
    }
    assert_eq!(retained.bytes(), &[b'A', 0, 255, b'Z']);
    assert_eq!(
        retained.slice(retained.span(1, 3).unwrap()).unwrap(),
        &[0, 255]
    );
}

#[test]
fn empty_sources_and_checked_half_open_spans() {
    let mut sources = SourceMap::default();
    let empty = sources.add("empty".into(), vec![]).unwrap();
    assert_eq!(
        empty.location(0).unwrap(),
        Location {
            line: 1,
            byte_column: 1
        }
    );
    assert!(empty.span(0, 0).unwrap().is_empty());
    assert_eq!(empty.byte_at(0), None);
    let source = sources.add("four".into(), b"four".to_vec()).unwrap();
    let span = source.span(1, 4).unwrap();
    assert_eq!((span.start(), span.end(), span.len()), (1, 4, 3));
    assert_eq!(empty.slice(span), Err(SourceError::InvalidSpan));
    for (start, end) in [(4, 3), (0, 5), (u64::MAX, u64::MAX), (0, u64::MAX)] {
        assert_eq!(source.span(start, end), Err(SourceError::InvalidSpan));
    }
    assert_eq!(source.location(5), Err(SourceError::InvalidSpan));
    assert!(source.span(4, 4).unwrap().is_empty());
}

#[test]
fn locations_are_byte_based_and_preserve_line_endings() {
    let mut sources = SourceMap::default();
    let source = sources
        .add(
            "bytes".into(),
            vec![b'a', b'\r', b'\n', 0xc3, 0xa9, b'\t', b'\n'],
        )
        .unwrap();
    let expected = [
        (1, 1),
        (1, 2),
        (1, 3),
        (2, 1),
        (2, 2),
        (2, 3),
        (2, 4),
        (3, 1),
    ];
    for (offset, (line, byte_column)) in expected.into_iter().enumerate() {
        assert_eq!(
            source.location(offset as u64).unwrap(),
            Location { line, byte_column }
        );
    }
}

#[test]
fn diagnostics_retain_source_and_escape_controls() {
    let diagnostic;
    {
        let mut sources = SourceMap::default();
        let source = sources
            .add("bad\nname.phs".into(), b"a\nxyz".to_vec())
            .unwrap();
        let span = source.span(3, 4).unwrap();
        diagnostic = Diagnostic::new(
            Severity::Error,
            "unexpected\nbyte\u{1b}".into(),
            source,
            span,
        )
        .unwrap();
    }
    assert_eq!(
        diagnostic.to_string(),
        "bad\\nname.phs:2:2: error: unexpected\\nbyte\\u{1b}"
    );
    assert_eq!(diagnostic.severity(), Severity::Error);
    assert_eq!(diagnostic.source().slice(diagnostic.span()).unwrap(), b"y");
    assert_eq!(diagnostic.message(), "unexpected\nbyte\u{1b}");
    let mut sources = SourceMap::default();
    let empty = sources.add("empty".into(), vec![]).unwrap();
    assert!(Diagnostic::new(Severity::Error, "bad span".into(), empty, diagnostic.span()).is_err());
}

#[test]
fn compact_tokens_preserve_all_nonzero_tags_and_boundaries() {
    assert_eq!(size_of::<Token>(), 8);
    assert_eq!(align_of::<Token>(), 4);
    assert_eq!(TokenKind::from_tag(0), None);
    for tag in 1..=255 {
        let kind = TokenKind::from_tag(tag).unwrap();
        for (offset, length) in [(0, 1), (u32::MAX as u64, Token::MAX_LENGTH as u64)] {
            let token = Token::new(offset, length, kind).unwrap();
            assert_eq!(token.offset() as u64, offset);
            assert_eq!(token.length() as u64, length);
            assert_eq!(token.kind().tag(), tag);
            let copy = token;
            assert_eq!(copy, token);
        }
    }
    assert_eq!(TokenKind::IDENTIFIER.tag(), 1);
    assert_eq!(TokenKind::KW_XOR.tag(), 125);
    for length in [0, Token::MAX_LENGTH as u64 + 1, u64::MAX] {
        assert_eq!(
            Token::new(0, length, TokenKind::INTEGER),
            Err(TokenError::InvalidLength)
        );
    }
    assert_eq!(
        Token::new(u32::MAX as u64 + 1, 1, TokenKind::INTEGER),
        Err(TokenError::OffsetTooLarge)
    );
}

#[test]
fn representation_validity_does_not_imply_source_containment() {
    let mut sources = SourceMap::default();
    let source = sources.add("one".into(), b"one".to_vec()).unwrap();
    let token = Token::new(0, 3, TokenKind::IDENTIFIER).unwrap();
    assert_eq!(source.slice(token.span(&source).unwrap()).unwrap(), b"one");
    for (offset, length) in [(2, 2), (3, 1), (u32::MAX as u64, Token::MAX_LENGTH as u64)] {
        assert_eq!(
            Token::new(offset, length, TokenKind::INTEGER)
                .unwrap()
                .span(&source),
            Err(SourceError::InvalidSpan)
        );
    }
}

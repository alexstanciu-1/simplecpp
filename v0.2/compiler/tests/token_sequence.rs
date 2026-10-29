use scpp_compiler::{
    source::SourceMap,
    token::{Token, TokenKind},
    token_sequence::{SequenceError, TokenBuilder, TokenSequence},
};
use std::sync::Arc;

fn token(offset: u64, length: u64) -> Token {
    Token::new(offset, length, TokenKind::IDENTIFIER).unwrap()
}

#[test]
fn empty_generation_is_present_and_retains_its_source() {
    let source = SourceMap::default().add("empty".into(), vec![]).unwrap();
    let sequence = TokenBuilder::new(Arc::clone(&source), usize::MAX).finish();
    assert!(sequence.is_empty());
    assert_eq!(sequence.len(), 0);
    assert_eq!(sequence.get(0), None);
    assert_eq!(sequence.get(usize::MAX), None);
    assert!(Arc::ptr_eq(sequence.source(), &source));
    let present: Option<TokenSequence> = Some(sequence);
    assert!(present.is_some());
}

#[test]
fn failed_append_preserves_order_and_allows_recovery() {
    let source = SourceMap::default()
        .add("code".into(), b"one two;".to_vec())
        .unwrap();
    let mut builder = TokenBuilder::new(source, 0);
    builder.push(token(0, 3)).unwrap();
    for invalid in [token(2, 2), token(0, 1)] {
        assert_eq!(
            builder.push(invalid),
            Err(SequenceError::OverlappingOrUnordered)
        );
    }
    for invalid in [
        token(7, 2),
        token(8, 1),
        token(u32::MAX as u64, Token::MAX_LENGTH as u64),
    ] {
        assert_eq!(builder.push(invalid), Err(SequenceError::OutsideSource));
    }
    builder.push(token(4, 3)).unwrap(); // gaps are legal
    let semicolon = Token::new(7, 1, TokenKind::SEMICOLON).unwrap();
    builder.push(semicolon).unwrap(); // adjacency and ending at EOF are legal
    let sequence = builder.finish();
    assert_eq!(sequence.tokens(), &[token(0, 3), token(4, 3), semicolon]);
    assert_eq!(sequence.get(2), Some(semicolon));
    assert_eq!(sequence.get(3), None);
}

#[test]
fn generation_and_source_survive_original_owners() {
    let retained;
    {
        let mut sources = SourceMap::default();
        let source = sources.add("bytes".into(), vec![b'A', 0, 255]).unwrap();
        let mut builder = TokenBuilder::new(Arc::clone(&source), 1);
        builder.push(token(0, 3)).unwrap();
        let sequence = builder.finish();
        retained = sequence.clone();
        assert_eq!(retained.tokens().as_ptr(), sequence.tokens().as_ptr());
        assert!(Arc::ptr_eq(retained.source(), &source));
    }
    let source = Arc::clone(retained.source());
    let span = retained.get(0).unwrap().span(&source).unwrap();
    drop(retained);
    assert_eq!(source.slice(span).unwrap(), &[b'A', 0, 255]);
}

#[test]
fn growth_retains_every_token_in_order() {
    let source = SourceMap::default()
        .add("many".into(), vec![0; 600])
        .unwrap();
    let mut builder = TokenBuilder::new(source, 0);
    for offset in 0..600 {
        builder.push(token(offset, 1)).unwrap();
    }
    let sequence = builder.finish();
    assert_eq!(sequence.len(), 600);
    for (offset, actual) in sequence.tokens().iter().enumerate() {
        assert_eq!(*actual, token(offset as u64, 1));
    }
}

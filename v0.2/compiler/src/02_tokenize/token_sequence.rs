//! Source-bound, ordered token generations. Scanning and token classification are separate.
use crate::inputs::source::SourceFile;
use crate::tokenize::token::Token;
use std::{fmt, sync::Arc};

#[derive(Clone, Copy, Debug, Eq, PartialEq)]
pub enum SequenceError {
    OutsideSource,
    OverlappingOrUnordered,
}

impl fmt::Display for SequenceError {
    fn fmt(&self, f: &mut fmt::Formatter<'_>) -> fmt::Result {
        f.write_str(match self {
            Self::OutsideSource => "token span is outside the retained source",
            Self::OverlappingOrUnordered => "token spans must be ordered and nonoverlapping",
        })
    }
}
impl std::error::Error for SequenceError {}

#[derive(Debug)]
struct Generation {
    source: Arc<SourceFile>,
    tokens: Vec<Token>,
}

/// Immutable generation; cloning retains the same source and token allocation.
/// Absence is represented by Option<TokenSequence>, not an empty handle.
#[derive(Clone, Debug)]
pub struct TokenSequence(Arc<Generation>);

impl TokenSequence {
    pub fn source(&self) -> &Arc<SourceFile> {
        &self.0.source
    }

    pub fn tokens(&self) -> &[Token] {
        &self.0.tokens
    }

    pub fn get(&self, index: usize) -> Option<Token> {
        self.0.tokens.get(index).copied()
    }

    pub fn len(&self) -> usize {
        self.0.tokens.len()
    }

    pub fn is_empty(&self) -> bool {
        self.0.tokens.is_empty()
    }
}

/// Mutable construction with single-use publication enforced by Rust ownership.
///
/// ```compile_fail
/// use scpp_compiler::{inputs::source::SourceMap, tokenize::token_sequence::TokenBuilder};
/// let source = SourceMap::default().add("empty".into(), vec![]).unwrap();
/// let builder = TokenBuilder::new(source, 0);
/// let first = builder.finish();
/// let second = builder.finish(); // publication consumes the builder
/// ```
#[derive(Debug)]
pub struct TokenBuilder(Generation);

impl TokenBuilder {
    /// The reserve hint is bounded by source bytes (every token is nonempty).
    /// Zero uses a default of at most 256 slots. Allocation failure follows Rust's
    /// standard allocation behavior; it is not represented by SequenceError.
    pub fn new(source: Arc<SourceFile>, previous_count: usize) -> Self {
        let hint = if previous_count == 0 {
            256
        } else {
            previous_count
        };
        let capacity = hint.min(source.byte_len() as usize);
        Self(Generation {
            source,
            tokens: Vec::with_capacity(capacity),
        })
    }

    /// Token owns representation validation; this layer binds its coordinates to
    /// this source and checks ordering. Failure leaves the builder unchanged.
    pub fn push(&mut self, token: Token) -> Result<(), SequenceError> {
        let span = token
            .span(&self.0.source)
            .map_err(|_| SequenceError::OutsideSource)?;
        if let Some(last) = self.0.tokens.last() {
            // Previously admitted tokens end inside a source of at most u32::MAX bytes.
            if span.start() < last.offset() + last.length() {
                return Err(SequenceError::OverlappingOrUnordered);
            }
        }
        self.0.tokens.push(token);
        Ok(())
    }

    /// Moves the existing Vec into one shared generation allocation; no token or
    /// source-byte copy and no shrink-to-fit/reallocation at publication.
    pub fn finish(self) -> TokenSequence {
        TokenSequence(Arc::new(self.0))
    }
}

#[cfg(test)]
mod tests {
    use super::*;
    use crate::{inputs::source::SourceMap, tokenize::token::TokenKind};

    #[test]
    fn bounded_reserve_and_publication_preserve_token_allocation() {
        let mut sources = SourceMap::default();
        let source = sources.add("small".into(), vec![0; 8]).unwrap();
        let mut builder = TokenBuilder::new(source, usize::MAX);
        assert_eq!(builder.0.tokens.capacity(), 8);
        builder
            .push(Token::new(0, 1, TokenKind::INTEGER).unwrap())
            .unwrap();
        let pointer = builder.0.tokens.as_ptr();
        let capacity = builder.0.tokens.capacity();
        let sequence = builder.finish();
        assert_eq!(sequence.tokens().as_ptr(), pointer);
        assert_eq!(sequence.0.tokens.capacity(), capacity);
        assert_eq!(sequence.clone().tokens().as_ptr(), pointer);
    }
}

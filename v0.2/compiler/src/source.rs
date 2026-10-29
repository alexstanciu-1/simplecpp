//! Immutable byte sources. IDs belong to one append-only SourceMap/compilation.
use std::fmt;
use std::path::{Path, PathBuf};
use std::sync::Arc;

#[derive(Clone, Copy, Debug, Eq, PartialEq, Hash)]
pub struct SourceId(u32);

/// Half-open byte range, including zero-width diagnostic locations at EOF.
#[derive(Clone, Copy, Debug, Eq, PartialEq)]
pub struct Span {
    start: u32,
    end: u32,
}

impl Span {
    pub fn start(self) -> u32 {
        self.start
    }

    pub fn end(self) -> u32 {
        self.end
    }

    pub fn len(self) -> u32 {
        self.end - self.start
    }

    pub fn is_empty(self) -> bool {
        self.start == self.end
    }
}

#[derive(Clone, Copy, Debug, Eq, PartialEq)]
pub enum SourceError {
    TooLarge,
    TooManySources,
    InvalidSpan,
}

impl fmt::Display for SourceError {
    fn fmt(&self, f: &mut fmt::Formatter<'_>) -> fmt::Result {
        f.write_str(match self {
            Self::TooLarge => "source exceeds the 32-bit byte-offset limit",
            Self::TooManySources => "source map exceeds the source-ID limit",
            Self::InvalidSpan => "byte span is reversed or outside the source",
        })
    }
}

impl std::error::Error for SourceError {}

/// One-based byte coordinates, not Unicode character or terminal display columns.
#[derive(Clone, Copy, Debug, Eq, PartialEq)]
pub struct Location {
    pub line: u64,
    pub byte_column: u64,
}

#[derive(Debug)]
pub struct SourceFile {
    id: SourceId,
    name: PathBuf,
    bytes: Box<[u8]>,
    line_starts: Vec<u32>,
}

impl SourceFile {
    pub fn id(&self) -> SourceId {
        self.id
    }

    pub fn name(&self) -> &Path {
        &self.name
    }

    pub fn bytes(&self) -> &[u8] {
        &self.bytes
    }

    pub fn byte_len(&self) -> u32 {
        self.bytes.len() as u32 // Checked before insertion into SourceMap.
    }

    pub fn byte_at(&self, offset: u64) -> Option<u8> {
        let index = usize::try_from(offset).ok()?;
        self.bytes.get(index).copied()
    }

    pub fn span(&self, start: u64, end: u64) -> Result<Span, SourceError> {
        if start > end || end > u64::from(self.byte_len()) {
            return Err(SourceError::InvalidSpan);
        }
        Ok(Span {
            start: start as u32,
            end: end as u32,
        })
    }

    /// Spans carry coordinates only; recheck containment when applying one here.
    pub fn slice(&self, span: Span) -> Result<&[u8], SourceError> {
        self.bytes
            .get(span.start as usize..span.end as usize)
            .ok_or(SourceError::InvalidSpan)
    }

    pub fn location(&self, offset: u64) -> Result<Location, SourceError> {
        self.span(offset, offset)?;
        let line = self
            .line_starts
            .partition_point(|&start| u64::from(start) <= offset)
            - 1;
        Ok(Location {
            line: line as u64 + 1,
            byte_column: offset - u64::from(self.line_starts[line]) + 1,
        })
    }
}

#[derive(Debug, Default)]
pub struct SourceMap {
    files: Vec<Arc<SourceFile>>,
}

impl SourceMap {
    /// Takes ownership; callers with borrowed bytes choose an explicit copy.
    /// Equal bytes/paths still create distinct snapshots. IDs are never recycled.
    pub fn add(&mut self, name: PathBuf, bytes: Vec<u8>) -> Result<Arc<SourceFile>, SourceError> {
        checked_source_len(bytes.len() as u64)?;
        let id =
            SourceId(u32::try_from(self.files.len()).map_err(|_| SourceError::TooManySources)?);
        let mut line_starts = vec![0];
        for (index, &byte) in bytes.iter().enumerate() {
            if byte == b'\n' {
                line_starts.push((index + 1) as u32);
            }
        }
        let source = Arc::new(SourceFile {
            id,
            name,
            bytes: bytes.into_boxed_slice(),
            line_starts,
        });
        self.files.push(Arc::clone(&source));
        Ok(source)
    }

    pub fn get(&self, id: SourceId) -> Option<&Arc<SourceFile>> {
        self.files.get(id.0 as usize)
    }
}

fn checked_source_len(length: u64) -> Result<u32, SourceError> {
    u32::try_from(length).map_err(|_| SourceError::TooLarge)
}

#[cfg(test)]
mod tests {
    use super::*;

    #[test]
    fn source_limit_without_a_four_gibibyte_allocation() {
        assert_eq!(checked_source_len(u32::MAX as u64), Ok(u32::MAX));
        assert_eq!(
            checked_source_len(u32::MAX as u64 + 1),
            Err(SourceError::TooLarge)
        );
        assert_eq!(checked_source_len(u64::MAX), Err(SourceError::TooLarge));
    }
}

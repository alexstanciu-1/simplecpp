//! Diagnostics retain their source snapshot and validate their location once.
use crate::source::{Location, SourceError, SourceFile, Span};
use std::fmt;
use std::sync::Arc;

#[derive(Clone, Copy, Debug, Eq, PartialEq)]
pub enum Severity {
    Error,
    Note,
}

#[derive(Clone, Debug)]
pub struct Diagnostic {
    severity: Severity,
    message: String,
    source: Arc<SourceFile>,
    span: Span,
    location: Location,
}

impl Diagnostic {
    pub fn new(
        severity: Severity,
        message: String,
        source: Arc<SourceFile>,
        span: Span,
    ) -> Result<Self, SourceError> {
        source.slice(span)?;
        let location = source.location(u64::from(span.start()))?;
        Ok(Self {
            severity,
            message,
            source,
            span,
            location,
        })
    }

    pub fn source(&self) -> &Arc<SourceFile> {
        &self.source
    }

    pub fn span(&self) -> Span {
        self.span
    }

    pub fn severity(&self) -> Severity {
        self.severity
    }

    pub fn message(&self) -> &str {
        &self.message
    }
}

impl fmt::Display for Diagnostic {
    fn fmt(&self, f: &mut fmt::Formatter<'_>) -> fmt::Result {
        let severity = match self.severity {
            Severity::Error => "error",
            Severity::Note => "note",
        };
        // Escape controls in names/messages; arbitrary source bytes are never interpreted as text.
        write!(
            f,
            "{}:{}:{}: {severity}: {}",
            self.source.name().to_string_lossy().escape_debug(),
            self.location.line,
            self.location.byte_column,
            self.message.escape_debug()
        )
    }
}

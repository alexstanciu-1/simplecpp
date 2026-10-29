//! Sequences input loading and phase workers; presentation belongs to the CLI.
use crate::{
    diagnostic::{Diagnostic, Severity},
    inputs::{load::load, source::SourceMap},
    language::Flavor,
    parse::{self, ast::ParsedFile},
    tokenize::{scanner, token_sequence::TokenSequence},
};
use std::path::PathBuf;

/// Invocation-owned sources; no global compiler state or retained workers.
#[derive(Default)]
pub struct Driver {
    sources: SourceMap,
}

impl Driver {
    pub fn scan_file(&mut self, path: PathBuf, flavor: Flavor) -> Result<TokenSequence, String> {
        let source = load(&mut self.sources, path).map_err(|e| e.to_string())?;
        scanner::scan(source, 0, flavor).map_err(|e| e.to_string())
    }

    pub fn parse_file(&mut self, path: PathBuf, flavor: Flavor) -> Result<ParsedFile, String> {
        let tokens = self.scan_file(path, flavor)?;
        parse::parse(tokens).map_err(|e| e.to_string())
    }

    pub fn inspect_source(&mut self, path: PathBuf, offset: u64) -> Result<Diagnostic, String> {
        let source = load(&mut self.sources, path).map_err(|e| e.to_string())?;
        let span = source.span(offset, offset).map_err(|e| {
            format!(
                "{}: {e} (offset {offset})",
                source.name().to_string_lossy().escape_debug()
            )
        })?;
        let message = format!(
            "source contains {} bytes; offset {offset}",
            source.byte_len()
        );
        Diagnostic::new(Severity::Note, message, source, span).map_err(|e| e.to_string())
    }
}

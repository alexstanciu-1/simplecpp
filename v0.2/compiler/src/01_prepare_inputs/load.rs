//! Filesystem acquisition into a caller-owned source map.
use super::source::{SourceError, SourceFile, SourceMap};
use std::{fmt, io, path::PathBuf, sync::Arc};

#[derive(Debug)]
pub enum LoadError {
    Read { path: PathBuf, cause: io::Error },
    Source(SourceError),
}
impl fmt::Display for LoadError {
    fn fmt(&self, f: &mut fmt::Formatter<'_>) -> fmt::Result {
        match self {
            Self::Read { path, cause } => write!(
                f,
                "cannot read {}: {cause}",
                path.to_string_lossy().escape_debug()
            ),
            Self::Source(error) => error.fmt(f),
        }
    }
}
impl std::error::Error for LoadError {
    fn source(&self) -> Option<&(dyn std::error::Error + 'static)> {
        Some(match self {
            Self::Read { cause, .. } => cause,
            Self::Source(error) => error,
        })
    }
}

pub fn load(sources: &mut SourceMap, path: PathBuf) -> Result<Arc<SourceFile>, LoadError> {
    let bytes = std::fs::read(&path).map_err(|cause| LoadError::Read {
        path: path.clone(),
        cause,
    })?;
    sources.add(path, bytes).map_err(LoadError::Source)
}

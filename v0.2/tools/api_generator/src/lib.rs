//! Runtime API preparation, independent of compiler syntax and runtime linkage.
pub mod emission;
pub mod integer;
pub mod json;
pub mod ownership;
pub mod runtime;
pub mod scalar;
pub mod target;

use std::fmt;
#[derive(Clone, Debug, Eq, PartialEq)]
pub struct Error(pub String);
impl fmt::Display for Error {
    fn fmt(&self, f: &mut fmt::Formatter<'_>) -> fmt::Result {
        f.write_str(&self.0)
    }
}
impl std::error::Error for Error {}
pub type Result<T> = std::result::Result<T, Error>;
pub(crate) fn ensure(ok: bool, message: &str) -> Result<()> {
    if ok {
        Ok(())
    } else {
        Err(Error(message.into()))
    }
}

// Shared publication path contract for every prepared family.
pub(crate) fn public_header(value: &json::Value) -> Result<String> {
    let path = value.text()?;
    ensure(
        path.starts_with("scpp/")
            && path.ends_with(".hpp")
            && !path.contains("..")
            && !path.contains("//")
            && !path.contains("/.")
            && path
                .bytes()
                .all(|b| b.is_ascii_lowercase() || b.is_ascii_digit() || b"_/.".contains(&b)),
        "Invalid public header path",
    )?;
    Ok(path.into())
}

//! Flavor frontends normalize syntax; shared parsing machinery constructs one AST.
pub mod ast;
mod cpp;
mod js;
mod parser;
mod php;
pub use parser::parse;

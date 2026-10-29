//! Independent compiler phases, sequenced by the compiler driver.
pub mod compiler;
pub mod diagnostic;
#[path = "01_prepare_inputs/mod.rs"]
pub mod inputs;
#[path = "02_tokenize/mod.rs"]
pub mod tokenize;

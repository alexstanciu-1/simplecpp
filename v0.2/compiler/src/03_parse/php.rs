//! PHP-flavor assignment syntax preserves declaration-versus-write intent for analysis.
use super::{
    ast::AssignmentMode,
    parser::{Head, Parser},
};
use crate::{diagnostic::Diagnostic, tokenize::token::TokenKind as K};
pub(super) fn head(p: &mut Parser) -> Result<Head, Diagnostic> {
    if p.kind(0) == Some(K::VARIABLE) && p.kind(1) == Some(K::ASSIGN) {
        return Ok(Head::Assignment(
            p.name(true)?,
            AssignmentMode::DeclareOrAssign,
        ));
    }
    if p.kind(0) == Some(K::VARIABLE) && p.kind(1) == Some(K::IDENTIFIER) {
        let name = p.name(true)?;
        return Ok(Head::Local(name, Some(p.ty(0)?)));
    }
    Ok(Head::Expression)
}

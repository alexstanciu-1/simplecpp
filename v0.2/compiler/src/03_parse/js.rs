//! JS-flavor local declarations. No prototype or dynamic-object semantics.
use super::{
    ast::AssignmentMode,
    parser::{Head, Parser},
};
use crate::{diagnostic::Diagnostic, tokenize::token::TokenKind as K};
pub(super) fn head(p: &mut Parser) -> Result<Head, Diagnostic> {
    if p.kind(0) == Some(K::KW_LET) {
        p.advance();
        let name = p.name(false)?;
        let ty = if p.take(K::COLON) {
            Some(p.ty(0)?)
        } else {
            None
        };
        return Ok(Head::Local(name, ty));
    }
    if p.kind(0) == Some(K::IDENTIFIER) && p.kind(1) == Some(K::ASSIGN) {
        return Ok(Head::Assignment(
            p.name(false)?,
            AssignmentMode::ExistingBinding,
        ));
    }
    Ok(Head::Expression)
}

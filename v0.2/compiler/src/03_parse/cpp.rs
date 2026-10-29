//! C++-flavor declaration syntax. No C++ semantic compatibility is implied.
use super::{
    ast::AssignmentMode,
    parser::{Head, Parser},
};
use crate::{diagnostic::Diagnostic, tokenize::token::TokenKind as K};
pub(super) fn head(p: &mut Parser) -> Result<Head, Diagnostic> {
    if p.kind(0) == Some(K::KW_AUTO) {
        p.advance();
        return Ok(Head::Local(p.name(false)?, None));
    }
    if p.kind(0) == Some(K::IDENTIFIER) && p.kind(1) == Some(K::ASSIGN) {
        return Ok(Head::Assignment(
            p.name(false)?,
            AssignmentMode::ExistingBinding,
        ));
    }
    if p.kind(0) == Some(K::IDENTIFIER)
        && (p.kind(1) == Some(K::IDENTIFIER)
            || (p.kind(1) == Some(K::LESS) && p.kind(2) != Some(K::LESS)))
    {
        let ty = p.ty(0)?;
        return Ok(Head::Local(p.name(false)?, Some(ty)));
    }
    Ok(Head::Expression)
}

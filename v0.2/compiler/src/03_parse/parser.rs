use super::ast::*;
use crate::{
    diagnostic::{Diagnostic, Severity},
    inputs::source::Span,
    language::Flavor,
    tokenize::{
        token::{Token, TokenKind as K},
        token_sequence::TokenSequence,
    },
};
use std::sync::Arc;
type Result<T> = std::result::Result<T, Diagnostic>;

pub fn parse(tokens: TokenSequence) -> Result<ParsedFile> {
    let head = match tokens.flavor() {
        Flavor::Cpp => super::cpp::head,
        Flavor::Php => super::php::head,
        Flavor::Js => super::js::head,
    };
    let mut parser = Parser {
        tokens,
        position: 0,
        expressions: vec![],
        head,
    };
    let statements = parser.statements(false, 0)?;
    Ok(ParsedFile {
        tokens: parser.tokens,
        statements,
        expressions: parser.expressions,
    })
}

pub(super) enum Head {
    Local(Name, Option<TypeSyntax>),
    Assignment(Name, AssignmentMode),
    Expression,
}
pub(super) struct Parser {
    tokens: TokenSequence,
    position: usize,
    expressions: Vec<Expression>,
    head: fn(&mut Parser) -> Result<Head>,
}
impl Parser {
    pub(super) fn kind(&self, ahead: usize) -> Option<K> {
        self.tokens.get(self.position + ahead).map(Token::kind)
    }
    pub(super) fn spelling(&self) -> &[u8] {
        self.tokens.get(self.position).map_or(b"", |token| {
            self.tokens
                .source()
                .slice(token.span(self.tokens.source()).unwrap())
                .unwrap()
        })
    }
    fn start(&self) -> u32 {
        self.tokens
            .get(self.position)
            .map_or(self.tokens.source().byte_len(), Token::offset)
    }
    fn span(&self, start: u32) -> Span {
        let end = if self.position == 0 {
            start
        } else {
            let t = self.tokens.get(self.position - 1).unwrap();
            t.offset() + t.length()
        };
        self.tokens
            .source()
            .span(start.into(), end.max(start).into())
            .unwrap()
    }
    pub(super) fn error(&self, message: &str) -> Diagnostic {
        let span = self.tokens.get(self.position).map_or_else(
            || {
                self.tokens
                    .source()
                    .span(self.start().into(), self.start().into())
                    .unwrap()
            },
            |token| token.span(self.tokens.source()).unwrap(),
        );
        Diagnostic::new(
            Severity::Error,
            message.into(),
            Arc::clone(self.tokens.source()),
            span,
        )
        .unwrap()
    }
    fn depth(&self, depth: usize) -> Result<()> {
        if depth >= 128 {
            Err(self.error("Syntax nesting exceeds 128 levels"))
        } else {
            Ok(())
        }
    }
    pub(super) fn take(&mut self, kind: K) -> bool {
        if self.kind(0) == Some(kind) {
            self.position += 1;
            true
        } else {
            false
        }
    }
    pub(super) fn expect(&mut self, kind: K, message: &str) -> Result<()> {
        if self.take(kind) {
            Ok(())
        } else {
            Err(self.error(message))
        }
    }
    pub(super) fn advance(&mut self) {
        self.position += 1;
    }
    pub(super) fn name(&mut self, variable: bool) -> Result<Name> {
        let kind = if variable { K::VARIABLE } else { K::IDENTIFIER };
        if self.kind(0) != Some(kind) {
            return Err(self.error("Expected name"));
        }
        let start = self.start();
        let text = self.spelling();
        let text = if variable { &text[1..] } else { text };
        let text = String::from_utf8(text.to_vec()).expect("scanner names are ASCII");
        self.advance();
        Ok(Name {
            text,
            span: self.span(start),
        })
    }
    pub(super) fn ty(&mut self, depth: usize) -> Result<TypeSyntax> {
        self.depth(depth)?;
        let start = self.start();
        let name = self.name(false)?;
        let mut arguments = vec![];
        if self.take(K::LESS) {
            loop {
                arguments.push(self.ty(depth + 1)?);
                if !self.take(K::COMMA) {
                    break;
                }
            }
            self.expect(K::GREATER, "Expected '>' after type arguments")?;
        }
        Ok(TypeSyntax {
            name,
            arguments,
            span: self.span(start),
        })
    }
    fn statements(&mut self, block: bool, depth: usize) -> Result<Vec<Statement>> {
        self.depth(depth)?;
        let mut statements = vec![];
        while self.kind(0).is_some() && !(block && self.kind(0) == Some(K::RBRACE)) {
            let start = self.start();
            let kind = if self.take(K::LBRACE) {
                let body = self.statements(true, depth + 1)?;
                self.expect(K::RBRACE, "Expected '}' after block")?;
                StatementKind::Block(body)
            } else {
                let head = (self.head)(self)?;
                let kind = match head {
                    Head::Local(name, annotation) => {
                        self.expect(K::ASSIGN, "Expected '=' before initializer")?;
                        StatementKind::Local {
                            name,
                            annotation,
                            initializer: self.expression(0, depth + 1)?,
                        }
                    }
                    Head::Assignment(name, mode) => {
                        self.expect(K::ASSIGN, "Expected '=' in assignment")?;
                        StatementKind::Assignment {
                            name,
                            value: self.expression(0, depth + 1)?,
                            mode,
                        }
                    }
                    Head::Expression => StatementKind::Expression(self.expression(0, depth + 1)?),
                };
                self.expect(K::SEMICOLON, "Expected ';' after statement")?;
                kind
            };
            statements.push(Statement {
                kind,
                span: self.span(start),
            });
        }
        Ok(statements)
    }
    fn append(&mut self, kind: ExpressionKind, span: Span) -> ExprId {
        let id = ExprId(self.expressions.len());
        self.expressions.push(Expression { kind, span });
        id
    }
    fn expression(&mut self, minimum: u8, depth: usize) -> Result<ExprId> {
        self.depth(depth)?;
        let start = self.start();
        let mut left = if self.take(K::PLUS) || self.take(K::MINUS) {
            let operator = if self.tokens.get(self.position - 1).unwrap().kind() == K::PLUS {
                UnaryOp::Plus
            } else {
                UnaryOp::Minus
            };
            let operand = self.expression(4, depth + 1)?;
            self.append(
                ExpressionKind::Unary { operator, operand },
                self.span(start),
            )
        } else if self.take(K::LPAREN) {
            let value = self.expression(0, depth + 1)?;
            self.expect(K::RPAREN, "Expected ')' after expression")?;
            self.append(ExpressionKind::Group(value), self.span(start))
        } else if self.take(K::INTEGER) {
            self.append(ExpressionKind::Integer, self.span(start))
        } else {
            let name = self.name(self.tokens.flavor() == Flavor::Php)?;
            self.append(ExpressionKind::Name(name), self.span(start))
        };
        while let Some((operator, precedence, width)) = self.binary() {
            if precedence < minimum {
                break;
            }
            self.position += width;
            let right = self.expression(precedence + 1, depth + 1)?;
            left = self.append(
                ExpressionKind::Binary {
                    operator,
                    left,
                    right,
                },
                self.span(start),
            );
        }
        Ok(left)
    }
    fn binary(&self) -> Option<(BinaryOp, u8, usize)> {
        let kind = self.kind(0)?;
        if kind == K::LESS || kind == K::GREATER {
            let a = self.tokens.get(self.position)?;
            let b = self.tokens.get(self.position + 1)?;
            return (a.kind() == b.kind() && a.offset() + a.length() == b.offset()).then_some((
                if kind == K::LESS {
                    BinaryOp::ShiftLeft
                } else {
                    BinaryOp::ShiftRight
                },
                1,
                2,
            ));
        }
        Some(if kind == K::PLUS {
            (BinaryOp::Add, 2, 1)
        } else if kind == K::MINUS {
            (BinaryOp::Subtract, 2, 1)
        } else if kind == K::STAR {
            (BinaryOp::Multiply, 3, 1)
        } else if kind == K::SLASH {
            (BinaryOp::Divide, 3, 1)
        } else if kind == K::PERCENT {
            (BinaryOp::Remainder, 3, 1)
        } else {
            return None;
        })
    }
}

//! Flavor-neutral source intent. No resolved types, runtime bindings or strictness decisions.
use crate::{inputs::source::Span, tokenize::token_sequence::TokenSequence};

#[derive(Debug)]
pub struct ParsedFile {
    pub tokens: TokenSequence,
    pub statements: Vec<Statement>,
    pub expressions: Vec<Expression>,
}
#[derive(Clone, Debug, Eq, PartialEq)]
pub struct Name {
    pub text: String,
    pub span: Span,
}
#[derive(Debug)]
pub struct TypeSyntax {
    pub name: Name,
    pub arguments: Vec<TypeSyntax>,
    pub span: Span,
}
#[derive(Debug)]
pub struct Statement {
    pub kind: StatementKind,
    pub span: Span,
}
#[derive(Debug)]
pub enum StatementKind {
    Local {
        name: Name,
        annotation: Option<TypeSyntax>,
        initializer: ExprId,
    },
    Assignment {
        name: Name,
        value: ExprId,
        mode: AssignmentMode,
    },
    Expression(ExprId),
    Block(Vec<Statement>),
}
#[derive(Clone, Copy, Debug, Eq, PartialEq)]
pub enum AssignmentMode {
    ExistingBinding,
    DeclareOrAssign,
}
/// File-local expression identity. An arena avoids recursive destruction of long binary chains.
#[derive(Clone, Copy, Debug, Eq, PartialEq)]
pub struct ExprId(pub usize);
#[derive(Debug)]
pub struct Expression {
    pub kind: ExpressionKind,
    pub span: Span,
}
#[derive(Debug)]
pub enum ExpressionKind {
    Integer, // Exact spelling is borrowed from span; default type belongs to analysis.
    Name(Name),
    Unary {
        operator: UnaryOp,
        operand: ExprId,
    },
    Binary {
        operator: BinaryOp,
        left: ExprId,
        right: ExprId,
    },
    Group(ExprId),
}
#[derive(Clone, Copy, Debug, Eq, PartialEq)]
pub enum UnaryOp {
    Plus,
    Minus,
}
#[derive(Clone, Copy, Debug, Eq, PartialEq)]
pub enum BinaryOp {
    Add,
    Subtract,
    Multiply,
    Divide,
    Remainder,
    ShiftLeft,
    ShiftRight,
}

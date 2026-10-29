//! Compact token representation; lexical classification belongs to the future scanner.
use crate::source::{SourceError, SourceFile, Span};
use std::fmt;
use std::num::NonZeroU8;

/// Nonzero spelling tag. The vocabulary is adopted from the C++ foundation;
/// unknown nonzero tags remain representable, without admitting source syntax.
#[derive(Clone, Copy, Debug, Eq, PartialEq)]
pub struct TokenKind(NonZeroU8);

impl TokenKind {
    pub const fn from_tag(tag: u8) -> Option<Self> {
        match NonZeroU8::new(tag) {
            Some(value) => Some(Self(value)),
            None => None,
        }
    }

    pub const fn tag(self) -> u8 {
        self.0.get()
    }

    pub const IDENTIFIER: Self = Self(NonZeroU8::new(1).unwrap());
    pub const VARIABLE: Self = Self(NonZeroU8::new(2).unwrap());
    pub const INTEGER: Self = Self(NonZeroU8::new(3).unwrap());
    pub const FLOATING: Self = Self(NonZeroU8::new(4).unwrap());
    pub const SINGLE_STRING: Self = Self(NonZeroU8::new(5).unwrap());
    pub const DOUBLE_STRING: Self = Self(NonZeroU8::new(6).unwrap());
    pub const SEMICOLON: Self = Self(NonZeroU8::new(7).unwrap());
    pub const LPAREN: Self = Self(NonZeroU8::new(8).unwrap());
    pub const RPAREN: Self = Self(NonZeroU8::new(9).unwrap());
    pub const LBRACE: Self = Self(NonZeroU8::new(10).unwrap());
    pub const RBRACE: Self = Self(NonZeroU8::new(11).unwrap());
    pub const QUESTION: Self = Self(NonZeroU8::new(12).unwrap());
    pub const COLON: Self = Self(NonZeroU8::new(13).unwrap());
    pub const COMMA: Self = Self(NonZeroU8::new(14).unwrap());
    pub const AMPERSAND: Self = Self(NonZeroU8::new(15).unwrap());
    pub const LBRACKET: Self = Self(NonZeroU8::new(16).unwrap());
    pub const RBRACKET: Self = Self(NonZeroU8::new(17).unwrap());
    pub const LESS: Self = Self(NonZeroU8::new(18).unwrap());
    pub const GREATER: Self = Self(NonZeroU8::new(19).unwrap());
    pub const PLUS: Self = Self(NonZeroU8::new(20).unwrap());
    pub const MINUS: Self = Self(NonZeroU8::new(21).unwrap());
    pub const STAR: Self = Self(NonZeroU8::new(22).unwrap());
    pub const SLASH: Self = Self(NonZeroU8::new(23).unwrap());
    pub const PERCENT: Self = Self(NonZeroU8::new(24).unwrap());
    pub const DOT: Self = Self(NonZeroU8::new(25).unwrap());
    pub const BANG: Self = Self(NonZeroU8::new(26).unwrap());
    pub const TILDE: Self = Self(NonZeroU8::new(27).unwrap());
    pub const PIPE: Self = Self(NonZeroU8::new(28).unwrap());
    pub const CARET: Self = Self(NonZeroU8::new(29).unwrap());
    pub const BACKSLASH: Self = Self(NonZeroU8::new(30).unwrap());
    pub const ASSIGN: Self = Self(NonZeroU8::new(31).unwrap());
    pub const ATTRIBUTE: Self = Self(NonZeroU8::new(32).unwrap());
    pub const ELLIPSIS: Self = Self(NonZeroU8::new(33).unwrap());
    pub const POWER_ASSIGN: Self = Self(NonZeroU8::new(34).unwrap());
    pub const LEFT_ASSIGN: Self = Self(NonZeroU8::new(35).unwrap());
    pub const RIGHT_ASSIGN: Self = Self(NonZeroU8::new(36).unwrap());
    pub const IDENTICAL: Self = Self(NonZeroU8::new(37).unwrap());
    pub const NOT_IDENTICAL: Self = Self(NonZeroU8::new(38).unwrap());
    pub const SPACESHIP: Self = Self(NonZeroU8::new(39).unwrap());
    pub const EQUAL: Self = Self(NonZeroU8::new(40).unwrap());
    pub const NOT_EQUAL: Self = Self(NonZeroU8::new(41).unwrap());
    pub const LESS_EQUAL: Self = Self(NonZeroU8::new(42).unwrap());
    pub const GREATER_EQUAL: Self = Self(NonZeroU8::new(43).unwrap());
    pub const ARROW: Self = Self(NonZeroU8::new(44).unwrap());
    pub const MEMBER: Self = Self(NonZeroU8::new(45).unwrap());
    pub const SCOPE: Self = Self(NonZeroU8::new(46).unwrap());
    pub const COALESCE: Self = Self(NonZeroU8::new(47).unwrap());
    pub const LOGICAL_AND: Self = Self(NonZeroU8::new(48).unwrap());
    pub const LOGICAL_OR: Self = Self(NonZeroU8::new(49).unwrap());
    pub const INCREMENT: Self = Self(NonZeroU8::new(50).unwrap());
    pub const DECREMENT: Self = Self(NonZeroU8::new(51).unwrap());
    pub const POWER: Self = Self(NonZeroU8::new(52).unwrap());
    pub const PLUS_ASSIGN: Self = Self(NonZeroU8::new(53).unwrap());
    pub const MINUS_ASSIGN: Self = Self(NonZeroU8::new(54).unwrap());
    pub const STAR_ASSIGN: Self = Self(NonZeroU8::new(55).unwrap());
    pub const SLASH_ASSIGN: Self = Self(NonZeroU8::new(56).unwrap());
    pub const PERCENT_ASSIGN: Self = Self(NonZeroU8::new(57).unwrap());
    pub const CONCAT_ASSIGN: Self = Self(NonZeroU8::new(58).unwrap());
    pub const AND_ASSIGN: Self = Self(NonZeroU8::new(59).unwrap());
    pub const OR_ASSIGN: Self = Self(NonZeroU8::new(60).unwrap());
    pub const XOR_ASSIGN: Self = Self(NonZeroU8::new(61).unwrap());
    pub const KW_ABSTRACT: Self = Self(NonZeroU8::new(62).unwrap());
    pub const KW_AND: Self = Self(NonZeroU8::new(63).unwrap());
    pub const KW_AS: Self = Self(NonZeroU8::new(64).unwrap());
    pub const KW_ASYNC: Self = Self(NonZeroU8::new(65).unwrap());
    pub const KW_AWAIT: Self = Self(NonZeroU8::new(66).unwrap());
    pub const KW_BREAK: Self = Self(NonZeroU8::new(67).unwrap());
    pub const KW_CASE: Self = Self(NonZeroU8::new(68).unwrap());
    pub const KW_CATCH: Self = Self(NonZeroU8::new(69).unwrap());
    pub const KW_CLASS: Self = Self(NonZeroU8::new(70).unwrap());
    pub const KW_CLEAN: Self = Self(NonZeroU8::new(71).unwrap());
    pub const KW_CONST: Self = Self(NonZeroU8::new(72).unwrap());
    pub const KW_CONTINUE: Self = Self(NonZeroU8::new(73).unwrap());
    pub const KW_DATA: Self = Self(NonZeroU8::new(74).unwrap());
    pub const KW_DEFAULT: Self = Self(NonZeroU8::new(75).unwrap());
    pub const KW_DO: Self = Self(NonZeroU8::new(76).unwrap());
    pub const KW_ECHO: Self = Self(NonZeroU8::new(77).unwrap());
    pub const KW_ELSE: Self = Self(NonZeroU8::new(78).unwrap());
    pub const KW_ELSEIF: Self = Self(NonZeroU8::new(79).unwrap());
    pub const KW_EMPTY: Self = Self(NonZeroU8::new(80).unwrap());
    pub const KW_ENUM: Self = Self(NonZeroU8::new(81).unwrap());
    pub const KW_EXTENDS: Self = Self(NonZeroU8::new(82).unwrap());
    pub const KW_FALSE: Self = Self(NonZeroU8::new(83).unwrap());
    pub const KW_FINAL: Self = Self(NonZeroU8::new(84).unwrap());
    pub const KW_FINALLY: Self = Self(NonZeroU8::new(85).unwrap());
    pub const KW_FN: Self = Self(NonZeroU8::new(86).unwrap());
    pub const KW_FOR: Self = Self(NonZeroU8::new(87).unwrap());
    pub const KW_FOREACH: Self = Self(NonZeroU8::new(88).unwrap());
    pub const KW_FUNCTION: Self = Self(NonZeroU8::new(89).unwrap());
    pub const KW_IF: Self = Self(NonZeroU8::new(90).unwrap());
    pub const KW_IMPLEMENTS: Self = Self(NonZeroU8::new(91).unwrap());
    pub const KW_INSTANCEOF: Self = Self(NonZeroU8::new(92).unwrap());
    pub const KW_INTERFACE: Self = Self(NonZeroU8::new(93).unwrap());
    pub const KW_ISSET: Self = Self(NonZeroU8::new(94).unwrap());
    pub const KW_LOCK: Self = Self(NonZeroU8::new(95).unwrap());
    pub const KW_MATCH: Self = Self(NonZeroU8::new(96).unwrap());
    pub const KW_MOVE: Self = Self(NonZeroU8::new(97).unwrap());
    pub const KW_NAMESPACE: Self = Self(NonZeroU8::new(98).unwrap());
    pub const KW_NEW: Self = Self(NonZeroU8::new(99).unwrap());
    pub const KW_NULL: Self = Self(NonZeroU8::new(100).unwrap());
    pub const KW_OBJECT_CAST: Self = Self(NonZeroU8::new(101).unwrap());
    pub const KW_OR: Self = Self(NonZeroU8::new(102).unwrap());
    pub const KW_PRIVATE: Self = Self(NonZeroU8::new(103).unwrap());
    pub const KW_PROTECTED: Self = Self(NonZeroU8::new(104).unwrap());
    pub const KW_PUBLIC: Self = Self(NonZeroU8::new(105).unwrap());
    pub const KW_READONLY: Self = Self(NonZeroU8::new(106).unwrap());
    pub const KW_RETURN: Self = Self(NonZeroU8::new(107).unwrap());
    pub const KW_SET: Self = Self(NonZeroU8::new(108).unwrap());
    pub const KW_STATIC: Self = Self(NonZeroU8::new(109).unwrap());
    pub const KW_STRUCT: Self = Self(NonZeroU8::new(110).unwrap());
    pub const KW_SWITCH: Self = Self(NonZeroU8::new(111).unwrap());
    pub const KW_TAKE: Self = Self(NonZeroU8::new(112).unwrap());
    pub const KW_TEMPLATE: Self = Self(NonZeroU8::new(113).unwrap());
    pub const KW_THROW: Self = Self(NonZeroU8::new(114).unwrap());
    pub const KW_TRAIT: Self = Self(NonZeroU8::new(115).unwrap());
    pub const KW_TRUE: Self = Self(NonZeroU8::new(116).unwrap());
    pub const KW_TRY: Self = Self(NonZeroU8::new(117).unwrap());
    pub const KW_TYPENAME: Self = Self(NonZeroU8::new(118).unwrap());
    pub const KW_UNION: Self = Self(NonZeroU8::new(119).unwrap());
    pub const KW_UNSET: Self = Self(NonZeroU8::new(120).unwrap());
    pub const KW_USE: Self = Self(NonZeroU8::new(121).unwrap());
    pub const KW_VOID: Self = Self(NonZeroU8::new(122).unwrap());
    pub const KW_WEAK: Self = Self(NonZeroU8::new(123).unwrap());
    pub const KW_WHILE: Self = Self(NonZeroU8::new(124).unwrap());
    pub const KW_XOR: Self = Self(NonZeroU8::new(125).unwrap());
}

#[derive(Clone, Copy, Debug, Eq, PartialEq)]
pub enum TokenError {
    OffsetTooLarge,
    InvalidLength,
}

impl fmt::Display for TokenError {
    fn fmt(&self, f: &mut fmt::Formatter<'_>) -> fmt::Result {
        f.write_str(match self {
            Self::OffsetTooLarge => "token offset exceeds 32 bits",
            Self::InvalidLength => "token length must be between 1 and 16777215 bytes",
        })
    }
}

impl std::error::Error for TokenError {}

/// Eight-byte native record, not a persistence/serialization format.
/// Source containment and ordering are separate from representation validity.
#[repr(C)]
#[derive(Clone, Copy, Debug, Eq, PartialEq)]
pub struct Token {
    offset: u32,
    packed: u32,
}

impl Token {
    pub const MAX_LENGTH: u32 = 0x00ff_ffff;

    pub fn new(offset: u64, length: u64, kind: TokenKind) -> Result<Self, TokenError> {
        let offset = u32::try_from(offset).map_err(|_| TokenError::OffsetTooLarge)?;
        if length == 0 || length > u64::from(Self::MAX_LENGTH) {
            return Err(TokenError::InvalidLength);
        }
        Ok(Self {
            offset,
            packed: length as u32 | (u32::from(kind.tag()) << 24),
        })
    }

    pub fn offset(self) -> u32 {
        self.offset
    }

    pub fn length(self) -> u32 {
        self.packed & Self::MAX_LENGTH
    }

    pub fn kind(self) -> TokenKind {
        TokenKind::from_tag((self.packed >> 24) as u8)
            .expect("Token constructor preserves a nonzero tag")
    }

    pub fn span(self, source: &SourceFile) -> Result<Span, SourceError> {
        let start = u64::from(self.offset);
        source.span(start, start + u64::from(self.length()))
    }
}

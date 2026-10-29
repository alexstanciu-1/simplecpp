//! In-memory PHS lexical scanning. No decoding, name resolution or runtime policy.
use crate::{
    diagnostic::{Diagnostic, Severity},
    inputs::source::SourceFile,
    tokenize::token::{Token, TokenKind as Kind},
    tokenize::token_sequence::{TokenBuilder, TokenSequence},
};
use std::sync::Arc;

/// Returns a complete generation or the first lexical diagnostic. No partial
/// generation escapes. The source bytes are borrowed and token spellings not copied.
pub fn scan(source: Arc<SourceFile>, previous_count: usize) -> Result<TokenSequence, Diagnostic> {
    let bytes = source.bytes();
    let mut cursor = Cursor { bytes, offset: 0 };
    let mut builder = TokenBuilder::new(Arc::clone(&source), previous_count);
    while cursor.offset < bytes.len() {
        cursor
            .trivia()
            .map_err(|error| diagnostic(&source, error))?;
        if cursor.offset == bytes.len() {
            break;
        }
        let start = cursor.offset;
        let kind = cursor.token().map_err(|error| diagnostic(&source, error))?;
        let length = cursor.offset - start;
        let token = Token::new(start as u64, length as u64, kind)
            .map_err(|_| diagnostic(&source, (start, "Token length exceeds 24-bit capacity")))?;
        builder
            .push(token)
            .expect("scanner emits ordered, contained token spans");
    }
    Ok(builder.finish())
}

type Failure = (usize, &'static str);
fn diagnostic(source: &Arc<SourceFile>, (offset, message): Failure) -> Diagnostic {
    let span = source
        .span(offset as u64, (offset + 1) as u64)
        .expect("lexical errors point to a source byte");
    Diagnostic::new(Severity::Error, message.into(), Arc::clone(source), span)
        .expect("lexical diagnostic belongs to its source")
}
fn letter(byte: u8) -> bool {
    byte == b'_' || byte.is_ascii_alphabetic()
}
fn name_byte(byte: u8) -> bool {
    letter(byte) || byte.is_ascii_digit()
}

struct Cursor<'a> {
    bytes: &'a [u8],
    offset: usize,
}
impl Cursor<'_> {
    fn peek(&self) -> Option<u8> {
        self.bytes.get(self.offset).copied()
    }
    fn starts(&self, spelling: &[u8]) -> bool {
        self.bytes[self.offset..].starts_with(spelling)
    }
    fn advance_while(&mut self, predicate: fn(u8) -> bool) {
        while self.peek().is_some_and(predicate) {
            self.offset += 1;
        }
    }
    fn trivia(&mut self) -> Result<(), Failure> {
        loop {
            self.advance_while(|b| matches!(b, b' ' | b'\t' | b'\r' | b'\n'));
            if self.starts(b"//") || (self.starts(b"#") && !self.starts(b"#[")) {
                self.advance_while(|b| b != b'\r' && b != b'\n');
            } else if self.starts(b"/*") {
                let start = self.offset;
                self.offset += 2;
                while self.peek().is_some() && !self.starts(b"*/") {
                    self.offset += 1;
                }
                if self.peek().is_none() {
                    return Err((start, "Unterminated block comment"));
                }
                self.offset += 2;
            } else {
                return Ok(());
            }
        }
    }
    fn token(&mut self) -> Result<Kind, Failure> {
        let start = self.offset;
        let byte = self.bytes[start];
        if byte == b'$' {
            self.offset += 1;
            if !self.peek().is_some_and(letter) {
                return Err((start, "Expected variable name"));
            }
            self.advance_while(name_byte);
            return Ok(Kind::VARIABLE);
        }
        if letter(byte) {
            self.advance_while(name_byte);
            return Ok(word(&self.bytes[start..self.offset]));
        }
        if matches!(byte, b'\'' | b'"') {
            self.offset += 1;
            while let Some(next) = self.peek() {
                self.offset += 1;
                if next == byte {
                    return Ok(if byte == b'\'' {
                        Kind::SINGLE_STRING
                    } else {
                        Kind::DOUBLE_STRING
                    });
                }
                // Escapes protect the following byte; decoding/validation comes later.
                if next == b'\\' && self.peek().is_some() {
                    self.offset += 1;
                }
            }
            return Err((start, "Unterminated quoted string"));
        }
        if byte.is_ascii_digit()
            || (byte == b'.' && self.bytes.get(start + 1).is_some_and(u8::is_ascii_digit))
        {
            return self.number();
        }
        if let Some((kind, length)) = punctuation(&self.bytes[start..]) {
            self.offset += length;
            return Ok(kind);
        }
        Err((start, "Unsupported token"))
    }
    fn number(&mut self) -> Result<Kind, Failure> {
        let start = self.offset;
        let mut kind = Kind::INTEGER;
        self.advance_while(|b| b.is_ascii_digit());
        if self.peek() == Some(b'.') {
            kind = Kind::FLOATING;
            self.offset += 1;
            self.advance_while(|b| b.is_ascii_digit());
        }
        if matches!(self.peek(), Some(b'e' | b'E')) {
            kind = Kind::FLOATING;
            self.offset += 1;
            if matches!(self.peek(), Some(b'+' | b'-')) {
                self.offset += 1;
            }
            let exponent = self.offset;
            self.advance_while(|b| b.is_ascii_digit());
            if self.offset == exponent {
                return Err((start, "Expected exponent digits"));
            }
        }
        if self.peek().is_some_and(|b| letter(b) || b == b'.') {
            return Err((start, "Unsupported numeric literal"));
        }
        Ok(kind)
    }
}

// Tags classify spellings, not semantic support or reserved-name status.
fn word(bytes: &[u8]) -> Kind {
    match bytes {
        b"abstract" => Kind::KW_ABSTRACT,
        b"and" => Kind::KW_AND,
        b"as" => Kind::KW_AS,
        b"async" => Kind::KW_ASYNC,
        b"await" => Kind::KW_AWAIT,
        b"break" => Kind::KW_BREAK,
        b"case" => Kind::KW_CASE,
        b"catch" => Kind::KW_CATCH,
        b"class" => Kind::KW_CLASS,
        b"clean" => Kind::KW_CLEAN,
        b"const" => Kind::KW_CONST,
        b"continue" => Kind::KW_CONTINUE,
        b"data" => Kind::KW_DATA,
        b"default" => Kind::KW_DEFAULT,
        b"do" => Kind::KW_DO,
        b"echo" => Kind::KW_ECHO,
        b"else" => Kind::KW_ELSE,
        b"elseif" => Kind::KW_ELSEIF,
        b"empty" => Kind::KW_EMPTY,
        b"enum" => Kind::KW_ENUM,
        b"extends" => Kind::KW_EXTENDS,
        b"false" => Kind::KW_FALSE,
        b"final" => Kind::KW_FINAL,
        b"finally" => Kind::KW_FINALLY,
        b"fn" => Kind::KW_FN,
        b"for" => Kind::KW_FOR,
        b"foreach" => Kind::KW_FOREACH,
        b"function" => Kind::KW_FUNCTION,
        b"if" => Kind::KW_IF,
        b"implements" => Kind::KW_IMPLEMENTS,
        b"instanceof" => Kind::KW_INSTANCEOF,
        b"interface" => Kind::KW_INTERFACE,
        b"isset" => Kind::KW_ISSET,
        b"lock" => Kind::KW_LOCK,
        b"match" => Kind::KW_MATCH,
        b"move" => Kind::KW_MOVE,
        b"namespace" => Kind::KW_NAMESPACE,
        b"new" => Kind::KW_NEW,
        b"null" => Kind::KW_NULL,
        b"object_cast" => Kind::KW_OBJECT_CAST,
        b"or" => Kind::KW_OR,
        b"private" => Kind::KW_PRIVATE,
        b"protected" => Kind::KW_PROTECTED,
        b"public" => Kind::KW_PUBLIC,
        b"readonly" => Kind::KW_READONLY,
        b"return" => Kind::KW_RETURN,
        b"set" => Kind::KW_SET,
        b"static" => Kind::KW_STATIC,
        b"struct" => Kind::KW_STRUCT,
        b"switch" => Kind::KW_SWITCH,
        b"take" => Kind::KW_TAKE,
        b"template" => Kind::KW_TEMPLATE,
        b"throw" => Kind::KW_THROW,
        b"trait" => Kind::KW_TRAIT,
        b"true" => Kind::KW_TRUE,
        b"try" => Kind::KW_TRY,
        b"typename" => Kind::KW_TYPENAME,
        b"union" => Kind::KW_UNION,
        b"unset" => Kind::KW_UNSET,
        b"use" => Kind::KW_USE,
        b"void" => Kind::KW_VOID,
        b"weak" => Kind::KW_WEAK,
        b"while" => Kind::KW_WHILE,
        b"xor" => Kind::KW_XOR,
        _ => Kind::IDENTIFIER,
    }
}

fn punctuation(bytes: &[u8]) -> Option<(Kind, usize)> {
    Some(match bytes {
        [b'.', b'.', b'.', ..] => (Kind::ELLIPSIS, 3),
        [b'*', b'*', b'=', ..] => (Kind::POWER_ASSIGN, 3),
        [b'<', b'<', b'=', ..] => (Kind::LEFT_ASSIGN, 3),
        [b'>', b'>', b'=', ..] => (Kind::RIGHT_ASSIGN, 3),
        [b'=', b'=', b'=', ..] => (Kind::IDENTICAL, 3),
        [b'!', b'=', b'=', ..] => (Kind::NOT_IDENTICAL, 3),
        [b'<', b'=', b'>', ..] => (Kind::SPACESHIP, 3),
        [b'#', b'[', ..] => (Kind::ATTRIBUTE, 2),
        [b'=', b'=', ..] => (Kind::EQUAL, 2),
        [b'!', b'=', ..] => (Kind::NOT_EQUAL, 2),
        [b'<', b'=', ..] => (Kind::LESS_EQUAL, 2),
        [b'>', b'=', ..] => (Kind::GREATER_EQUAL, 2),
        [b'=', b'>', ..] => (Kind::ARROW, 2),
        [b'-', b'>', ..] => (Kind::MEMBER, 2),
        [b':', b':', ..] => (Kind::SCOPE, 2),
        [b'?', b'?', ..] => (Kind::COALESCE, 2),
        [b'&', b'&', ..] => (Kind::LOGICAL_AND, 2),
        [b'|', b'|', ..] => (Kind::LOGICAL_OR, 2),
        [b'+', b'+', ..] => (Kind::INCREMENT, 2),
        [b'-', b'-', ..] => (Kind::DECREMENT, 2),
        [b'*', b'*', ..] => (Kind::POWER, 2),
        [b'+', b'=', ..] => (Kind::PLUS_ASSIGN, 2),
        [b'-', b'=', ..] => (Kind::MINUS_ASSIGN, 2),
        [b'*', b'=', ..] => (Kind::STAR_ASSIGN, 2),
        [b'/', b'=', ..] => (Kind::SLASH_ASSIGN, 2),
        [b'%', b'=', ..] => (Kind::PERCENT_ASSIGN, 2),
        [b'.', b'=', ..] => (Kind::CONCAT_ASSIGN, 2),
        [b'&', b'=', ..] => (Kind::AND_ASSIGN, 2),
        [b'|', b'=', ..] => (Kind::OR_ASSIGN, 2),
        [b'^', b'=', ..] => (Kind::XOR_ASSIGN, 2),
        [b';', ..] => (Kind::SEMICOLON, 1),
        [b'(', ..] => (Kind::LPAREN, 1),
        [b')', ..] => (Kind::RPAREN, 1),
        [b'{', ..] => (Kind::LBRACE, 1),
        [b'}', ..] => (Kind::RBRACE, 1),
        [b'?', ..] => (Kind::QUESTION, 1),
        [b':', ..] => (Kind::COLON, 1),
        [b',', ..] => (Kind::COMMA, 1),
        [b'&', ..] => (Kind::AMPERSAND, 1),
        [b'[', ..] => (Kind::LBRACKET, 1),
        [b']', ..] => (Kind::RBRACKET, 1),
        [b'<', ..] => (Kind::LESS, 1),
        [b'>', ..] => (Kind::GREATER, 1),
        [b'+', ..] => (Kind::PLUS, 1),
        [b'-', ..] => (Kind::MINUS, 1),
        [b'*', ..] => (Kind::STAR, 1),
        [b'/', ..] => (Kind::SLASH, 1),
        [b'%', ..] => (Kind::PERCENT, 1),
        [b'.', ..] => (Kind::DOT, 1),
        [b'!', ..] => (Kind::BANG, 1),
        [b'~', ..] => (Kind::TILDE, 1),
        [b'|', ..] => (Kind::PIPE, 1),
        [b'^', ..] => (Kind::CARET, 1),
        [b'\\', ..] => (Kind::BACKSLASH, 1),
        [b'=', ..] => (Kind::ASSIGN, 1),
        _ => return None,
    })
}

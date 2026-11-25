// Benchmark-only safe Rust scanner. Vocabulary comes from the same flavor contract as C++.
include!(concat!(env!("VOCAB_DIR"), "/vocabulary.rs"));

#[repr(C)]
pub struct Token { pub offset: i64, pub length: i64, pub kind: i64 }
pub struct Scan { pub source: Vec<u8>, pub tokens: Vec<Token>, pub error: Option<usize> }

fn letter(b: u8) -> bool { b == b'_' || b.is_ascii_alphabetic() }

pub fn scan(source: Vec<u8>, hint: usize) -> Scan {
    let mut tokens = Vec::with_capacity(hint);
    let mut i = 0;
    while i < source.len() {
        let start = i;
        let b = source[i];
        i += 1;
        let kind = match b {
            b' ' | b'\t' | b'\n' | b'\r' => continue,
            b'=' => {
                if source.get(i) != Some(&b'=') { 61 } else {
                    i += 1;
                    if source.get(i) == Some(&b'=') { i += 1; 11 } else { 15 }
                }
            }
            _ if letter(b) => {
                while i < source.len() && (letter(source[i]) || source[i].is_ascii_digit()) { i += 1; }
                if keyword(&source[start..i]) { 62 } else { 1 }
            }
            b'0'..=b'9' => {
                while i < source.len() && source[i].is_ascii_digit() { i += 1; }
                if source.get(i) == Some(&b'.') && source.get(i+1).is_some_and(u8::is_ascii_digit) {
                    i += 1;
                    while i < source.len() && source[i].is_ascii_digit() { i += 1; }
                    4
                } else { 3 }
            }
            b'"' | b'\'' => {
                let mut closed = false;
                while i < source.len() {
                    let c = source[i];
                    i += 1;
                    if c == b { closed = true; break; }
                    if c == b'\\' && i < source.len() { i += 1; }
                }
                if !closed { 0 } else if b == b'"' { 6 } else { 5 }
            }
            b'$' if PHP => {
                if i < source.len() && letter(source[i]) {
                    i += 1;
                    while i < source.len() && (letter(source[i]) || source[i].is_ascii_digit()) { i += 1; }
                    2
                } else { 0 }
            }
            _ => match punctuation(&source[start..]) {
                Some((k,n)) => { i = start+n; k }
                None => 0,
            }
        };
        if kind == 0 {
            tokens.clear();
            return Scan {source, tokens, error: Some(start)};
        }
        tokens.push(Token {offset: start as i64, length: (i-start) as i64, kind});
    }
    Scan {source, tokens, error: None}
}

use rust_scanner::{scan, Scan, Token};
use std::{env, fs, fmt::Write, time::Instant};

fn dump(r: &Scan) -> String {
    let mut out = String::new();
    if let Some(pos) = r.error {
        assert!(r.tokens.is_empty());
        writeln!(out,"error {pos} Unsupported token").unwrap();
    }
    for t in &r.tokens {
        let slice = &r.source[t.offset as usize..(t.offset+t.length) as usize];
        writeln!(out,"{} {} {} {}",t.offset,t.length,t.kind,std::str::from_utf8(slice).unwrap()).unwrap();
    }
    out
}
fn main() {
    assert_eq!(std::mem::size_of::<Token>(),24);
    let args: Vec<String> = env::args().collect();
    let input = fs::read(&args[2]).unwrap();
    if args[1] == "edits" {
        let sibling = scan(input,0);
        let original = dump(&sibling);
        let previous = scan(fs::read(&args[3]).unwrap(),0);
        let old = dump(&previous);
        let mut hint = sibling.tokens.len();
        for path in &args[3..] {
            let r = scan(fs::read(path).unwrap(),hint);
            if r.error.is_none() { hint = r.tokens.len(); }
            print!("{}|\n",dump(&r));
        }
        assert_eq!(dump(&sibling),original);
        assert_eq!(dump(&previous),old);
        return;
    }
    let hint = args.get(3).map_or(0, |p| scan(fs::read(p).unwrap(),0).tokens.len());
    if args[1] == "dump" { print!("{}",dump(&scan(input,hint))); return; }
    let n = scan(input.clone(),0).tokens.len();
    for _ in 0..9 {
        let start = Instant::now();
        for _ in 0..128 {
            let r = scan(input.clone(),hint);
            assert!(r.tokens.len()==n && r.error.is_none());
        }
        println!("{}",start.elapsed().as_secs_f64()/128.0);
    }
}

use std::{env,fs,hint::black_box,time::Instant};
fn batch(op:&str,s:&str,t:&str,n:&str,r:&str,count:u64)->(u128,u64) {
    let mut sum=0;
    let start=Instant::now();
    for _ in 0..count {
        let subject=black_box(s);
        if op=="replace" { let out=subject.replace(black_box(n),black_box(r)); sum+=black_box(&out).len() as u64; }
        else if op=="compare" { sum+=match subject.cmp(black_box(t)) {std::cmp::Ordering::Less=>0,std::cmp::Ordering::Equal=>1,std::cmp::Ordering::Greater=>2}; }
        else { sum+=subject.len() as u64; }
    }
    (start.elapsed().as_nanos(),sum)
}
fn main() {
    let data=fs::read_to_string(env::args().nth(1).unwrap()).unwrap();
    for line in data.lines() {
        let f:Vec<&str>=line.split('\t').collect();
        let (op,s,t,n,r)=(f[1],f[2],f[3],f[4],f[5]);
        if op=="replace" { assert_eq!(s.replace(n,r),f[6]); }
        if op=="compare" { assert_eq!(s.cmp(t) as i32,f[6].parse::<i32>().unwrap()); }
        let mut count=1;
        while batch(op,s,t,n,r,count).0<20_000_000 && count<(1<<26) {count*=2;}
        for trial in 0..7 {let(ns,sum)=batch(op,s,t,n,r,count); println!("{},rust,{},{},{},{}",f[0],trial,count,ns,sum);}
    }
}

extern "C" { fn consume(data: *const u8, count: usize) -> u64; }

#[no_mangle]
pub extern "C" fn format_sample(kind: u32, index: u32) -> u64 {
    let text = match kind {
        0 => (index % 2 != 0).to_string(),
        1 => (index as u8).to_string(),
        2 => (i64::from(index) * 97003 - 50000000).to_string(),
        _ => (f64::from(index % 10000) / 16.0 + 0.03125).to_string(),
    };
    unsafe { consume(text.as_ptr(), text.len()) }
}

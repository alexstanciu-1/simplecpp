#[no_mangle]
pub extern "C" fn make_value(bytes: usize) -> *mut String {
    Box::into_raw(Box::new("x".repeat(bytes)))
}
#[no_mangle]
pub unsafe extern "C" fn length(value: *const String) -> u64 {
    (*value).len() as u64
}
#[no_mangle]
pub unsafe extern "C" fn release_value(value: *mut String) {
    drop(Box::from_raw(value));
}

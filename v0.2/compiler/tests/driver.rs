use scpp_compiler::compiler::driver::Driver;
use scpp_compiler::language::Flavor;
use std::path::PathBuf;

struct Fixture(PathBuf);
impl Drop for Fixture {
    fn drop(&mut self) {
        let _ = std::fs::remove_dir_all(&self.0);
    }
}

#[test]
fn driver_recovers_from_failed_reads_and_scans_without_invalidating_snapshots() {
    let fixture = Fixture(std::env::temp_dir().join(format!("scpp-driver-{}", std::process::id())));
    std::fs::create_dir(&fixture.0).unwrap();
    let path = fixture.0.join("input.phs");
    let mut driver = Driver::default();
    assert!(
        driver
            .scan_file(path.clone(), Flavor::Php)
            .unwrap_err()
            .contains("cannot read")
    );
    std::fs::write(&path, b"$x int = 1;").unwrap();
    let first = driver.scan_file(path.clone(), Flavor::Php).unwrap();
    std::fs::write(&path, b"1e+").unwrap();
    assert!(
        driver
            .scan_file(path.clone(), Flavor::Php)
            .unwrap_err()
            .contains("Expected exponent digits")
    );
    std::fs::write(&path, b"return 2;").unwrap();
    let second = driver.scan_file(path.clone(), Flavor::Php).unwrap();
    let note = driver.inspect_source(path.clone(), 0).unwrap();
    assert!(driver.inspect_source(path.clone(), 100).is_err());
    assert_ne!(first.source().id(), second.source().id());
    assert_ne!(second.source().id(), note.source().id());
    let mut files = vec![];
    for (flavor, text) in [
        (Flavor::Cpp, "auto count = 3;"),
        (Flavor::Php, "$count = 3;"),
        (Flavor::Js, "let count = 3;"),
    ] {
        std::fs::write(&path, text).unwrap();
        let file = driver.parse_file(path.clone(), flavor).unwrap();
        assert_eq!(file.tokens.flavor(), flavor);
        files.push(file);
    }
    assert_ne!(files[0].tokens.source().id(), files[1].tokens.source().id());
    drop(driver);
    assert_eq!(files[0].tokens.source().bytes(), b"auto count = 3;");

    assert_eq!(first.source().bytes(), b"$x int = 1;");
    assert_eq!(second.source().bytes(), b"return 2;");
    assert_eq!(note.message(), "source contains 9 bytes; offset 0");
}

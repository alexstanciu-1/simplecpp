use std::path::PathBuf;
use std::process::{Command, Output};

fn run(args: &[&str]) -> Output {
    Command::new(env!("CARGO_BIN_EXE_scpp-compiler"))
        .args(args)
        .output()
        .unwrap()
}

struct Fixture(PathBuf);
impl Fixture {
    fn new() -> Self {
        let directory = std::env::temp_dir().join(format!("scpp-rust-cli-{}", std::process::id()));
        std::fs::create_dir(&directory).unwrap();
        Self(directory)
    }
}
impl Drop for Fixture {
    fn drop(&mut self) {
        let _ = std::fs::remove_dir_all(&self.0);
    }
}

#[test]
fn command_line_contract() {
    let fixture = Fixture::new();
    let file = fixture.0.join("source bytes.phs");
    std::fs::write(&file, [b'a', b'\r', b'\n', 0, 255]).unwrap();
    let path = file.to_str().unwrap();
    for (offset, location) in [("0", "1:1"), ("3", "2:1"), ("5", "2:3")] {
        let result = run(&["source", path, offset]);
        assert!(result.status.success());
        assert!(result.stderr.is_empty());
        assert_eq!(
            String::from_utf8(result.stdout).unwrap(),
            format!("{path}:{location}: note: source contains 5 bytes; offset {offset}\n")
        );
    }
    assert!(run(&["source", path]).status.success());
    assert!(run(&["--help"]).status.success());
    assert!(
        String::from_utf8(run(&["--help"]).stdout)
            .unwrap()
            .contains("no scanning or compilation yet")
    );
    assert_eq!(
        run(&["--version"]).stdout,
        b"scpp-compiler 0.2.0 (foundation)\n"
    );
    for args in [
        vec![],
        vec!["compile", path],
        vec!["source"],
        vec!["source", path, "-1"],
        vec!["source", path, "18446744073709551616"],
        vec!["source", path, "6"],
        vec!["source", path, "0", "extra"],
    ] {
        let result = run(&args);
        assert_eq!(result.status.code(), Some(1));
        assert!(result.stdout.is_empty());
        let error = String::from_utf8(result.stderr).unwrap();
        assert!(error.starts_with("error: "));
        assert!(!error.contains("panicked"));
    }
    let missing = fixture.0.join("missing");
    let result = run(&["source", missing.to_str().unwrap()]);
    assert_eq!(result.status.code(), Some(1));
    assert!(
        String::from_utf8(result.stderr)
            .unwrap()
            .contains("cannot read")
    );
    std::fs::write(&file, []).unwrap();
    let result = run(&["source", path]);
    assert!(result.status.success());
    assert!(
        String::from_utf8(result.stdout)
            .unwrap()
            .contains(":1:1: note: source contains 0 bytes")
    );
}

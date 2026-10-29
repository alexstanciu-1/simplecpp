use scpp_compiler::diagnostic::{Diagnostic, Severity};
use scpp_compiler::source::SourceMap;
use std::ffi::OsString;
use std::io::{self, Write};
use std::path::PathBuf;
use std::process::ExitCode;

const HELP: &str = "Simple C++ compiler foundation (PHS scanning; no compilation yet)\n\
Usage: scpp-compiler source <path> [byte-offset]\n\
       scpp-compiler scan <path>\n\
       scpp-compiler --help | --version\n\
Reports a one-based line and byte-column for a source offset (default 0).\n";

fn run(args: Vec<OsString>, output: &mut impl Write) -> Result<(), String> {
    if args.len() == 1 && args[0] == "--help" {
        return write!(output, "{HELP}").map_err(|e| e.to_string());
    }
    if args.len() == 1 && args[0] == "--version" {
        return writeln!(
            output,
            "scpp-compiler {} (foundation)",
            env!("CARGO_PKG_VERSION")
        )
        .map_err(|e| e.to_string());
    }
    if args.len() == 2 && args[0] == "scan" {
        let path = PathBuf::from(&args[1]);
        let bytes = std::fs::read(&path)
            .map_err(|e| format!("cannot read {}: {e}", path.to_string_lossy().escape_debug()))?;
        let source = SourceMap::default()
            .add(path, bytes)
            .map_err(|e| e.to_string())?;
        let tokens = scpp_compiler::scanner::scan(source, 0).map_err(|e| e.to_string())?;
        for token in tokens.tokens() {
            writeln!(
                output,
                "{} {} {}",
                token.offset(),
                token.length(),
                token.kind().tag()
            )
            .map_err(|e| e.to_string())?;
        }
        return Ok(());
    }
    if !(args.len() == 2 || args.len() == 3) || args[0] != "source" {
        return Err("expected source <path> [byte-offset] or scan <path>; use --help".to_owned());
    }
    let offset = if args.len() == 3 {
        args[2]
            .to_str()
            .and_then(|s| s.parse::<u64>().ok())
            .ok_or_else(|| "byte-offset must be an unsigned 64-bit integer".to_owned())?
    } else {
        0
    };
    let path = PathBuf::from(&args[1]);
    let bytes = std::fs::read(&path)
        .map_err(|e| format!("cannot read {}: {e}", path.to_string_lossy().escape_debug()))?;
    let mut sources = SourceMap::default();
    let source = sources.add(path, bytes).map_err(|e| e.to_string())?;
    let span = source.span(offset, offset).map_err(|e| {
        format!(
            "{}: {e} (offset {offset})",
            source.name().to_string_lossy().escape_debug()
        )
    })?;
    let message = format!(
        "source contains {} bytes; offset {offset}",
        source.byte_len()
    );
    let diagnostic =
        Diagnostic::new(Severity::Note, message, source, span).map_err(|e| e.to_string())?;
    writeln!(output, "{diagnostic}").map_err(|e| e.to_string())
}

fn main() -> ExitCode {
    let args = std::env::args_os().skip(1).collect();
    match run(args, &mut io::stdout().lock()) {
        Ok(()) => ExitCode::SUCCESS,
        Err(message) => {
            let _ = writeln!(io::stderr().lock(), "error: {message}");
            ExitCode::FAILURE
        }
    }
}

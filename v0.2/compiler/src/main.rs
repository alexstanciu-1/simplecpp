use scpp_compiler::{compiler::driver::Driver, language::Flavor};
use std::ffi::OsString;
use std::io::{self, Write};
use std::path::PathBuf;
use std::process::ExitCode;

const HELP: &str = "Simple C++ compiler foundation (three syntax flavors; no compilation yet)\n\
Usage: scpp-compiler source <path> [byte-offset]\n\
       scpp-compiler scan <cpp|php|js> <path>\n\
       scpp-compiler parse <cpp|php|js> <path>\n\
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
    if args.len() == 3 && (args[0] == "scan" || args[0] == "parse") {
        let flavor: Flavor = args[1].to_str().ok_or("flavor must be UTF-8")?.parse()?;
        let path = PathBuf::from(&args[2]);
        if args[0] == "parse" {
            let parsed = Driver::default().parse_file(path, flavor)?;
            return writeln!(
                output,
                "parsed {} statements, {} expressions",
                parsed.statements.len(),
                parsed.expressions.len()
            )
            .map_err(|e| e.to_string());
        }
        let tokens = Driver::default().scan_file(path, flavor)?;
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
        return Err(
            "expected source <path> [byte-offset] or scan/parse <cpp|php|js> <path>; use --help"
                .to_owned(),
        );
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
    let diagnostic = Driver::default().inspect_source(path, offset)?;
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

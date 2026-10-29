use scpp_api_tooling::{
    Error, Result, integer, json,
    target::{TargetContext, TargetFacts},
};
use std::{
    ffi::OsString,
    io::{self, Write},
    path::Path,
    process::ExitCode,
};
fn read(path: &OsString) -> Result<Vec<u8>> {
    std::fs::read(path)
        .map_err(|e| Error(format!("Cannot read {}: {e}", Path::new(path).display())))
}
fn run(args: &[OsString]) -> Result<serde_json::Value> {
    if (args.len() == 6 || args.len() == 7)
        && (args[0] == "emit-scalars" || args[0] == "emit-runtime")
    {
        let check = args.len() == 7;
        if check && args[6] != "--check" {
            return Err(Error("Unknown generator option".into()));
        }
        let context = TargetContext::parse(&read(&args[4])?)?;
        let facts = TargetFacts::parse(&read(&args[3])?, &context)?;
        let metadata = json::parse(&read(&args[1])?, "runtime metadata")?;
        let publication = json::parse(&read(&args[2])?, "publication")?;
        let files = if args[0] == "emit-runtime" {
            let plan = scpp_api_tooling::runtime::prepare(&metadata, &publication, &facts)?;
            scpp_api_tooling::emission::emit_runtime(&plan)?
        } else {
            let plan = scpp_api_tooling::scalar::prepare(&metadata, &publication, &facts)?;
            scpp_api_tooling::emission::emit_scalars(&plan)?
        };
        scpp_api_tooling::emission::publish(&files, Path::new(&args[5]), check)?;
        return Ok(
            serde_json::json!({"status":if check {"verified"} else {"generated"},"artifacts":files.len()}),
        );
    }
    if args.len() == 3 && args[0] == "validate-target" {
        let context = TargetContext::parse(&read(&args[2])?)?;
        let facts = TargetFacts::parse(&read(&args[1])?, &context)?;
        return Ok(
            serde_json::json!({"context_id":facts.context().id(),"target":facts.context().target(),"standard_library":facts.standard_library(),"status":"valid"}),
        );
    }
    if args.len() == 5
        && (args[0] == "prepare-integer-construction"
            || args[0] == "prepare-scalars"
            || args[0] == "prepare-runtime")
    {
        let context = TargetContext::parse(&read(&args[4])?)?;
        let facts = TargetFacts::parse(&read(&args[3])?, &context)?;
        let metadata = json::parse(&read(&args[1])?, "runtime metadata")?;
        let publication = json::parse(&read(&args[2])?, "publication")?;
        return if args[0] == "prepare-runtime" {
            Ok(scpp_api_tooling::runtime::prepare(&metadata, &publication, &facts)?.describe())
        } else if args[0] == "prepare-scalars" {
            Ok(scpp_api_tooling::scalar::prepare(&metadata, &publication, &facts)?.describe())
        } else {
            Ok(integer::prepare(&metadata, &publication, &facts)?.describe())
        };
    }
    Err(Error("Usage: scpp-api-tooling emit-runtime|emit-scalars METADATA PUBLICATION FACTS CONTEXT OUTPUT [--check] | validate-target FACTS CONTEXT | prepare-runtime|prepare-scalars|prepare-integer-construction METADATA PUBLICATION FACTS CONTEXT (prepare commands only inspect plans)".into()))
}
fn main() -> ExitCode {
    let result = run(&std::env::args_os().skip(1).collect::<Vec<_>>()).and_then(|value| {
        writeln!(io::stdout().lock(), "{value}").map_err(|e| Error(e.to_string()))
    });
    match result {
        Ok(()) => ExitCode::SUCCESS,
        Err(e) => {
            let _ = writeln!(io::stderr().lock(), "{e}");
            ExitCode::FAILURE
        }
    }
}

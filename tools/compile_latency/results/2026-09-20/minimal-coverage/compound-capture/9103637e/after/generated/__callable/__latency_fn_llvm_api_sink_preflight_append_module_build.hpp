#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct LlvmApiModuleBuildRow;
struct LlvmApiSinkPreflightArtifact;
void __latency_fn_llvm_api_sink_preflight_append_module_build(LlvmApiSinkPreflightArtifact& artifact, LlvmApiModuleBuildRow row);
}

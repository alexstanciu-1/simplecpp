#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class NativeExecutionArtifact;
shared_p<NativeExecutionArtifact> __latency_fn_backend_native_execution_adapter_compile_and_run_llvm_artifact(const string_t& nativeOutDir, const string_t& runLabel, const string_t& nativeCompiler);
}

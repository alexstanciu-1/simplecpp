#include <scpp/lang/php.hpp>
#include "__types/NativeExecutionArtifact.hpp"
#include "__callable/__latency_fn_backend_native_execution_adapter_compile_and_run_llvm_artifact.hpp"
#include "__callable/__latency_fn_backend_native_execution_adapter_compile_and_run_llvm_json.hpp"
#include "__callable/__latency_fn_backend_native_execution_adapter_json_from_artifact.hpp"
namespace scpp { extern const int __latency_lines_backend_native_execution_adapter[]; }
namespace scpp {
string_t __latency_fn_backend_native_execution_adapter_compile_and_run_llvm_json(const string_t& nativeOutDir, const string_t& runLabel, const string_t& nativeCompiler) {
	SCPP_CALL_DEPTH_GUARD("backend_native_execution_adapter::compile_and_run_llvm_json", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_native_execution_adapter.phs", __latency_lines_backend_native_execution_adapter[10]);
	return __latency_fn_backend_native_execution_adapter_json_from_artifact(__latency_fn_backend_native_execution_adapter_compile_and_run_llvm_artifact(nativeOutDir, runLabel, nativeCompiler));
}

}

#include <scpp/lang/php.hpp>
#include "__types/EmissionLLVMWorkerInput.hpp"
#include "__types/EmissionLLVMWorkerResult.hpp"
namespace scpp {
extern const int __latency_partition_lines_636f6d70696c652f6261636b656e642f6c6c766d5f746578745f66726f6d5f706c616e[];
bool_t EmissionLLVMWorkerInput::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == EmissionLLVMWorkerInput::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

bool_t EmissionLLVMWorkerResult::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == EmissionLLVMWorkerResult::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

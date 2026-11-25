#include <scpp/lang/php.hpp>
#include "__types/BackendLoweringWorkerInput.hpp"
#include "__types/BackendLoweringWorkerResult.hpp"
namespace scpp {
extern const int __latency_partition_lines_636f6d70696c652f6261636b656e642f6c6f776572696e675f706c616e[];
bool_t BackendLoweringWorkerInput::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == BackendLoweringWorkerInput::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

bool_t BackendLoweringWorkerResult::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == BackendLoweringWorkerResult::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

#include <scpp/lang/php.hpp>
#include "__types/CapabilityReadinessWorkerInput.hpp"
#include "__types/CapabilityReadinessWorkerResult.hpp"
namespace scpp {
extern const int __latency_partition_lines_636f6d70696c652f6361706162696c69746965732f747970655f6361706162696c6974795f72656164696e657373[];
bool_t CapabilityReadinessWorkerInput::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == CapabilityReadinessWorkerInput::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

bool_t CapabilityReadinessWorkerResult::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == CapabilityReadinessWorkerResult::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

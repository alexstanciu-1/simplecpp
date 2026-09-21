#include <scpp/lang/php.hpp>
#include "__types/ObjectOutputWorkerInput.hpp"
#include "__types/ObjectOutputWorkerResult.hpp"
namespace scpp {
extern const int __latency_partition_lines_636f6d70696c652f6261636b656e642f6261636b656e645f6f626a6563745f6c696e6b5f6361636865[];
bool_t ObjectOutputWorkerInput::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == ObjectOutputWorkerInput::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

bool_t ObjectOutputWorkerResult::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == ObjectOutputWorkerResult::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

#include <scpp/lang/php.hpp>
#include "__types/StorageLifetimeWorkerInput.hpp"
#include "__types/StorageLifetimeWorkerResult.hpp"
namespace scpp {
extern const int __latency_partition_lines_636f6d70696c652f6361706162696c69746965732f73746f726167655f6c69666574696d655f72656164696e657373[];
bool_t StorageLifetimeWorkerInput::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == StorageLifetimeWorkerInput::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

bool_t StorageLifetimeWorkerResult::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == StorageLifetimeWorkerResult::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

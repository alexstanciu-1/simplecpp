#include <scpp/lang/php.hpp>
#include "__types/SourceReadWorkerProbeInput.hpp"
#include "__types/SourceReadWorkerPublicationStats.hpp"
#include "__types/SourceReadWorkerResult.hpp"
namespace scpp {
extern const int __latency_partition_lines_636f6d70696c652f6d6f64656c2f736f757263655f756e697473[];
bool_t SourceReadWorkerProbeInput::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == SourceReadWorkerProbeInput::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

bool_t SourceReadWorkerResult::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == SourceReadWorkerResult::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

bool_t SourceReadWorkerPublicationStats::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == SourceReadWorkerPublicationStats::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

#include <scpp/lang/php.hpp>
#include "__types/PipelineConfig.hpp"

namespace scpp {
	using namespace ::scpp;

bool_t PipelineConfig::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == PipelineConfig::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}


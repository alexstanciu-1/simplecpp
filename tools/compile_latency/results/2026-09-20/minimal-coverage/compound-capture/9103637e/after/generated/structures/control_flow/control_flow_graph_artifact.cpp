#include <scpp/lang/php.hpp>
#include "__types/ControlFlowGraphArtifact.hpp"

namespace scpp {
	using namespace ::scpp;

bool_t ControlFlowGraphArtifact::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == ControlFlowGraphArtifact::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}


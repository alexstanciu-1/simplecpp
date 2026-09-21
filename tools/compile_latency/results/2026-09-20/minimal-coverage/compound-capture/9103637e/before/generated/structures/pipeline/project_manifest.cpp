#include <scpp/lang/php.hpp>
#include "__types/ProjectManifest.hpp"

namespace scpp {
	using namespace ::scpp;

bool_t ProjectManifest::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == ProjectManifest::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}


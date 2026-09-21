#include <scpp/lang/php.hpp>
#include "__types/NativeExecutionArtifact.hpp"
namespace scpp {
	using namespace ::scpp;
bool_t NativeExecutionArtifact::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == NativeExecutionArtifact::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

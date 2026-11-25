#include <scpp/lang/php.hpp>
#include "__types/BackendRequestAuthorizationArtifact.hpp"
#include "__types/BackendRequestRowList.hpp"

namespace scpp {
	using namespace ::scpp;

bool_t BackendRequestAuthorizationArtifact::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == BackendRequestAuthorizationArtifact::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

BackendRequestAuthorizationArtifact::BackendRequestAuthorizationArtifact() {
	SCPP_CALL_DEPTH_GUARD("BackendRequestAuthorizationArtifact::__construct", "/tmp/scpp-edit-latency-20260919/app/structures/backend/backend_request_authorization_artifact.phs", 23);
	this->request_rows = create<BackendRequestRowList>();
}

}


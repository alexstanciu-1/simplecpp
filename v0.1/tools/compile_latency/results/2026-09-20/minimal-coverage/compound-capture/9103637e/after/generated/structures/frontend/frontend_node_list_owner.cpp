#include <scpp/lang/php.hpp>
#include "__types/FrontendNodeList.hpp"
#include "__types/FrontendNodeListOwner.hpp"

namespace scpp {
	using namespace ::scpp;

bool_t FrontendNodeListOwner::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == FrontendNodeListOwner::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

FrontendNodeListOwner::FrontendNodeListOwner() {
	SCPP_CALL_DEPTH_GUARD("FrontendNodeListOwner::__construct", "/tmp/scpp-edit-latency-20260919/app/structures/frontend/frontend_node_list_owner.phs", 12);
	this->current_rows = create<FrontendNodeList>();
	this->retained_rows = create<FrontendNodeList>();
}

}


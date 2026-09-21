#include <scpp/lang/php.hpp>
#include "__types/FrontendModel.hpp"
#include "__types/FrontendNodeList.hpp"

namespace scpp {
	using namespace ::scpp;

bool_t FrontendModel::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == FrontendModel::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

FrontendModel::FrontendModel() {
	SCPP_CALL_DEPTH_GUARD("FrontendModel::__construct", "/tmp/scpp-edit-latency-20260919/app/structures/frontend/frontend_model.phs", 32);
	this->node_rows = create<FrontendNodeList>();
}

}


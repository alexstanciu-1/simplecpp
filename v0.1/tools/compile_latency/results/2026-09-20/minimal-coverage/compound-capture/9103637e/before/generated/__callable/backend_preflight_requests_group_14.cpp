#include <scpp/lang/php.hpp>
#include "__types/BackendRequestAuthorizationArtifact.hpp"
#include "__types/BackendRequestAuthorizationRow.hpp"
#include "__types/BackendRequestRowList.hpp"
#include "__types/BackendRequestRowSpan.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_first_request_span.hpp"
#include "__callable/__latency_fn_backend_request_row_lists_first_span.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_next_request_span.hpp"
#include "__callable/__latency_fn_backend_request_row_lists_next_span.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_request_span_is_empty.hpp"
#include "__callable/__latency_fn_backend_request_row_lists_span_is_empty.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_span_request_at.hpp"
#include "__callable/__latency_fn_backend_request_row_lists_span_request_at.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_work_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_work_contract.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_work_project_callable_contract.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_work_source_row.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_work_source_reference.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_work_target_symbol.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_work_feature.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_work_lowering_adapter.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_work_step_kind.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_work_type_ref.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_work_cache_owner_symbol.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_work_status.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_work_blocked_reason.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_work_project_callable_blocked_reason.hpp"
namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
BackendRequestRowSpan __latency_fn_backend_preflight_requests_first_request_span(shared_p<BackendRequestAuthorizationArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::first_request_span", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[179]);
	return __latency_fn_backend_request_row_lists_first_span(artifact->request_rows);
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
BackendRequestRowSpan __latency_fn_backend_preflight_requests_next_request_span(shared_p<BackendRequestAuthorizationArtifact> artifact, BackendRequestRowSpan span) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::next_request_span", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[180]);
	return __latency_fn_backend_request_row_lists_next_span(artifact->request_rows, span);
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
bool_t __latency_fn_backend_preflight_requests_request_span_is_empty(BackendRequestRowSpan span) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::request_span_is_empty", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[181]);
	return __latency_fn_backend_request_row_lists_span_is_empty(span);
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
BackendRequestAuthorizationRow __latency_fn_backend_preflight_requests_span_request_at(shared_p<BackendRequestAuthorizationArtifact> artifact, BackendRequestRowSpan span, int_t<> offset) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::span_request_at", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[182]);
	return __latency_fn_backend_request_row_lists_span_request_at(artifact->request_rows, span, offset);
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_backend_preflight_requests_work_id(BackendRequestAuthorizationRow work) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::work_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[183]);
	return work->request_id;
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_backend_preflight_requests_work_contract(BackendRequestAuthorizationRow work) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::work_contract", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[184]);
	return work->contract_id;
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_backend_preflight_requests_work_project_callable_contract(BackendRequestAuthorizationRow work) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::work_project_callable_contract", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[185]);
	return work->project_callable_contract_id;
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_backend_preflight_requests_work_source_row(BackendRequestAuthorizationRow work) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::work_source_row", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[186]);
	return work->source_row_id;
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_backend_preflight_requests_work_source_reference(BackendRequestAuthorizationRow work) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::work_source_reference", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[187]);
	return work->source_reference_id;
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_backend_preflight_requests_work_target_symbol(BackendRequestAuthorizationRow work) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::work_target_symbol", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[188]);
	return work->target_symbol_id;
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_backend_preflight_requests_work_feature(BackendRequestAuthorizationRow work) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::work_feature", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[189]);
	return work->feature_id;
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_backend_preflight_requests_work_lowering_adapter(BackendRequestAuthorizationRow work) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::work_lowering_adapter", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[190]);
	return work->lowering_adapter_id;
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_backend_preflight_requests_work_step_kind(BackendRequestAuthorizationRow work) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::work_step_kind", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[191]);
	return work->lowering_step_kind_id;
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_backend_preflight_requests_work_type_ref(BackendRequestAuthorizationRow work) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::work_type_ref", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[192]);
	return work->provider_type_ref_id;
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_backend_preflight_requests_work_cache_owner_symbol(BackendRequestAuthorizationRow work) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::work_cache_owner_symbol", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[193]);
	return work->cache_owner_symbol_id;
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_backend_preflight_requests_work_status(BackendRequestAuthorizationRow work) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::work_status", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[194]);
	return work->status_id;
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_backend_preflight_requests_work_blocked_reason(BackendRequestAuthorizationRow work) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::work_blocked_reason", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[195]);
	return work->blocked_reason_id;
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_backend_preflight_requests_work_project_callable_blocked_reason(BackendRequestAuthorizationRow work) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::work_project_callable_blocked_reason", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[196]);
	return work->project_callable_blocked_reason_id;
}

}

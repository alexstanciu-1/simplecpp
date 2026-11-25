#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/ResidentFunctionBodyLogicalFrontendBodySpanDescriptorRow.hpp"
#include "__types/ResidentFunctionBodyLogicalFrontendConsumerDescriptorRow.hpp"
#include "__types/ResidentFunctionBodyLogicalFrontendReferenceCandidateRow.hpp"
#include "__types/resident_function_body_logical_frontend_consumer_descriptors.hpp"
#include "__types/resident_function_body_logical_frontend_reference_candidates.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_reference_candidates_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_reference_candidates_access_path_body_span_summary_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_reference_candidates_access_path_full_source_fallback_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_reference_candidates_status_candidate_found_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_reference_candidates_status_no_candidate_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_reference_candidates_status_blocked_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_reference_candidates_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_reference_candidates_blocked_reason_descriptor_not_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_reference_candidates_blocked_reason_span_not_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_reference_candidates_has_ready_descriptors.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_reference_candidates_access_path_body_span_summary_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_reference_candidates_blocked_row_from_span.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_reference_candidates_status_blocked_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_reference_candidates_access_path_body_span_summary_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_reference_candidates_blocked_reason_descriptor_not_ready_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_reference_candidates_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_reference_candidates_blocked_reason_span_not_ready_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_reference_candidates_blocked_row_from_span.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_reference_candidates_row_from_span.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_reference_candidates_status_candidate_found_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_reference_candidates_status_no_candidate_id.hpp"
namespace scpp { extern const int __latency_lines_resident_function_body_logical_frontend_reference_candidates[]; }
namespace scpp {
bool_t resident_function_body_logical_frontend_reference_candidates::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == resident_function_body_logical_frontend_reference_candidates::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_logical_frontend_reference_candidates[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_function_body_logical_frontend_reference_candidates_uint32_from_int(int_t<> value) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_logical_frontend_reference_candidates::uint32_from_int", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_logical_frontend_reference_candidates.phs", __latency_lines_resident_function_body_logical_frontend_reference_candidates[0]);
	return cast<int_t<std::uint32_t>>(value);
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_logical_frontend_reference_candidates[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_logical_frontend_reference_candidates_access_path_body_span_summary_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_logical_frontend_reference_candidates::access_path_body_span_summary_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_logical_frontend_reference_candidates.phs", __latency_lines_resident_function_body_logical_frontend_reference_candidates[1]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_logical_frontend_reference_candidates[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_logical_frontend_reference_candidates_access_path_full_source_fallback_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_logical_frontend_reference_candidates::access_path_full_source_fallback_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_logical_frontend_reference_candidates.phs", __latency_lines_resident_function_body_logical_frontend_reference_candidates[2]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_logical_frontend_reference_candidates[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_logical_frontend_reference_candidates_status_candidate_found_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_logical_frontend_reference_candidates::status_candidate_found_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_logical_frontend_reference_candidates.phs", __latency_lines_resident_function_body_logical_frontend_reference_candidates[3]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_logical_frontend_reference_candidates[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_logical_frontend_reference_candidates_status_no_candidate_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_logical_frontend_reference_candidates::status_no_candidate_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_logical_frontend_reference_candidates.phs", __latency_lines_resident_function_body_logical_frontend_reference_candidates[4]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_logical_frontend_reference_candidates[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_logical_frontend_reference_candidates_status_blocked_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_logical_frontend_reference_candidates::status_blocked_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_logical_frontend_reference_candidates.phs", __latency_lines_resident_function_body_logical_frontend_reference_candidates[5]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(3));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_logical_frontend_reference_candidates[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_logical_frontend_reference_candidates_blocked_reason_none_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_logical_frontend_reference_candidates::blocked_reason_none_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_logical_frontend_reference_candidates.phs", __latency_lines_resident_function_body_logical_frontend_reference_candidates[6]);
	return __latency_fn_structure_row_ids_none_kind_id();
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_logical_frontend_reference_candidates[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_logical_frontend_reference_candidates_blocked_reason_descriptor_not_ready_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_logical_frontend_reference_candidates::blocked_reason_descriptor_not_ready_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_logical_frontend_reference_candidates.phs", __latency_lines_resident_function_body_logical_frontend_reference_candidates[7]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_logical_frontend_reference_candidates[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_logical_frontend_reference_candidates_blocked_reason_span_not_ready_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_logical_frontend_reference_candidates::blocked_reason_span_not_ready_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_logical_frontend_reference_candidates.phs", __latency_lines_resident_function_body_logical_frontend_reference_candidates[8]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_logical_frontend_reference_candidates[]; }
namespace scpp {
bool_t __latency_fn_resident_function_body_logical_frontend_reference_candidates_has_ready_descriptors(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_logical_frontend_reference_candidates::has_ready_descriptors", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_logical_frontend_reference_candidates.phs", __latency_lines_resident_function_body_logical_frontend_reference_candidates[9]);
	auto __latency_local_0 = report->resident_function_body_logical_frontend_consumer_descriptors;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto descriptor = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(cast<int_t<>>(descriptor->owner_run_id), cast<int_t<>>(ownerRunId)) && php::identical(cast<int_t<>>(descriptor->status_id), cast<int_t<>>(__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_status_ready_id()))))) {
			return bool_t(static_cast<bool_t>(true));
		}
	}
	return bool_t(static_cast<bool_t>(false));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_logical_frontend_reference_candidates[]; }
namespace scpp {
ResidentFunctionBodyLogicalFrontendReferenceCandidateRow __latency_fn_resident_function_body_logical_frontend_reference_candidates_blocked_row_from_span(ResidentFunctionBodyLogicalFrontendConsumerDescriptorRow descriptor, ResidentFunctionBodyLogicalFrontendBodySpanDescriptorRow span, int_t<std::uint16_t> blockedReasonId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_logical_frontend_reference_candidates::blocked_row_from_span", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_logical_frontend_reference_candidates.phs", __latency_lines_resident_function_body_logical_frontend_reference_candidates[10]);
	ResidentFunctionBodyLogicalFrontendReferenceCandidateRow row = ResidentFunctionBodyLogicalFrontendReferenceCandidateRow{};
	row->owner_run_id = span->owner_run_id;
	row->source_unit_id = span->source_unit_id;
	row->symbol_id = span->symbol_id;
	row->logical_consumer_descriptor_id = descriptor->descriptor_id;
	row->body_span_descriptor_id = span->span_descriptor_id;
	row->candidate_node_id = span->reference_candidate_node_id;
	row->reference_candidate_count = span->reference_candidate_count;
	row->callee_start_offset = span->reference_callee_start_offset;
	row->callee_length = span->reference_callee_length;
	row->actual_arg_count = span->reference_actual_arg_count;
	row->body_node_count = span->logical_body_node_count;
	row->body_span_kind_id = span->body_span_kind_id;
	row->access_path_id = __latency_fn_resident_function_body_logical_frontend_reference_candidates_access_path_body_span_summary_id();
	row->status_id = __latency_fn_resident_function_body_logical_frontend_reference_candidates_status_blocked_id();
	row->blocked_reason_id = blockedReasonId;
	return row;
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_logical_frontend_reference_candidates[]; }
namespace scpp {
ResidentFunctionBodyLogicalFrontendReferenceCandidateRow __latency_fn_resident_function_body_logical_frontend_reference_candidates_row_from_span(ResidentFunctionBodyLogicalFrontendConsumerDescriptorRow descriptor, ResidentFunctionBodyLogicalFrontendBodySpanDescriptorRow span) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_logical_frontend_reference_candidates::row_from_span", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_logical_frontend_reference_candidates.phs", __latency_lines_resident_function_body_logical_frontend_reference_candidates[11]);
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(descriptor->status_id), cast<int_t<>>(__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_status_ready_id()))))) {
		return __latency_fn_resident_function_body_logical_frontend_reference_candidates_blocked_row_from_span(descriptor, span, __latency_fn_resident_function_body_logical_frontend_reference_candidates_blocked_reason_descriptor_not_ready_id());
	}
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(span->status_id), cast<int_t<>>(__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_status_ready_id()))))) {
		return __latency_fn_resident_function_body_logical_frontend_reference_candidates_blocked_row_from_span(descriptor, span, __latency_fn_resident_function_body_logical_frontend_reference_candidates_blocked_reason_span_not_ready_id());
	}
	ResidentFunctionBodyLogicalFrontendReferenceCandidateRow row = ResidentFunctionBodyLogicalFrontendReferenceCandidateRow{};
	row->owner_run_id = span->owner_run_id;
	row->source_unit_id = span->source_unit_id;
	row->symbol_id = span->symbol_id;
	row->logical_consumer_descriptor_id = descriptor->descriptor_id;
	row->body_span_descriptor_id = span->span_descriptor_id;
	row->candidate_node_id = span->reference_candidate_node_id;
	row->reference_candidate_count = span->reference_candidate_count;
	row->callee_start_offset = span->reference_callee_start_offset;
	row->callee_length = span->reference_callee_length;
	row->actual_arg_count = span->reference_actual_arg_count;
	row->body_node_count = span->logical_body_node_count;
	row->body_span_kind_id = span->body_span_kind_id;
	row->access_path_id = __latency_fn_resident_function_body_logical_frontend_reference_candidates_access_path_body_span_summary_id();
	row->status_id = __latency_fn_resident_function_body_logical_frontend_reference_candidates_status_candidate_found_id();
	row->blocked_reason_id = __latency_fn_resident_function_body_logical_frontend_reference_candidates_blocked_reason_none_id();
	if (static_cast<bool>((php::identical(cast<int_t<>>(row->candidate_node_id), static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(row->reference_candidate_count), static_cast<int_t<> >(0))))) {
		row->status_id = __latency_fn_resident_function_body_logical_frontend_reference_candidates_status_no_candidate_id();
	}
	return row;
}

}

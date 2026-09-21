#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/ResidentFunctionBodyRowListPublishPlanRow.hpp"
#include "__types/ResidentFunctionBodyStableNodeRemapProofRow.hpp"
#include "__types/resident_function_body_row_list_publish_plans.hpp"
#include "__types/resident_function_body_row_list_publish_results.hpp"
#include "__types/resident_function_body_stable_node_remap_proofs.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_results_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_results_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_results_status_blocked_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_results_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_results_blocked_reason_publish_plan_not_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_results_blocked_reason_missing_remap_proof_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_results_blocked_reason_missing_parse_slice_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_results_blocked_reason_missing_source_unit_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_results_blocked_reason_parser_diagnostic_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_results_blocked_reason_token_cursor_mismatch_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_results_blocked_reason_empty_replacement_body_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_results_blocked_reason_publish_failed_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_results_has_publish_plans.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_results_remap_proof_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
namespace scpp { extern const int __latency_lines_resident_function_body_row_list_publish_results[]; }
namespace scpp {
bool_t resident_function_body_row_list_publish_results::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == resident_function_body_row_list_publish_results::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_row_list_publish_results[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_function_body_row_list_publish_results_uint32_from_int(int_t<> value) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_row_list_publish_results::uint32_from_int", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_row_list_publish_results.phs", __latency_lines_resident_function_body_row_list_publish_results[0]);
	return cast<int_t<std::uint32_t>>(value);
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_row_list_publish_results[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_row_list_publish_results_status_ready_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_row_list_publish_results::status_ready_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_row_list_publish_results.phs", __latency_lines_resident_function_body_row_list_publish_results[1]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_row_list_publish_results[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_row_list_publish_results_status_blocked_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_row_list_publish_results::status_blocked_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_row_list_publish_results.phs", __latency_lines_resident_function_body_row_list_publish_results[2]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_row_list_publish_results[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_row_list_publish_results_blocked_reason_none_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_row_list_publish_results::blocked_reason_none_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_row_list_publish_results.phs", __latency_lines_resident_function_body_row_list_publish_results[3]);
	return __latency_fn_structure_row_ids_none_kind_id();
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_row_list_publish_results[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_row_list_publish_results_blocked_reason_publish_plan_not_ready_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_row_list_publish_results::blocked_reason_publish_plan_not_ready_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_row_list_publish_results.phs", __latency_lines_resident_function_body_row_list_publish_results[4]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_row_list_publish_results[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_row_list_publish_results_blocked_reason_missing_remap_proof_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_row_list_publish_results::blocked_reason_missing_remap_proof_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_row_list_publish_results.phs", __latency_lines_resident_function_body_row_list_publish_results[5]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_row_list_publish_results[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_row_list_publish_results_blocked_reason_missing_parse_slice_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_row_list_publish_results::blocked_reason_missing_parse_slice_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_row_list_publish_results.phs", __latency_lines_resident_function_body_row_list_publish_results[6]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(3));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_row_list_publish_results[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_row_list_publish_results_blocked_reason_missing_source_unit_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_row_list_publish_results::blocked_reason_missing_source_unit_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_row_list_publish_results.phs", __latency_lines_resident_function_body_row_list_publish_results[7]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(4));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_row_list_publish_results[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_row_list_publish_results_blocked_reason_parser_diagnostic_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_row_list_publish_results::blocked_reason_parser_diagnostic_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_row_list_publish_results.phs", __latency_lines_resident_function_body_row_list_publish_results[8]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(5));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_row_list_publish_results[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_row_list_publish_results_blocked_reason_token_cursor_mismatch_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_row_list_publish_results::blocked_reason_token_cursor_mismatch_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_row_list_publish_results.phs", __latency_lines_resident_function_body_row_list_publish_results[9]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(6));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_row_list_publish_results[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_row_list_publish_results_blocked_reason_empty_replacement_body_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_row_list_publish_results::blocked_reason_empty_replacement_body_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_row_list_publish_results.phs", __latency_lines_resident_function_body_row_list_publish_results[10]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(7));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_row_list_publish_results[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_row_list_publish_results_blocked_reason_publish_failed_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_row_list_publish_results::blocked_reason_publish_failed_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_row_list_publish_results.phs", __latency_lines_resident_function_body_row_list_publish_results[11]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(8));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_row_list_publish_results[]; }
namespace scpp {
bool_t __latency_fn_resident_function_body_row_list_publish_results_has_publish_plans(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_row_list_publish_results::has_publish_plans", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_row_list_publish_results.phs", __latency_lines_resident_function_body_row_list_publish_results[12]);
	auto __latency_local_0 = report->resident_function_body_row_list_publish_plans;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto plan = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(plan->owner_run_id), cast<int_t<>>(ownerRunId)))) {
			return bool_t(static_cast<bool_t>(true));
		}
	}
	return bool_t(static_cast<bool_t>(false));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_row_list_publish_results[]; }
namespace scpp {
ResidentFunctionBodyStableNodeRemapProofRow __latency_fn_resident_function_body_row_list_publish_results_remap_proof_by_id(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> proofId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_row_list_publish_results::remap_proof_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_row_list_publish_results.phs", __latency_lines_resident_function_body_row_list_publish_results[13]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(proofId, php::count(report->resident_function_body_stable_node_remap_proofs))))) {
		ResidentFunctionBodyStableNodeRemapProofRow proof = report->resident_function_body_stable_node_remap_proofs[__latency_fn_structure_row_ids_dense_index(proofId)];
		if (static_cast<bool>(php::identical(cast<int_t<>>(proof->remap_proof_id), cast<int_t<>>(proofId)))) {
			return proof;
		}
	}
	auto __latency_local_0 = report->resident_function_body_stable_node_remap_proofs;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto proof = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(proof->remap_proof_id), cast<int_t<>>(proofId)))) {
			return proof;
		}
	}
	ResidentFunctionBodyStableNodeRemapProofRow empty = ResidentFunctionBodyStableNodeRemapProofRow{};
	return empty;
}

}

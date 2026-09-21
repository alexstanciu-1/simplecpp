#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/ResidentFunctionBodyMixedFrontendAssemblyProofRow.hpp"
#include "__types/ResidentFunctionBodyRowListPublishResultRow.hpp"
#include "__types/resident_function_body_mixed_frontend_assembly_proofs.hpp"
#include "__types/resident_function_body_row_list_publish_results.hpp"
#include "__callable/__latency_fn_resident_function_body_mixed_frontend_assembly_proofs_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_mixed_frontend_assembly_proofs_assembly_kind_replacement_only_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_mixed_frontend_assembly_proofs_assembly_kind_mixed_reuse_and_replacement_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_mixed_frontend_assembly_proofs_assembly_kind_blocked_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_mixed_frontend_assembly_proofs_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_mixed_frontend_assembly_proofs_status_blocked_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_mixed_frontend_assembly_proofs_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_resident_function_body_mixed_frontend_assembly_proofs_blocked_reason_no_publish_result_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_mixed_frontend_assembly_proofs_blocked_reason_missing_publish_result_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_mixed_frontend_assembly_proofs_blocked_reason_missing_current_frontend_list_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_mixed_frontend_assembly_proofs_blocked_reason_empty_assembled_body_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_mixed_frontend_assembly_proofs_has_ready_publish_results.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_results_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_function_body_mixed_frontend_assembly_proofs_has_source_unit_proof.hpp"
#include "__callable/__latency_fn_resident_function_body_mixed_frontend_assembly_proofs_publish_result_by_decision.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_results_status_ready_id.hpp"
namespace scpp { extern const int __latency_lines_resident_function_body_mixed_frontend_assembly_proofs[]; }
namespace scpp {
bool_t resident_function_body_mixed_frontend_assembly_proofs::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == resident_function_body_mixed_frontend_assembly_proofs::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_mixed_frontend_assembly_proofs[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_function_body_mixed_frontend_assembly_proofs_uint32_from_int(int_t<> value) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_mixed_frontend_assembly_proofs::uint32_from_int", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_mixed_frontend_assembly_proofs.phs", __latency_lines_resident_function_body_mixed_frontend_assembly_proofs[0]);
	return cast<int_t<std::uint32_t>>(value);
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_mixed_frontend_assembly_proofs[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_mixed_frontend_assembly_proofs_assembly_kind_replacement_only_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_mixed_frontend_assembly_proofs::assembly_kind_replacement_only_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_mixed_frontend_assembly_proofs.phs", __latency_lines_resident_function_body_mixed_frontend_assembly_proofs[1]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_mixed_frontend_assembly_proofs[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_mixed_frontend_assembly_proofs_assembly_kind_mixed_reuse_and_replacement_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_mixed_frontend_assembly_proofs::assembly_kind_mixed_reuse_and_replacement_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_mixed_frontend_assembly_proofs.phs", __latency_lines_resident_function_body_mixed_frontend_assembly_proofs[2]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_mixed_frontend_assembly_proofs[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_mixed_frontend_assembly_proofs_assembly_kind_blocked_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_mixed_frontend_assembly_proofs::assembly_kind_blocked_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_mixed_frontend_assembly_proofs.phs", __latency_lines_resident_function_body_mixed_frontend_assembly_proofs[3]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(3));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_mixed_frontend_assembly_proofs[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_mixed_frontend_assembly_proofs_status_ready_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_mixed_frontend_assembly_proofs::status_ready_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_mixed_frontend_assembly_proofs.phs", __latency_lines_resident_function_body_mixed_frontend_assembly_proofs[4]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_mixed_frontend_assembly_proofs[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_mixed_frontend_assembly_proofs_status_blocked_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_mixed_frontend_assembly_proofs::status_blocked_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_mixed_frontend_assembly_proofs.phs", __latency_lines_resident_function_body_mixed_frontend_assembly_proofs[5]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_mixed_frontend_assembly_proofs[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_mixed_frontend_assembly_proofs_blocked_reason_none_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_mixed_frontend_assembly_proofs::blocked_reason_none_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_mixed_frontend_assembly_proofs.phs", __latency_lines_resident_function_body_mixed_frontend_assembly_proofs[6]);
	return __latency_fn_structure_row_ids_none_kind_id();
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_mixed_frontend_assembly_proofs[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_mixed_frontend_assembly_proofs_blocked_reason_no_publish_result_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_mixed_frontend_assembly_proofs::blocked_reason_no_publish_result_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_mixed_frontend_assembly_proofs.phs", __latency_lines_resident_function_body_mixed_frontend_assembly_proofs[7]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_mixed_frontend_assembly_proofs[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_mixed_frontend_assembly_proofs_blocked_reason_missing_publish_result_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_mixed_frontend_assembly_proofs::blocked_reason_missing_publish_result_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_mixed_frontend_assembly_proofs.phs", __latency_lines_resident_function_body_mixed_frontend_assembly_proofs[8]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_mixed_frontend_assembly_proofs[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_mixed_frontend_assembly_proofs_blocked_reason_missing_current_frontend_list_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_mixed_frontend_assembly_proofs::blocked_reason_missing_current_frontend_list_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_mixed_frontend_assembly_proofs.phs", __latency_lines_resident_function_body_mixed_frontend_assembly_proofs[9]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(3));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_mixed_frontend_assembly_proofs[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_mixed_frontend_assembly_proofs_blocked_reason_empty_assembled_body_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_mixed_frontend_assembly_proofs::blocked_reason_empty_assembled_body_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_mixed_frontend_assembly_proofs.phs", __latency_lines_resident_function_body_mixed_frontend_assembly_proofs[10]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(4));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_mixed_frontend_assembly_proofs[]; }
namespace scpp {
bool_t __latency_fn_resident_function_body_mixed_frontend_assembly_proofs_has_ready_publish_results(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_mixed_frontend_assembly_proofs::has_ready_publish_results", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_mixed_frontend_assembly_proofs.phs", __latency_lines_resident_function_body_mixed_frontend_assembly_proofs[11]);
	auto __latency_local_0 = report->resident_function_body_row_list_publish_results;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto result = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(cast<int_t<>>(result->owner_run_id), cast<int_t<>>(ownerRunId)) && php::identical(cast<int_t<>>(result->status_id), cast<int_t<>>(__latency_fn_resident_function_body_row_list_publish_results_status_ready_id()))))) {
			return bool_t(static_cast<bool_t>(true));
		}
	}
	return bool_t(static_cast<bool_t>(false));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_mixed_frontend_assembly_proofs[]; }
namespace scpp {
bool_t __latency_fn_resident_function_body_mixed_frontend_assembly_proofs_has_source_unit_proof(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> sourceUnitId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_mixed_frontend_assembly_proofs::has_source_unit_proof", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_mixed_frontend_assembly_proofs.phs", __latency_lines_resident_function_body_mixed_frontend_assembly_proofs[12]);
	auto __latency_local_0 = report->resident_function_body_mixed_frontend_assembly_proofs;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(cast<int_t<>>(row->owner_run_id), cast<int_t<>>(ownerRunId)) && php::identical(cast<int_t<>>(row->source_unit_id), cast<int_t<>>(sourceUnitId))))) {
			return bool_t(static_cast<bool_t>(true));
		}
	}
	return bool_t(static_cast<bool_t>(false));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_mixed_frontend_assembly_proofs[]; }
namespace scpp {
ResidentFunctionBodyRowListPublishResultRow __latency_fn_resident_function_body_mixed_frontend_assembly_proofs_publish_result_by_decision(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> decisionId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_mixed_frontend_assembly_proofs::publish_result_by_decision", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_mixed_frontend_assembly_proofs.phs", __latency_lines_resident_function_body_mixed_frontend_assembly_proofs[13]);
	auto __latency_local_0 = report->resident_function_body_row_list_publish_results;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto result = __latency_local_1.value_copy();
		if (static_cast<bool>(((php::identical(cast<int_t<>>(result->owner_run_id), cast<int_t<>>(ownerRunId)) && php::identical(cast<int_t<>>(result->function_body_work_decision_id), cast<int_t<>>(decisionId))) && php::identical(cast<int_t<>>(result->status_id), cast<int_t<>>(__latency_fn_resident_function_body_row_list_publish_results_status_ready_id()))))) {
			return result;
		}
	}
	ResidentFunctionBodyRowListPublishResultRow empty = ResidentFunctionBodyRowListPublishResultRow{};
	return empty;
}

}

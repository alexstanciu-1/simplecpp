#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/ResidentFunctionBodyLocalParseProofRow.hpp"
#include "__types/ResidentFunctionBodyPublishRepointPreflightRow.hpp"
#include "__types/resident_function_body_local_parse_proofs.hpp"
#include "__types/resident_function_body_publish_repoint_preflights.hpp"
#include "__callable/__latency_fn_resident_function_body_publish_repoint_preflights_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_publish_repoint_preflights_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_publish_repoint_preflights_status_blocked_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_publish_repoint_preflights_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_resident_function_body_publish_repoint_preflights_blocked_reason_local_parse_not_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_publish_repoint_preflights_blocked_reason_parser_diagnostic_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_publish_repoint_preflights_blocked_reason_token_cursor_mismatch_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_publish_repoint_preflights_blocked_reason_missing_body_snapshot_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_publish_repoint_preflights_blocked_reason_missing_owner_identity_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_publish_repoint_preflights_publish_strategy_proof_only_deferred_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_publish_repoint_preflights_required_remap_stable_body_node_ids_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_publish_repoint_preflights_has_local_parse_proofs.hpp"
#include "__callable/__latency_fn_resident_function_body_local_parse_proofs_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_function_body_publish_repoint_preflights_blocked_reason_local_parse_not_ready_id.hpp"
#include "__callable/__latency_fn_resident_function_body_publish_repoint_preflights_blocked_reason_missing_body_snapshot_id.hpp"
#include "__callable/__latency_fn_resident_function_body_publish_repoint_preflights_blocked_reason_missing_owner_identity_id.hpp"
#include "__callable/__latency_fn_resident_function_body_publish_repoint_preflights_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_resident_function_body_publish_repoint_preflights_blocked_reason_parser_diagnostic_id.hpp"
#include "__callable/__latency_fn_resident_function_body_publish_repoint_preflights_blocked_reason_token_cursor_mismatch_id.hpp"
#include "__callable/__latency_fn_resident_function_body_publish_repoint_preflights_publish_strategy_proof_only_deferred_id.hpp"
#include "__callable/__latency_fn_resident_function_body_publish_repoint_preflights_required_remap_stable_body_node_ids_id.hpp"
#include "__callable/__latency_fn_resident_function_body_publish_repoint_preflights_row_from_local_parse_proof.hpp"
#include "__callable/__latency_fn_resident_function_body_publish_repoint_preflights_status_blocked_id.hpp"
#include "__callable/__latency_fn_resident_function_body_publish_repoint_preflights_status_ready_id.hpp"
namespace scpp { extern const int __latency_lines_resident_function_body_publish_repoint_preflights[]; }
namespace scpp {
bool_t resident_function_body_publish_repoint_preflights::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == resident_function_body_publish_repoint_preflights::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_publish_repoint_preflights[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_function_body_publish_repoint_preflights_uint32_from_int(int_t<> value) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_publish_repoint_preflights::uint32_from_int", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_publish_repoint_preflights.phs", __latency_lines_resident_function_body_publish_repoint_preflights[0]);
	return cast<int_t<std::uint32_t>>(value);
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_publish_repoint_preflights[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_publish_repoint_preflights_status_ready_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_publish_repoint_preflights::status_ready_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_publish_repoint_preflights.phs", __latency_lines_resident_function_body_publish_repoint_preflights[1]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_publish_repoint_preflights[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_publish_repoint_preflights_status_blocked_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_publish_repoint_preflights::status_blocked_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_publish_repoint_preflights.phs", __latency_lines_resident_function_body_publish_repoint_preflights[2]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_publish_repoint_preflights[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_publish_repoint_preflights_blocked_reason_none_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_publish_repoint_preflights::blocked_reason_none_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_publish_repoint_preflights.phs", __latency_lines_resident_function_body_publish_repoint_preflights[3]);
	return __latency_fn_structure_row_ids_none_kind_id();
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_publish_repoint_preflights[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_publish_repoint_preflights_blocked_reason_local_parse_not_ready_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_publish_repoint_preflights::blocked_reason_local_parse_not_ready_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_publish_repoint_preflights.phs", __latency_lines_resident_function_body_publish_repoint_preflights[4]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_publish_repoint_preflights[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_publish_repoint_preflights_blocked_reason_parser_diagnostic_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_publish_repoint_preflights::blocked_reason_parser_diagnostic_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_publish_repoint_preflights.phs", __latency_lines_resident_function_body_publish_repoint_preflights[5]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_publish_repoint_preflights[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_publish_repoint_preflights_blocked_reason_token_cursor_mismatch_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_publish_repoint_preflights::blocked_reason_token_cursor_mismatch_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_publish_repoint_preflights.phs", __latency_lines_resident_function_body_publish_repoint_preflights[6]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(3));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_publish_repoint_preflights[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_publish_repoint_preflights_blocked_reason_missing_body_snapshot_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_publish_repoint_preflights::blocked_reason_missing_body_snapshot_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_publish_repoint_preflights.phs", __latency_lines_resident_function_body_publish_repoint_preflights[7]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(4));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_publish_repoint_preflights[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_publish_repoint_preflights_blocked_reason_missing_owner_identity_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_publish_repoint_preflights::blocked_reason_missing_owner_identity_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_publish_repoint_preflights.phs", __latency_lines_resident_function_body_publish_repoint_preflights[8]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(5));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_publish_repoint_preflights[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_publish_repoint_preflights_publish_strategy_proof_only_deferred_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_publish_repoint_preflights::publish_strategy_proof_only_deferred_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_publish_repoint_preflights.phs", __latency_lines_resident_function_body_publish_repoint_preflights[9]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_publish_repoint_preflights[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_publish_repoint_preflights_required_remap_stable_body_node_ids_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_publish_repoint_preflights::required_remap_stable_body_node_ids_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_publish_repoint_preflights.phs", __latency_lines_resident_function_body_publish_repoint_preflights[10]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_publish_repoint_preflights[]; }
namespace scpp {
bool_t __latency_fn_resident_function_body_publish_repoint_preflights_has_local_parse_proofs(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_publish_repoint_preflights::has_local_parse_proofs", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_publish_repoint_preflights.phs", __latency_lines_resident_function_body_publish_repoint_preflights[11]);
	auto __latency_local_0 = report->resident_function_body_local_parse_proofs;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto proof = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(proof->owner_run_id), cast<int_t<>>(ownerRunId)))) {
			return bool_t(static_cast<bool_t>(true));
		}
	}
	return bool_t(static_cast<bool_t>(false));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_publish_repoint_preflights[]; }
namespace scpp {
ResidentFunctionBodyPublishRepointPreflightRow __latency_fn_resident_function_body_publish_repoint_preflights_row_from_local_parse_proof(ResidentFunctionBodyLocalParseProofRow proof) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_publish_repoint_preflights::row_from_local_parse_proof", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_publish_repoint_preflights.phs", __latency_lines_resident_function_body_publish_repoint_preflights[12]);
	ResidentFunctionBodyPublishRepointPreflightRow row = ResidentFunctionBodyPublishRepointPreflightRow{};
	row->owner_run_id = proof->owner_run_id;
	row->local_parse_proof_id = proof->proof_id;
	row->parse_slice_id = proof->parse_slice_id;
	row->function_body_work_decision_id = proof->function_body_work_decision_id;
	row->current_function_body_snapshot_id = proof->current_function_body_snapshot_id;
	row->source_unit_id = proof->source_unit_id;
	row->symbol_id = proof->symbol_id;
	row->body_start_offset = proof->body_start_offset;
	row->body_end_offset = proof->body_end_offset;
	row->local_frontend_node_count = proof->local_frontend_node_count;
	row->local_statement_count = proof->local_statement_count;
	row->local_expression_count = proof->local_expression_count;
	row->full_source_body_node_count = proof->full_source_body_node_count;
	row->expected_token_after_body_index = proof->expected_token_after_body_index;
	row->actual_token_after_body_index = proof->actual_token_after_body_index;
	row->publish_strategy_id = __latency_fn_resident_function_body_publish_repoint_preflights_publish_strategy_proof_only_deferred_id();
	row->required_remap_kind_id = __latency_fn_resident_function_body_publish_repoint_preflights_required_remap_stable_body_node_ids_id();
	row->status_id = __latency_fn_resident_function_body_publish_repoint_preflights_status_ready_id();
	row->blocked_reason_id = __latency_fn_resident_function_body_publish_repoint_preflights_blocked_reason_none_id();
	if (static_cast<bool>((cast<int_t<>>(proof->parser_diagnostic_count) > static_cast<int_t<> >(0)))) {
		row->status_id = __latency_fn_resident_function_body_publish_repoint_preflights_status_blocked_id();
		row->blocked_reason_id = __latency_fn_resident_function_body_publish_repoint_preflights_blocked_reason_parser_diagnostic_id();
	}
	else {
		if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(proof->actual_token_after_body_index), cast<int_t<>>(proof->expected_token_after_body_index))))) {
			row->status_id = __latency_fn_resident_function_body_publish_repoint_preflights_status_blocked_id();
			row->blocked_reason_id = __latency_fn_resident_function_body_publish_repoint_preflights_blocked_reason_token_cursor_mismatch_id();
		}
		else {
			if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(proof->status_id), cast<int_t<>>(__latency_fn_resident_function_body_local_parse_proofs_status_ready_id()))))) {
				row->status_id = __latency_fn_resident_function_body_publish_repoint_preflights_status_blocked_id();
				row->blocked_reason_id = __latency_fn_resident_function_body_publish_repoint_preflights_blocked_reason_local_parse_not_ready_id();
			}
			else {
				if (static_cast<bool>(php::identical(cast<int_t<>>(proof->current_function_body_snapshot_id), static_cast<int_t<> >(0)))) {
					row->status_id = __latency_fn_resident_function_body_publish_repoint_preflights_status_blocked_id();
					row->blocked_reason_id = __latency_fn_resident_function_body_publish_repoint_preflights_blocked_reason_missing_body_snapshot_id();
				}
				else {
					if (static_cast<bool>((php::identical(cast<int_t<>>(proof->source_unit_id), static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(proof->symbol_id), static_cast<int_t<> >(0))))) {
						row->status_id = __latency_fn_resident_function_body_publish_repoint_preflights_status_blocked_id();
						row->blocked_reason_id = __latency_fn_resident_function_body_publish_repoint_preflights_blocked_reason_missing_owner_identity_id();
					}
				}
			}
		}
	}
	return row;
}

}

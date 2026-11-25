#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/ResidentFrontendNodeListPublishRow.hpp"
#include "__types/ResidentFunctionBodyChangeRow.hpp"
#include "__types/ResidentFunctionBodyWorkDecisionRow.hpp"
#include "__types/ResidentRecomputeTargetRow.hpp"
#include "__types/ResidentReverseDependencyLookupRow.hpp"
#include "__types/ResidentSymbolDefinitionChangeRow.hpp"
#include "__types/ResidentTokenListPublishRow.hpp"
#include "__types/resident_function_body_work_decisions.hpp"
#include "__types/resident_recompute_targets.hpp"
#include "__callable/__latency_fn_resident_public_surface_incremental_acceptance_blocked_reason_dirty_bailout_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_public_surface_incremental_acceptance_function_body_change_by_symbol_change.hpp"
#include "__callable/__latency_fn_resident_public_surface_incremental_acceptance_work_decision_by_function_body_change.hpp"
#include "__callable/__latency_fn_resident_public_surface_incremental_acceptance_selected_source_reparse_count.hpp"
#include "__callable/__latency_fn_resident_public_surface_incremental_acceptance_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_recompute_targets_status_selected_id.hpp"
#include "__callable/__latency_fn_resident_recompute_targets_target_source_unit_reparse_id.hpp"
#include "__callable/__latency_fn_resident_public_surface_incremental_acceptance_selected_symbol_target_count.hpp"
#include "__callable/__latency_fn_resident_public_surface_incremental_acceptance_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_recompute_targets_status_selected_id.hpp"
#include "__callable/__latency_fn_resident_public_surface_incremental_acceptance_selected_dependent_resolution_count.hpp"
#include "__callable/__latency_fn_resident_public_surface_incremental_acceptance_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_recompute_targets_status_selected_id.hpp"
#include "__callable/__latency_fn_resident_recompute_targets_target_dependent_resolution_id.hpp"
#include "__callable/__latency_fn_resident_public_surface_incremental_acceptance_reverse_lookup_count.hpp"
#include "__callable/__latency_fn_resident_public_surface_incremental_acceptance_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_reverse_dependencies_lookup_belongs_to_change.hpp"
#include "__callable/__latency_fn_resident_public_surface_incremental_acceptance_reverse_lookup_total_count.hpp"
#include "__callable/__latency_fn_resident_public_surface_incremental_acceptance_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_reverse_dependencies_lookup_belongs_to_change.hpp"
#include "__callable/__latency_fn_resident_public_surface_incremental_acceptance_token_list_publish_count.hpp"
#include "__callable/__latency_fn_resident_public_surface_incremental_acceptance_uint32_from_int.hpp"
#include "__callable/__latency_fn_row_segment_policy_publish_status_ok_id.hpp"
#include "__callable/__latency_fn_resident_public_surface_incremental_acceptance_frontend_node_list_publish_count.hpp"
#include "__callable/__latency_fn_resident_public_surface_incremental_acceptance_uint32_from_int.hpp"
#include "__callable/__latency_fn_row_segment_policy_publish_status_ok_id.hpp"
namespace scpp { extern const int __latency_lines_resident_public_surface_incremental_acceptance[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_public_surface_incremental_acceptance_blocked_reason_dirty_bailout_id() {
	SCPP_CALL_DEPTH_GUARD("resident_public_surface_incremental_acceptance::blocked_reason_dirty_bailout_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_public_surface_incremental_acceptance.phs", __latency_lines_resident_public_surface_incremental_acceptance[16]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(11));
}

}

namespace scpp { extern const int __latency_lines_resident_public_surface_incremental_acceptance[]; }
namespace scpp {
ResidentFunctionBodyChangeRow __latency_fn_resident_public_surface_incremental_acceptance_function_body_change_by_symbol_change(shared_p<CompilerProjectRunReport> report, ResidentSymbolDefinitionChangeRow change) {
	SCPP_CALL_DEPTH_GUARD("resident_public_surface_incremental_acceptance::function_body_change_by_symbol_change", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_public_surface_incremental_acceptance.phs", __latency_lines_resident_public_surface_incremental_acceptance[17]);
	auto __latency_local_0 = report->resident_function_body_changes;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(cast<int_t<>>(row->owner_run_id), cast<int_t<>>(change->owner_run_id)) && php::identical(cast<int_t<>>(row->symbol_definition_change_id), cast<int_t<>>(change->change_id))))) {
			return row;
		}
	}
	ResidentFunctionBodyChangeRow empty = ResidentFunctionBodyChangeRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_resident_public_surface_incremental_acceptance[]; }
namespace scpp {
ResidentFunctionBodyWorkDecisionRow __latency_fn_resident_public_surface_incremental_acceptance_work_decision_by_function_body_change(shared_p<CompilerProjectRunReport> report, ResidentFunctionBodyChangeRow change) {
	SCPP_CALL_DEPTH_GUARD("resident_public_surface_incremental_acceptance::work_decision_by_function_body_change", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_public_surface_incremental_acceptance.phs", __latency_lines_resident_public_surface_incremental_acceptance[18]);
	auto __latency_local_0 = report->resident_function_body_work_decisions;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(cast<int_t<>>(row->owner_run_id), cast<int_t<>>(change->owner_run_id)) && php::identical(cast<int_t<>>(row->function_body_change_id), cast<int_t<>>(change->change_id))))) {
			return row;
		}
	}
	ResidentFunctionBodyWorkDecisionRow empty = ResidentFunctionBodyWorkDecisionRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_resident_public_surface_incremental_acceptance[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_public_surface_incremental_acceptance_selected_source_reparse_count(shared_p<CompilerProjectRunReport> report, ResidentSymbolDefinitionChangeRow change) {
	SCPP_CALL_DEPTH_GUARD("resident_public_surface_incremental_acceptance::selected_source_reparse_count", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_public_surface_incremental_acceptance.phs", __latency_lines_resident_public_surface_incremental_acceptance[19]);
	int_t<> count = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = report->resident_recompute_targets;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto target = __latency_local_1.value_copy();
		if (static_cast<bool>((((php::identical(cast<int_t<>>(target->owner_run_id), cast<int_t<>>(change->owner_run_id)) && php::identical(cast<int_t<>>(target->source_unit_id), cast<int_t<>>(change->source_unit_id))) && php::identical(cast<int_t<>>(target->target_kind_id), cast<int_t<>>(__latency_fn_resident_recompute_targets_target_source_unit_reparse_id()))) && php::identical(cast<int_t<>>(target->status_id), cast<int_t<>>(__latency_fn_resident_recompute_targets_status_selected_id()))))) {
			count = (count + static_cast<int_t<> >(1));
		}
	}
	return __latency_fn_resident_public_surface_incremental_acceptance_uint32_from_int(count);
}

}

namespace scpp { extern const int __latency_lines_resident_public_surface_incremental_acceptance[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_public_surface_incremental_acceptance_selected_symbol_target_count(shared_p<CompilerProjectRunReport> report, ResidentSymbolDefinitionChangeRow change, int_t<std::uint16_t> targetKindId) {
	SCPP_CALL_DEPTH_GUARD("resident_public_surface_incremental_acceptance::selected_symbol_target_count", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_public_surface_incremental_acceptance.phs", __latency_lines_resident_public_surface_incremental_acceptance[20]);
	int_t<> count = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = report->resident_recompute_targets;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto target = __latency_local_1.value_copy();
		if (static_cast<bool>((((((php::identical(cast<int_t<>>(target->owner_run_id), cast<int_t<>>(change->owner_run_id)) && php::identical(cast<int_t<>>(target->source_unit_id), cast<int_t<>>(change->source_unit_id))) && php::identical(cast<int_t<>>(target->symbol_id), cast<int_t<>>(change->symbol_id))) && php::identical(cast<int_t<>>(target->reason_change_id), cast<int_t<>>(change->change_id))) && php::identical(cast<int_t<>>(target->target_kind_id), cast<int_t<>>(targetKindId))) && php::identical(cast<int_t<>>(target->status_id), cast<int_t<>>(__latency_fn_resident_recompute_targets_status_selected_id()))))) {
			count = (count + static_cast<int_t<> >(1));
		}
	}
	return __latency_fn_resident_public_surface_incremental_acceptance_uint32_from_int(count);
}

}

namespace scpp { extern const int __latency_lines_resident_public_surface_incremental_acceptance[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_public_surface_incremental_acceptance_selected_dependent_resolution_count(shared_p<CompilerProjectRunReport> report, ResidentSymbolDefinitionChangeRow change) {
	SCPP_CALL_DEPTH_GUARD("resident_public_surface_incremental_acceptance::selected_dependent_resolution_count", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_public_surface_incremental_acceptance.phs", __latency_lines_resident_public_surface_incremental_acceptance[21]);
	int_t<> count = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = report->resident_recompute_targets;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto target = __latency_local_1.value_copy();
		if (static_cast<bool>((((php::identical(cast<int_t<>>(target->owner_run_id), cast<int_t<>>(change->owner_run_id)) && php::identical(cast<int_t<>>(target->reason_change_id), cast<int_t<>>(change->change_id))) && php::identical(cast<int_t<>>(target->target_kind_id), cast<int_t<>>(__latency_fn_resident_recompute_targets_target_dependent_resolution_id()))) && php::identical(cast<int_t<>>(target->status_id), cast<int_t<>>(__latency_fn_resident_recompute_targets_status_selected_id()))))) {
			count = (count + static_cast<int_t<> >(1));
		}
	}
	return __latency_fn_resident_public_surface_incremental_acceptance_uint32_from_int(count);
}

}

namespace scpp { extern const int __latency_lines_resident_public_surface_incremental_acceptance[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_public_surface_incremental_acceptance_reverse_lookup_count(shared_p<CompilerProjectRunReport> report, ResidentSymbolDefinitionChangeRow change, int_t<std::uint16_t> statusId) {
	SCPP_CALL_DEPTH_GUARD("resident_public_surface_incremental_acceptance::reverse_lookup_count", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_public_surface_incremental_acceptance.phs", __latency_lines_resident_public_surface_incremental_acceptance[22]);
	int_t<> count = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = report->resident_reverse_dependency_lookups;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto lookup = __latency_local_1.value_copy();
		if (static_cast<bool>((__latency_fn_resident_reverse_dependencies_lookup_belongs_to_change(lookup, change) && php::identical(cast<int_t<>>(lookup->status_id), cast<int_t<>>(statusId))))) {
			count = (count + static_cast<int_t<> >(1));
		}
	}
	return __latency_fn_resident_public_surface_incremental_acceptance_uint32_from_int(count);
}

}

namespace scpp { extern const int __latency_lines_resident_public_surface_incremental_acceptance[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_public_surface_incremental_acceptance_reverse_lookup_total_count(shared_p<CompilerProjectRunReport> report, ResidentSymbolDefinitionChangeRow change) {
	SCPP_CALL_DEPTH_GUARD("resident_public_surface_incremental_acceptance::reverse_lookup_total_count", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_public_surface_incremental_acceptance.phs", __latency_lines_resident_public_surface_incremental_acceptance[23]);
	int_t<> count = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = report->resident_reverse_dependency_lookups;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto lookup = __latency_local_1.value_copy();
		if (static_cast<bool>(php::condition_truthy(__latency_fn_resident_reverse_dependencies_lookup_belongs_to_change(lookup, change)))) {
			count = (count + static_cast<int_t<> >(1));
		}
	}
	return __latency_fn_resident_public_surface_incremental_acceptance_uint32_from_int(count);
}

}

namespace scpp { extern const int __latency_lines_resident_public_surface_incremental_acceptance[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_public_surface_incremental_acceptance_token_list_publish_count(shared_p<CompilerProjectRunReport> report, ResidentSymbolDefinitionChangeRow change) {
	SCPP_CALL_DEPTH_GUARD("resident_public_surface_incremental_acceptance::token_list_publish_count", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_public_surface_incremental_acceptance.phs", __latency_lines_resident_public_surface_incremental_acceptance[24]);
	int_t<> count = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = report->resident_token_list_publishes;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(((php::identical(cast<int_t<>>(row->owner_run_id), cast<int_t<>>(change->owner_run_id)) && php::identical(cast<int_t<>>(row->source_unit_id), cast<int_t<>>(change->source_unit_id))) && php::identical(cast<int_t<>>(row->publish_status_id), cast<int_t<>>(__latency_fn_row_segment_policy_publish_status_ok_id()))))) {
			count = (count + static_cast<int_t<> >(1));
		}
	}
	return __latency_fn_resident_public_surface_incremental_acceptance_uint32_from_int(count);
}

}

namespace scpp { extern const int __latency_lines_resident_public_surface_incremental_acceptance[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_public_surface_incremental_acceptance_frontend_node_list_publish_count(shared_p<CompilerProjectRunReport> report, ResidentSymbolDefinitionChangeRow change) {
	SCPP_CALL_DEPTH_GUARD("resident_public_surface_incremental_acceptance::frontend_node_list_publish_count", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_public_surface_incremental_acceptance.phs", __latency_lines_resident_public_surface_incremental_acceptance[25]);
	int_t<> count = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = report->resident_frontend_node_list_publishes;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(((php::identical(cast<int_t<>>(row->owner_run_id), cast<int_t<>>(change->owner_run_id)) && php::identical(cast<int_t<>>(row->source_unit_id), cast<int_t<>>(change->source_unit_id))) && php::identical(cast<int_t<>>(row->publish_status_id), cast<int_t<>>(__latency_fn_row_segment_policy_publish_status_ok_id()))))) {
			count = (count + static_cast<int_t<> >(1));
		}
	}
	return __latency_fn_resident_public_surface_incremental_acceptance_uint32_from_int(count);
}

}

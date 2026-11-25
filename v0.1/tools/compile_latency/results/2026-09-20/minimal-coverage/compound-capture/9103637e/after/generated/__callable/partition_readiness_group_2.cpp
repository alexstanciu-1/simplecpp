#include <scpp/lang/php.hpp>
#include "__types/PartitionReadinessRow.hpp"
#include "__types/ResidentSourceUnitSnapshotRow.hpp"
#include "__types/ResidentSymbolDefinitionSnapshotRow.hpp"
#include "__callable/__latency_fn_partition_readiness_merge_order_key_for_row.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint64_from_int.hpp"
#include "__callable/__latency_fn_partition_readiness_complete_publication_fields.hpp"
#include "__callable/__latency_fn_partition_readiness_merge_order_key_for_row.hpp"
#include "__callable/__latency_fn_partition_readiness_publication_model_main_thread_coordinator_id.hpp"
#include "__callable/__latency_fn_partition_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_partition_readiness_blocked_reason_missing_source_owner_id.hpp"
#include "__callable/__latency_fn_partition_readiness_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_partition_readiness_owner_kind_source_unit_id.hpp"
#include "__callable/__latency_fn_partition_readiness_row.hpp"
#include "__callable/__latency_fn_partition_readiness_source_owner_row.hpp"
#include "__callable/__latency_fn_partition_readiness_status_blocked_id.hpp"
#include "__callable/__latency_fn_partition_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_partition_readiness_blocked_reason_missing_symbol_body_owner_id.hpp"
#include "__callable/__latency_fn_partition_readiness_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_partition_readiness_owner_kind_symbol_body_id.hpp"
#include "__callable/__latency_fn_partition_readiness_row.hpp"
#include "__callable/__latency_fn_partition_readiness_status_blocked_id.hpp"
#include "__callable/__latency_fn_partition_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_partition_readiness_symbol_body_owner_row.hpp"
#include "__callable/__latency_fn_partition_readiness_owner_kind_backend_id.hpp"
#include "__callable/__latency_fn_partition_readiness_owner_kind_lowering_id.hpp"
#include "__callable/__latency_fn_partition_readiness_owner_kind_name.hpp"
#include "__callable/__latency_fn_partition_readiness_owner_kind_object_id.hpp"
#include "__callable/__latency_fn_partition_readiness_owner_kind_source_unit_id.hpp"
#include "__callable/__latency_fn_partition_readiness_owner_kind_symbol_body_id.hpp"
#include "__callable/__latency_fn_partition_readiness_status_blocked_id.hpp"
#include "__callable/__latency_fn_partition_readiness_status_name.hpp"
#include "__callable/__latency_fn_partition_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_partition_readiness_blocked_reason_backend_partition_blocked_id.hpp"
#include "__callable/__latency_fn_partition_readiness_blocked_reason_capability_readiness_worker_failed_id.hpp"
#include "__callable/__latency_fn_partition_readiness_blocked_reason_coordinator_owned_link_boundary_id.hpp"
#include "__callable/__latency_fn_partition_readiness_blocked_reason_llvm_text_emission_worker_failed_id.hpp"
#include "__callable/__latency_fn_partition_readiness_blocked_reason_lowering_backend_request_worker_failed_id.hpp"
#include "__callable/__latency_fn_partition_readiness_blocked_reason_lowering_plan_blocked_id.hpp"
#include "__callable/__latency_fn_partition_readiness_blocked_reason_missing_backend_owner_id.hpp"
#include "__callable/__latency_fn_partition_readiness_blocked_reason_missing_lowering_owner_id.hpp"
#include "__callable/__latency_fn_partition_readiness_blocked_reason_missing_source_owner_id.hpp"
#include "__callable/__latency_fn_partition_readiness_blocked_reason_missing_symbol_body_owner_id.hpp"
#include "__callable/__latency_fn_partition_readiness_blocked_reason_name.hpp"
#include "__callable/__latency_fn_partition_readiness_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_partition_readiness_blocked_reason_object_execution_deferred_id.hpp"
#include "__callable/__latency_fn_partition_readiness_blocked_reason_object_output_worker_failed_id.hpp"
#include "__callable/__latency_fn_partition_readiness_blocked_reason_parser_frontend_worker_failed_id.hpp"
#include "__callable/__latency_fn_partition_readiness_blocked_reason_references_contracts_worker_failed_id.hpp"
#include "__callable/__latency_fn_partition_readiness_blocked_reason_storage_lifetime_worker_failed_id.hpp"
#include "__callable/__latency_fn_partition_readiness_blocked_reason_symbol_fact_worker_failed_id.hpp"
#include "__callable/__latency_fn_partition_readiness_blocked_reason_tokenization_worker_failed_id.hpp"
namespace scpp { extern const int __latency_lines_partition_readiness[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_partition_readiness_merge_order_key_for_row(PartitionReadinessRow row) {
	SCPP_CALL_DEPTH_GUARD("partition_readiness::merge_order_key_for_row", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/partition_readiness.phs", __latency_lines_partition_readiness[31]);
	int_t<> key = required_cast<int_t<>>(cast<int_t<>>(row->owner_kind_id));
	key = ((key * static_cast<int_t<> >(1000000)) + cast<int_t<>>(row->row_id));
	return __latency_fn_structure_row_ids_uint64_from_int(key);
}

}

namespace scpp { extern const int __latency_lines_partition_readiness[]; }
namespace scpp {
PartitionReadinessRow __latency_fn_partition_readiness_complete_publication_fields(PartitionReadinessRow row) {
	SCPP_CALL_DEPTH_GUARD("partition_readiness::complete_publication_fields", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/partition_readiness.phs", __latency_lines_partition_readiness[32]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->input_snapshot_generation), static_cast<int_t<> >(0)))) {
		row->input_snapshot_generation = row->owner_run_id;
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->local_row_first_id), static_cast<int_t<> >(0)))) {
		row->local_row_first_id = row->owner_row_id;
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->local_row_first_id), static_cast<int_t<> >(0)))) {
			row->local_row_first_id = row->row_id;
		}
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->local_row_count), static_cast<int_t<> >(0)))) {
		row->local_row_count = __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->merge_order_key), static_cast<int_t<> >(0)))) {
		row->merge_order_key = __latency_fn_partition_readiness_merge_order_key_for_row(row);
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_partition_readiness_status_ready_id())))) {
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->published_row_first_id), static_cast<int_t<> >(0)))) {
			row->published_row_first_id = row->row_id;
		}
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->published_row_count), static_cast<int_t<> >(0)))) {
			row->published_row_count = __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1));
		}
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->publication_model_id), static_cast<int_t<> >(0)))) {
		row->publication_model_id = __latency_fn_partition_readiness_publication_model_main_thread_coordinator_id();
	}
	return row;
}

}

namespace scpp { extern const int __latency_lines_partition_readiness[]; }
namespace scpp {
PartitionReadinessRow __latency_fn_partition_readiness_source_owner_row(int_t<std::uint32_t> rowId, ResidentSourceUnitSnapshotRow snapshot) {
	SCPP_CALL_DEPTH_GUARD("partition_readiness::source_owner_row", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/partition_readiness.phs", __latency_lines_partition_readiness[33]);
	int_t<std::uint16_t> statusId = required_cast<int_t<std::uint16_t>>(__latency_fn_partition_readiness_status_ready_id());
	int_t<std::uint16_t> blockedReasonId = required_cast<int_t<std::uint16_t>>(__latency_fn_partition_readiness_blocked_reason_none_id());
	if (static_cast<bool>(php::identical(cast<int_t<>>(snapshot->source_unit_id), static_cast<int_t<> >(0)))) {
		statusId = __latency_fn_partition_readiness_status_blocked_id();
		blockedReasonId = __latency_fn_partition_readiness_blocked_reason_missing_source_owner_id();
	}
	return __latency_fn_partition_readiness_row(cast<int_t<std::uint32_t>>(rowId), snapshot->owner_run_id, __latency_fn_partition_readiness_owner_kind_source_unit_id(), snapshot->source_unit_id, __latency_fn_structure_row_ids_none_id(), snapshot->snapshot_id, cast<int_t<std::uint16_t>>(statusId), cast<int_t<std::uint16_t>>(blockedReasonId));
}

}

namespace scpp { extern const int __latency_lines_partition_readiness[]; }
namespace scpp {
PartitionReadinessRow __latency_fn_partition_readiness_symbol_body_owner_row(int_t<std::uint32_t> rowId, ResidentSymbolDefinitionSnapshotRow snapshot) {
	SCPP_CALL_DEPTH_GUARD("partition_readiness::symbol_body_owner_row", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/partition_readiness.phs", __latency_lines_partition_readiness[34]);
	int_t<std::uint16_t> statusId = required_cast<int_t<std::uint16_t>>(__latency_fn_partition_readiness_status_ready_id());
	int_t<std::uint16_t> blockedReasonId = required_cast<int_t<std::uint16_t>>(__latency_fn_partition_readiness_blocked_reason_none_id());
	if (static_cast<bool>((php::identical(cast<int_t<>>(snapshot->symbol_id), static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(snapshot->body_hash), static_cast<int_t<> >(0))))) {
		statusId = __latency_fn_partition_readiness_status_blocked_id();
		blockedReasonId = __latency_fn_partition_readiness_blocked_reason_missing_symbol_body_owner_id();
	}
	return __latency_fn_partition_readiness_row(cast<int_t<std::uint32_t>>(rowId), snapshot->owner_run_id, __latency_fn_partition_readiness_owner_kind_symbol_body_id(), snapshot->source_unit_id, snapshot->symbol_id, snapshot->snapshot_id, cast<int_t<std::uint16_t>>(statusId), cast<int_t<std::uint16_t>>(blockedReasonId));
}

}

namespace scpp { extern const int __latency_lines_partition_readiness[]; }
namespace scpp {
string_t __latency_fn_partition_readiness_owner_kind_name(int_t<std::uint16_t> ownerKindId) {
	SCPP_CALL_DEPTH_GUARD("partition_readiness::owner_kind_name", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/partition_readiness.phs", __latency_lines_partition_readiness[35]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(ownerKindId), cast<int_t<>>(__latency_fn_partition_readiness_owner_kind_source_unit_id())))) {
		return string_t("source_unit");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(ownerKindId), cast<int_t<>>(__latency_fn_partition_readiness_owner_kind_symbol_body_id())))) {
		return string_t("symbol_body");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(ownerKindId), cast<int_t<>>(__latency_fn_partition_readiness_owner_kind_lowering_id())))) {
		return string_t("lowering");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(ownerKindId), cast<int_t<>>(__latency_fn_partition_readiness_owner_kind_backend_id())))) {
		return string_t("backend");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(ownerKindId), cast<int_t<>>(__latency_fn_partition_readiness_owner_kind_object_id())))) {
		return string_t("object");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_partition_readiness[]; }
namespace scpp {
string_t __latency_fn_partition_readiness_status_name(int_t<std::uint16_t> statusId) {
	SCPP_CALL_DEPTH_GUARD("partition_readiness::status_name", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/partition_readiness.phs", __latency_lines_partition_readiness[36]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(statusId), cast<int_t<>>(__latency_fn_partition_readiness_status_ready_id())))) {
		return string_t("ready");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(statusId), cast<int_t<>>(__latency_fn_partition_readiness_status_blocked_id())))) {
		return string_t("blocked");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_partition_readiness[]; }
namespace scpp {
string_t __latency_fn_partition_readiness_blocked_reason_name(int_t<std::uint16_t> blockedReasonId) {
	SCPP_CALL_DEPTH_GUARD("partition_readiness::blocked_reason_name", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/partition_readiness.phs", __latency_lines_partition_readiness[37]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_partition_readiness_blocked_reason_none_id())))) {
		return string_t("none");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_partition_readiness_blocked_reason_missing_source_owner_id())))) {
		return string_t("missing_source_owner");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_partition_readiness_blocked_reason_missing_symbol_body_owner_id())))) {
		return string_t("missing_symbol_body_owner");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_partition_readiness_blocked_reason_missing_lowering_owner_id())))) {
		return string_t("missing_lowering_owner");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_partition_readiness_blocked_reason_lowering_plan_blocked_id())))) {
		return string_t("lowering_plan_blocked");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_partition_readiness_blocked_reason_missing_backend_owner_id())))) {
		return string_t("missing_backend_owner");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_partition_readiness_blocked_reason_backend_partition_blocked_id())))) {
		return string_t("backend_partition_blocked");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_partition_readiness_blocked_reason_object_execution_deferred_id())))) {
		return string_t("object_execution_deferred");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_partition_readiness_blocked_reason_tokenization_worker_failed_id())))) {
		return string_t("tokenization_worker_failed");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_partition_readiness_blocked_reason_parser_frontend_worker_failed_id())))) {
		return string_t("parser_frontend_worker_failed");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_partition_readiness_blocked_reason_symbol_fact_worker_failed_id())))) {
		return string_t("symbol_fact_worker_failed");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_partition_readiness_blocked_reason_references_contracts_worker_failed_id())))) {
		return string_t("references_contracts_worker_failed");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_partition_readiness_blocked_reason_capability_readiness_worker_failed_id())))) {
		return string_t("capability_readiness_worker_failed");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_partition_readiness_blocked_reason_storage_lifetime_worker_failed_id())))) {
		return string_t("storage_lifetime_worker_failed");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_partition_readiness_blocked_reason_lowering_backend_request_worker_failed_id())))) {
		return string_t("lowering_backend_request_worker_failed");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_partition_readiness_blocked_reason_llvm_text_emission_worker_failed_id())))) {
		return string_t("llvm_text_emission_worker_failed");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_partition_readiness_blocked_reason_object_output_worker_failed_id())))) {
		return string_t("object_output_worker_failed");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(blockedReasonId), cast<int_t<>>(__latency_fn_partition_readiness_blocked_reason_coordinator_owned_link_boundary_id())))) {
		return string_t("coordinator_owned_link_boundary");
	}
	return string_t("unknown");
}

}

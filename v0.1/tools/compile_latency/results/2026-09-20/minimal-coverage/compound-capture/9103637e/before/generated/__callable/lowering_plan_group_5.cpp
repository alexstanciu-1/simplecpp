#include <scpp/lang/php.hpp>
#include "__types/BackendRequestAuthorizationArtifact.hpp"
#include "__types/BackendRequestAuthorizationRow.hpp"
#include "__types/LoweringBlockedRequestRow.hpp"
#include "__types/LoweringPlan.hpp"
#include "__types/LoweringStep.hpp"
#include "__types/PartitionReadinessArtifact.hpp"
#include "__types/PartitionReadinessRow.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__callable/__latency_fn_lowering_plan_blocked_request_stable_hash.hpp"
#include "__callable/__latency_fn_lowering_plan_publication_plan_row_count.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_lowering_plan_publication_plan_row_count.hpp"
#include "__callable/__latency_fn_lowering_plan_publication_published_row_count.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_lowering_plan_backend_lowering_publication_row.hpp"
#include "__callable/__latency_fn_lowering_plan_publication_published_row_count.hpp"
#include "__callable/__latency_fn_partition_readiness_blocked_reason_missing_lowering_owner_id.hpp"
#include "__callable/__latency_fn_partition_readiness_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_partition_readiness_owner_kind_lowering_id.hpp"
#include "__callable/__latency_fn_partition_readiness_row.hpp"
#include "__callable/__latency_fn_partition_readiness_status_blocked_id.hpp"
#include "__callable/__latency_fn_partition_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint64_from_int.hpp"
#include "__callable/__latency_fn_lowering_plan_backend_lowering_publication_artifact.hpp"
#include "__callable/__latency_fn_lowering_plan_backend_lowering_publication_row.hpp"
#include "__callable/__latency_fn_partition_readiness_append_artifact_row.hpp"
#include "__callable/__latency_fn_partition_readiness_new_artifact.hpp"
#include "__callable/__latency_fn_lowering_plan_backend_lowering_publication_published_row_total.hpp"
#include "__callable/__latency_fn_partition_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_lowering_plan_semantic_hash_modulus.hpp"
#include "__callable/__latency_fn_lowering_plan_semantic_hash_mix_int.hpp"
#include "__callable/__latency_fn_lowering_plan_semantic_hash_modulus.hpp"
#include "__callable/__latency_fn_lowering_plan_semantic_hash_mix_backend_request.hpp"
#include "__callable/__latency_fn_lowering_plan_semantic_hash_mix_int.hpp"
#include "__callable/__latency_fn_lowering_plan_semantic_hash_mix_int.hpp"
#include "__callable/__latency_fn_lowering_plan_semantic_hash_mix_lowering_plan.hpp"
namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_lowering_plan_blocked_request_stable_hash(LoweringBlockedRequestRow row) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::blocked_request_stable_hash", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[58]);
	string_t identity = required_cast<string_t>((string_t("lowering_blocked_request:v2:") + cast<string_t>(cast<int_t<>>(row->blocked_request_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->backend_request_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->contract_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->source_row_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->feature_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->lowering_adapter_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->cache_owner_symbol_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->plan_effect_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->status_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->blocked_reason_id))));
	return php::stable_hash_string_u64(identity);
}

}

namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_lowering_plan_publication_plan_row_count(shared_p<LoweringPlan> plan) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::publication_plan_row_count", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[59]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(plan->owner_symbol_id), static_cast<int_t<> >(0)))) {
		return __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0));
	}
	return __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_lowering_plan_publication_published_row_count(shared_p<BackendRequestAuthorizationArtifact> requests, shared_p<LoweringPlan> plan) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::publication_published_row_count", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[60]);
	int_t<> total = required_cast<int_t<>>(cast<int_t<>>(requests->request_count));
	total = (total + cast<int_t<>>(__latency_fn_lowering_plan_publication_plan_row_count(plan)));
	total = (total + cast<int_t<>>(plan->step_count));
	total = (total + cast<int_t<>>(plan->work_ref_count));
	total = (total + cast<int_t<>>(plan->blocked_request_count));
	total = (total + cast<int_t<>>(plan->binary_operand_count));
	total = (total + cast<int_t<>>(plan->local_operand_count));
	total = (total + cast<int_t<>>(plan->call_argument_count));
	total = (total + cast<int_t<>>(plan->control_flow_operand_count));
	return __latency_fn_structure_row_ids_uint32_from_int(total);
}

}

namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
PartitionReadinessRow __latency_fn_lowering_plan_backend_lowering_publication_row(int_t<std::uint32_t> ownerRunId, ProjectSymbolIndexRow entrySymbol, shared_p<BackendRequestAuthorizationArtifact> requests, shared_p<LoweringPlan> plan) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::backend_lowering_publication_row", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[61]);
	int_t<std::uint32_t> publishedRowCount = required_cast<int_t<std::uint32_t>>(__latency_fn_lowering_plan_publication_published_row_count(requests, plan));
	int_t<std::uint16_t> statusId = required_cast<int_t<std::uint16_t>>(__latency_fn_partition_readiness_status_ready_id());
	int_t<std::uint16_t> blockedReasonId = required_cast<int_t<std::uint16_t>>(__latency_fn_partition_readiness_blocked_reason_none_id());
	if (static_cast<bool>(php::identical(cast<int_t<>>(publishedRowCount), static_cast<int_t<> >(0)))) {
		statusId = __latency_fn_partition_readiness_status_blocked_id();
		blockedReasonId = __latency_fn_partition_readiness_blocked_reason_missing_lowering_owner_id();
	}
	PartitionReadinessRow row = __latency_fn_partition_readiness_row(__latency_fn_structure_row_ids_uint32_from_int((static_cast<int_t<> >(12000) + cast<int_t<>>(entrySymbol->symbol_id))), ownerRunId, __latency_fn_partition_readiness_owner_kind_lowering_id(), entrySymbol->source_unit_id, entrySymbol->symbol_id, entrySymbol->symbol_id, statusId, blockedReasonId);
	row->input_snapshot_generation = ownerRunId;
	row->local_row_first_id = __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1));
	row->local_row_count = publishedRowCount;
	row->merge_order_key = __latency_fn_structure_row_ids_uint64_from_int((static_cast<int_t<> >(6500000) + cast<int_t<>>(entrySymbol->symbol_id)));
	row->published_row_first_id = __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1));
	row->published_row_count = publishedRowCount;
	return row;
}

}

namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
shared_p<PartitionReadinessArtifact> __latency_fn_lowering_plan_backend_lowering_publication_artifact(int_t<std::uint32_t> ownerRunId, ProjectSymbolIndexRow entrySymbol, shared_p<BackendRequestAuthorizationArtifact> requests, shared_p<LoweringPlan> plan) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::backend_lowering_publication_artifact", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[62]);
	shared_p<PartitionReadinessArtifact> artifact = __latency_fn_partition_readiness_new_artifact(static_cast<int_t<> >(1));
	__latency_fn_partition_readiness_append_artifact_row(artifact, __latency_fn_lowering_plan_backend_lowering_publication_row(cast<int_t<std::uint32_t>>(ownerRunId), entrySymbol, requests, plan));
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_lowering_plan_backend_lowering_publication_published_row_total(shared_p<PartitionReadinessArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::backend_lowering_publication_published_row_total", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[63]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = artifact->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_partition_readiness_status_ready_id())))) {
			total = (total + cast<int_t<>>(row->published_row_count));
		}
	}
	return __latency_fn_structure_row_ids_uint32_from_int(total);
}

}

namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
int_t<> __latency_fn_lowering_plan_semantic_hash_modulus() {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::semantic_hash_modulus", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[64]);
	return static_cast<int_t<> >(1000000007);
}

}

namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
int_t<> __latency_fn_lowering_plan_semantic_hash_mix_int(int_t<> hash, int_t<> value) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::semantic_hash_mix_int", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[65]);
	int_t<> mixed = required_cast<int_t<>>((((hash * static_cast<int_t<> >(131)) + value) % __latency_fn_lowering_plan_semantic_hash_modulus()));
	if (static_cast<bool>((mixed < static_cast<int_t<> >(0)))) {
		return (mixed + __latency_fn_lowering_plan_semantic_hash_modulus());
	}
	return mixed;
}

}

namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
int_t<> __latency_fn_lowering_plan_semantic_hash_mix_backend_request(int_t<> hash, BackendRequestAuthorizationRow row) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::semantic_hash_mix_backend_request", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[66]);
	int_t<> next = required_cast<int_t<>>(__latency_fn_lowering_plan_semantic_hash_mix_int(hash, cast<int_t<>>(row->request_id)));
	next = __latency_fn_lowering_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->contract_id));
	next = __latency_fn_lowering_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->project_callable_contract_id));
	next = __latency_fn_lowering_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->source_row_id));
	next = __latency_fn_lowering_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->source_reference_id));
	next = __latency_fn_lowering_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->target_symbol_id));
	next = __latency_fn_lowering_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->feature_id));
	next = __latency_fn_lowering_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->lowering_adapter_id));
	next = __latency_fn_lowering_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->lowering_step_kind_id));
	next = __latency_fn_lowering_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->provider_type_ref_id));
	next = __latency_fn_lowering_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->cache_owner_symbol_id));
	next = __latency_fn_lowering_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->status_id));
	next = __latency_fn_lowering_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->blocked_reason_id));
	next = __latency_fn_lowering_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->project_callable_blocked_reason_id));
	next = __latency_fn_lowering_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->value));
	return next;
}

}

namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
int_t<> __latency_fn_lowering_plan_semantic_hash_mix_lowering_plan(int_t<> hash, shared_p<LoweringPlan> plan) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::semantic_hash_mix_lowering_plan", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[67]);
	int_t<> next = required_cast<int_t<>>(__latency_fn_lowering_plan_semantic_hash_mix_int(hash, cast<int_t<>>(plan->artifact_kind_id)));
	next = __latency_fn_lowering_plan_semantic_hash_mix_int(next, cast<int_t<>>(plan->schema_version));
	next = __latency_fn_lowering_plan_semantic_hash_mix_int(next, cast<int_t<>>(plan->source_model_id));
	next = __latency_fn_lowering_plan_semantic_hash_mix_int(next, cast<int_t<>>(plan->plan_model_id));
	next = __latency_fn_lowering_plan_semantic_hash_mix_int(next, cast<int_t<>>(plan->backend_emit_status_id));
	next = __latency_fn_lowering_plan_semantic_hash_mix_int(next, cast<int_t<>>(plan->owner_source_unit_id));
	next = __latency_fn_lowering_plan_semantic_hash_mix_int(next, cast<int_t<>>(plan->owner_symbol_id));
	next = __latency_fn_lowering_plan_semantic_hash_mix_int(next, cast<int_t<>>(plan->step_count));
	next = __latency_fn_lowering_plan_semantic_hash_mix_int(next, cast<int_t<>>(plan->work_ref_count));
	next = __latency_fn_lowering_plan_semantic_hash_mix_int(next, cast<int_t<>>(plan->blocked_request_count));
	next = __latency_fn_lowering_plan_semantic_hash_mix_int(next, cast<int_t<>>(plan->binary_operand_count));
	next = __latency_fn_lowering_plan_semantic_hash_mix_int(next, cast<int_t<>>(plan->local_operand_count));
	next = __latency_fn_lowering_plan_semantic_hash_mix_int(next, cast<int_t<>>(plan->call_argument_count));
	next = __latency_fn_lowering_plan_semantic_hash_mix_int(next, cast<int_t<>>(plan->control_flow_operand_count));
	auto __latency_local_0 = plan->work_ids;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto workId = __latency_local_1.value_copy();
		next = __latency_fn_lowering_plan_semantic_hash_mix_int(next, cast<int_t<>>(workId));
	}
	auto __latency_local_2 = plan->steps;
	for (auto __latency_local_3 : foreach_range(__latency_local_2)) {
		auto step = __latency_local_3.value_copy();
		next = __latency_fn_lowering_plan_semantic_hash_mix_int(next, cast<int_t<>>(step->step_id));
		next = __latency_fn_lowering_plan_semantic_hash_mix_int(next, cast<int_t<>>(step->backend_request_id));
		next = __latency_fn_lowering_plan_semantic_hash_mix_int(next, cast<int_t<>>(step->step_kind_id));
		next = __latency_fn_lowering_plan_semantic_hash_mix_int(next, cast<int_t<>>(step->contract_id));
		next = __latency_fn_lowering_plan_semantic_hash_mix_int(next, cast<int_t<>>(step->lowering_adapter_id));
		next = __latency_fn_lowering_plan_semantic_hash_mix_int(next, cast<int_t<>>(step->type_ref_id));
		next = __latency_fn_lowering_plan_semantic_hash_mix_int(next, cast<int_t<>>(step->source_row_id));
		next = __latency_fn_lowering_plan_semantic_hash_mix_int(next, cast<int_t<>>(step->source_reference_id));
		next = __latency_fn_lowering_plan_semantic_hash_mix_int(next, cast<int_t<>>(step->source_key_role_id));
		next = __latency_fn_lowering_plan_semantic_hash_mix_int(next, cast<int_t<>>(step->local_slot_source_row_id));
		next = __latency_fn_lowering_plan_semantic_hash_mix_int(next, cast<int_t<>>(step->target_symbol_id));
		next = __latency_fn_lowering_plan_semantic_hash_mix_int(next, cast<int_t<>>(step->value));
	}
	auto __latency_local_4 = plan->blocked_requests;
	for (auto __latency_local_5 : foreach_range(__latency_local_4)) {
		auto blocked = __latency_local_5.value_copy();
		next = __latency_fn_lowering_plan_semantic_hash_mix_int(next, cast<int_t<>>(blocked->blocked_request_id));
		next = __latency_fn_lowering_plan_semantic_hash_mix_int(next, cast<int_t<>>(blocked->backend_request_id));
		next = __latency_fn_lowering_plan_semantic_hash_mix_int(next, cast<int_t<>>(blocked->contract_id));
		next = __latency_fn_lowering_plan_semantic_hash_mix_int(next, cast<int_t<>>(blocked->project_callable_contract_id));
		next = __latency_fn_lowering_plan_semantic_hash_mix_int(next, cast<int_t<>>(blocked->source_row_id));
		next = __latency_fn_lowering_plan_semantic_hash_mix_int(next, cast<int_t<>>(blocked->feature_id));
		next = __latency_fn_lowering_plan_semantic_hash_mix_int(next, cast<int_t<>>(blocked->lowering_adapter_id));
		next = __latency_fn_lowering_plan_semantic_hash_mix_int(next, cast<int_t<>>(blocked->cache_owner_symbol_id));
		next = __latency_fn_lowering_plan_semantic_hash_mix_int(next, cast<int_t<>>(blocked->plan_effect_id));
		next = __latency_fn_lowering_plan_semantic_hash_mix_int(next, cast<int_t<>>(blocked->status_id));
		next = __latency_fn_lowering_plan_semantic_hash_mix_int(next, cast<int_t<>>(blocked->blocked_reason_id));
		next = __latency_fn_lowering_plan_semantic_hash_mix_int(next, cast<int_t<>>(blocked->project_callable_blocked_reason_id));
	}
	return next;
}

}

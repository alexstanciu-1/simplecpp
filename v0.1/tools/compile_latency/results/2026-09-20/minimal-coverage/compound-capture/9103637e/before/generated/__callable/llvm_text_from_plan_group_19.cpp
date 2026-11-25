#include <scpp/lang/php.hpp>
#include "__types/BackendEmissionDecisionArtifact.hpp"
#include "__types/BackendEmissionDecisionRow.hpp"
#include "__types/BackendRequestAuthorizationRow.hpp"
#include "__types/FunctionBodyTextEmissionPreflightArtifact.hpp"
#include "__types/FunctionBodyTextEmissionPreflightRow.hpp"
#include "__types/PartitionReadinessArtifact.hpp"
#include "__types/PartitionReadinessRow.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_publication_sink_boundary_row_count.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_publication_llvm_text_nonempty_row_count.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_emission_llvm_publication_published_row_count.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_publication_sink_boundary_row_count.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_emission_llvm_publication_published_row_count.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_emission_llvm_publication_row.hpp"
#include "__callable/__latency_fn_partition_readiness_blocked_reason_missing_backend_owner_id.hpp"
#include "__callable/__latency_fn_partition_readiness_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_partition_readiness_owner_kind_backend_id.hpp"
#include "__callable/__latency_fn_partition_readiness_row.hpp"
#include "__callable/__latency_fn_partition_readiness_status_blocked_id.hpp"
#include "__callable/__latency_fn_partition_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint64_from_int.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_emission_llvm_publication_artifact.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_emission_llvm_publication_row.hpp"
#include "__callable/__latency_fn_partition_readiness_append_artifact_row.hpp"
#include "__callable/__latency_fn_partition_readiness_new_artifact.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_emission_llvm_publication_published_row_total.hpp"
#include "__callable/__latency_fn_partition_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_semantic_hash_modulus.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_semantic_hash_mix_int.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_semantic_hash_modulus.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_semantic_hash_mix_int.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_semantic_hash_mix_request.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_semantic_hash_mix_decision.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_semantic_hash_mix_int.hpp"
namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_llvm_text_from_plan_publication_sink_boundary_row_count(BackendEmissionDecisionArtifact& emission) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::publication_sink_boundary_row_count", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[165]);
	if (static_cast<bool>((php::identical(cast<int_t<>>(emission->decision_count), static_cast<int_t<> >(0)) && php::identical(cast<int_t<>>(emission->owner_symbol_id), static_cast<int_t<> >(0))))) {
		return __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0));
	}
	return __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_llvm_text_from_plan_publication_llvm_text_nonempty_row_count(const string_t& moduleText) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::publication_llvm_text_nonempty_row_count", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[166]);
	if (static_cast<bool>((str::length(moduleText) > static_cast<int_t<> >(0)))) {
		return __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1));
	}
	return __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0));
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_llvm_text_from_plan_emission_llvm_publication_published_row_count(BackendEmissionDecisionArtifact& emission, FunctionBodyTextEmissionPreflightArtifact preflightArtifact) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::emission_llvm_publication_published_row_count", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[167]);
	int_t<> total = required_cast<int_t<>>(((((cast<int_t<>>(emission->decision_count) + cast<int_t<>>(emission->value_count)) + cast<int_t<>>(emission->block_count)) + cast<int_t<>>(preflightArtifact->row_count)) + cast<int_t<>>(__latency_fn_llvm_text_from_plan_publication_sink_boundary_row_count(emission))));
	return __latency_fn_structure_row_ids_uint32_from_int(total);
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
PartitionReadinessRow __latency_fn_llvm_text_from_plan_emission_llvm_publication_row(int_t<std::uint32_t> ownerRunId, ProjectSymbolIndexRow entrySymbol, BackendEmissionDecisionArtifact& emission, FunctionBodyTextEmissionPreflightArtifact preflightArtifact) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::emission_llvm_publication_row", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[168]);
	int_t<std::uint32_t> publishedRowCount = required_cast<int_t<std::uint32_t>>(__latency_fn_llvm_text_from_plan_emission_llvm_publication_published_row_count(emission, preflightArtifact));
	int_t<std::uint16_t> statusId = required_cast<int_t<std::uint16_t>>(__latency_fn_partition_readiness_status_ready_id());
	int_t<std::uint16_t> blockedReasonId = required_cast<int_t<std::uint16_t>>(__latency_fn_partition_readiness_blocked_reason_none_id());
	if (static_cast<bool>(php::identical(cast<int_t<>>(publishedRowCount), static_cast<int_t<> >(0)))) {
		statusId = __latency_fn_partition_readiness_status_blocked_id();
		blockedReasonId = __latency_fn_partition_readiness_blocked_reason_missing_backend_owner_id();
	}
	PartitionReadinessRow row = __latency_fn_partition_readiness_row(__latency_fn_structure_row_ids_uint32_from_int((static_cast<int_t<> >(13000) + cast<int_t<>>(entrySymbol->symbol_id))), ownerRunId, __latency_fn_partition_readiness_owner_kind_backend_id(), entrySymbol->source_unit_id, entrySymbol->symbol_id, entrySymbol->symbol_id, statusId, blockedReasonId);
	row->input_snapshot_generation = ownerRunId;
	row->local_row_first_id = __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1));
	row->local_row_count = publishedRowCount;
	row->merge_order_key = __latency_fn_structure_row_ids_uint64_from_int((static_cast<int_t<> >(6600000) + cast<int_t<>>(entrySymbol->symbol_id)));
	row->published_row_first_id = __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1));
	row->published_row_count = publishedRowCount;
	return row;
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
shared_p<PartitionReadinessArtifact> __latency_fn_llvm_text_from_plan_emission_llvm_publication_artifact(int_t<std::uint32_t> ownerRunId, ProjectSymbolIndexRow entrySymbol, BackendEmissionDecisionArtifact& emission, FunctionBodyTextEmissionPreflightArtifact preflightArtifact) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::emission_llvm_publication_artifact", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[169]);
	shared_p<PartitionReadinessArtifact> artifact = __latency_fn_partition_readiness_new_artifact(static_cast<int_t<> >(1));
	__latency_fn_partition_readiness_append_artifact_row(artifact, __latency_fn_llvm_text_from_plan_emission_llvm_publication_row(cast<int_t<std::uint32_t>>(ownerRunId), entrySymbol, emission, preflightArtifact));
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_llvm_text_from_plan_emission_llvm_publication_published_row_total(shared_p<PartitionReadinessArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::emission_llvm_publication_published_row_total", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[170]);
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

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
int_t<> __latency_fn_llvm_text_from_plan_semantic_hash_modulus() {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::semantic_hash_modulus", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[171]);
	return static_cast<int_t<> >(1000000007);
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
int_t<> __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(int_t<> hash, int_t<> value) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::semantic_hash_mix_int", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[172]);
	int_t<> mixed = required_cast<int_t<>>((((hash * static_cast<int_t<> >(131)) + value) % __latency_fn_llvm_text_from_plan_semantic_hash_modulus()));
	if (static_cast<bool>((mixed < static_cast<int_t<> >(0)))) {
		return (mixed + __latency_fn_llvm_text_from_plan_semantic_hash_modulus());
	}
	return mixed;
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
int_t<> __latency_fn_llvm_text_from_plan_semantic_hash_mix_request(int_t<> hash, BackendRequestAuthorizationRow row) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::semantic_hash_mix_request", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[173]);
	int_t<> next = required_cast<int_t<>>(__latency_fn_llvm_text_from_plan_semantic_hash_mix_int(hash, cast<int_t<>>(row->request_id)));
	next = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->contract_id));
	next = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->project_callable_contract_id));
	next = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->source_row_id));
	next = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->source_reference_id));
	next = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->target_symbol_id));
	next = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->feature_id));
	next = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->lowering_adapter_id));
	next = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->lowering_step_kind_id));
	next = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->provider_type_ref_id));
	next = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->cache_owner_symbol_id));
	next = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->status_id));
	next = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->blocked_reason_id));
	next = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->project_callable_blocked_reason_id));
	next = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->value));
	return next;
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
int_t<> __latency_fn_llvm_text_from_plan_semantic_hash_mix_decision(int_t<> hash, BackendEmissionDecisionRow row) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::semantic_hash_mix_decision", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[174]);
	int_t<> next = required_cast<int_t<>>(__latency_fn_llvm_text_from_plan_semantic_hash_mix_int(hash, cast<int_t<>>(row->decision_id)));
	next = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->owner_source_unit_id));
	next = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->owner_symbol_id));
	next = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->first_lowering_step_id));
	next = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->lowering_step_count));
	next = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->first_blocked_request_id));
	next = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->blocked_request_count));
	next = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->first_value_id));
	next = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->value_count));
	next = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->first_block_id));
	next = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->block_count));
	next = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->decision_kind_id));
	next = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->lowering_plan_status_id));
	next = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->status_id));
	next = __latency_fn_llvm_text_from_plan_semantic_hash_mix_int(next, cast<int_t<>>(row->blocked_reason_id));
	return next;
}

}

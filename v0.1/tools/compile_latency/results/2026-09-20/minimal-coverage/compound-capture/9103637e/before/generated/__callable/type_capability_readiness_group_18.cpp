#include <scpp/lang/php.hpp>
#include "__types/CapabilityConsumerRow.hpp"
#include "__types/CapabilityCoverageArtifact.hpp"
#include "__types/CapabilityProviderRow.hpp"
#include "__types/CapabilityReadinessRow.hpp"
#include "__types/CapabilityReadinessWorkerInput.hpp"
#include "__types/CapabilityRegistryRow.hpp"
#include "__types/PartitionReadinessArtifact.hpp"
#include "__types/PartitionReadinessRow.hpp"
#include "__types/ProjectCallableContractArtifact.hpp"
#include "__types/ProjectCallableContractRow.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__types/TypeArgRow.hpp"
#include "__types/TypeRefRow.hpp"
#include "__types/TypeRefTable.hpp"
#include "__callable/__latency_fn_partition_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_readiness_publication_published_row_total.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_semantic_hash_for_type_refs_and_coverage.hpp"
#include "__callable/__latency_fn_type_capability_readiness_semantic_hash_mix_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_reserve_capability_readiness_worker_input.hpp"
#include "__callable/__latency_fn_type_capability_readiness_capability_readiness_worker_input.hpp"
#include "__callable/__latency_fn_type_capability_readiness_reserve_capability_readiness_worker_input.hpp"
namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_type_capability_readiness_capability_readiness_publication_published_row_total(shared_p<PartitionReadinessArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::capability_readiness_publication_published_row_total", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[171]);
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

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_type_capability_readiness_semantic_hash_for_type_refs_and_coverage(TypeRefTable typeRefs, shared_p<CapabilityCoverageArtifact> coverage) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::semantic_hash_for_type_refs_and_coverage", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[172]);
	int_t<> hash = required_cast<int_t<>>(static_cast<int_t<> >(17));
	hash = __latency_fn_type_capability_readiness_semantic_hash_mix_int(hash, cast<int_t<>>(typeRefs->type_count));
	hash = __latency_fn_type_capability_readiness_semantic_hash_mix_int(hash, cast<int_t<>>(typeRefs->arg_count));
	auto __latency_local_0 = typeRefs->types;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		hash = __latency_fn_type_capability_readiness_semantic_hash_mix_int(hash, cast<int_t<>>(row->type_ref_id));
		hash = __latency_fn_type_capability_readiness_semantic_hash_mix_int(hash, cast<int_t<>>(row->type_kind_id));
		hash = __latency_fn_type_capability_readiness_semantic_hash_mix_int(hash, cast<int_t<>>(row->family_id));
		hash = __latency_fn_type_capability_readiness_semantic_hash_mix_int(hash, cast<int_t<>>(row->status_id));
	}
	auto __latency_local_2 = typeRefs->args;
	for (auto __latency_local_3 : foreach_range(__latency_local_2)) {
		auto row = __latency_local_3.value_copy();
		hash = __latency_fn_type_capability_readiness_semantic_hash_mix_int(hash, cast<int_t<>>(row->parent_type_ref_id));
		hash = __latency_fn_type_capability_readiness_semantic_hash_mix_int(hash, cast<int_t<>>(row->arg_index));
		hash = __latency_fn_type_capability_readiness_semantic_hash_mix_int(hash, cast<int_t<>>(row->arg_kind_id));
		hash = __latency_fn_type_capability_readiness_semantic_hash_mix_int(hash, cast<int_t<>>(row->type_arg_ref_id));
		hash = __latency_fn_type_capability_readiness_semantic_hash_mix_int(hash, cast<int_t<>>(row->value_arg_int));
		hash = __latency_fn_type_capability_readiness_semantic_hash_mix_int(hash, cast<int_t<>>(row->status_id));
	}
	hash = __latency_fn_type_capability_readiness_semantic_hash_mix_int(hash, cast<int_t<>>(coverage->capability_count));
	hash = __latency_fn_type_capability_readiness_semantic_hash_mix_int(hash, cast<int_t<>>(coverage->provider_count));
	hash = __latency_fn_type_capability_readiness_semantic_hash_mix_int(hash, cast<int_t<>>(coverage->consumer_count));
	hash = __latency_fn_type_capability_readiness_semantic_hash_mix_int(hash, cast<int_t<>>(coverage->readiness_count));
	hash = __latency_fn_type_capability_readiness_semantic_hash_mix_int(hash, cast<int_t<>>(coverage->blocked_consumer_count));
	auto __latency_local_4 = coverage->capabilities;
	for (auto __latency_local_5 : foreach_range(__latency_local_4)) {
		auto row = __latency_local_5.value_copy();
		hash = __latency_fn_type_capability_readiness_semantic_hash_mix_int(hash, cast<int_t<>>(row->capability_id));
	}
	auto __latency_local_6 = coverage->providers;
	for (auto __latency_local_7 : foreach_range(__latency_local_6)) {
		auto row = __latency_local_7.value_copy();
		hash = __latency_fn_type_capability_readiness_semantic_hash_mix_int(hash, cast<int_t<>>(row->capability_id));
		hash = __latency_fn_type_capability_readiness_semantic_hash_mix_int(hash, cast<int_t<>>(row->source_row_id));
		hash = __latency_fn_type_capability_readiness_semantic_hash_mix_int(hash, cast<int_t<>>(row->type_ref_id));
		hash = __latency_fn_type_capability_readiness_semantic_hash_mix_int(hash, cast<int_t<>>(row->source_key_id));
		hash = __latency_fn_type_capability_readiness_semantic_hash_mix_int(hash, cast<int_t<>>(row->status_id));
		hash = __latency_fn_type_capability_readiness_semantic_hash_mix_int(hash, cast<int_t<>>(row->adapter_id));
		hash = __latency_fn_type_capability_readiness_semantic_hash_mix_int(hash, cast<int_t<>>(row->evidence_id));
		hash = __latency_fn_type_capability_readiness_semantic_hash_mix_int(hash, cast<int_t<>>(row->blocked_reason_id));
	}
	auto __latency_local_8 = coverage->consumers;
	for (auto __latency_local_9 : foreach_range(__latency_local_8)) {
		auto row = __latency_local_9.value_copy();
		hash = __latency_fn_type_capability_readiness_semantic_hash_mix_int(hash, cast<int_t<>>(row->capability_id));
		hash = __latency_fn_type_capability_readiness_semantic_hash_mix_int(hash, cast<int_t<>>(row->source_row_id));
		hash = __latency_fn_type_capability_readiness_semantic_hash_mix_int(hash, cast<int_t<>>(row->provider_source_row_id));
		hash = __latency_fn_type_capability_readiness_semantic_hash_mix_int(hash, cast<int_t<>>(row->feature_id));
		hash = __latency_fn_type_capability_readiness_semantic_hash_mix_int(hash, cast<int_t<>>(row->source_key_id));
		hash = __latency_fn_type_capability_readiness_semantic_hash_mix_int(hash, cast<int_t<>>(row->provider_source_key_id));
		hash = __latency_fn_type_capability_readiness_semantic_hash_mix_int(hash, cast<int_t<>>(row->provider_type_ref_id));
		hash = __latency_fn_type_capability_readiness_semantic_hash_mix_int(hash, cast<int_t<>>(row->status_id));
		hash = __latency_fn_type_capability_readiness_semantic_hash_mix_int(hash, cast<int_t<>>(row->blocked_reason_id));
	}
	auto __latency_local_10 = coverage->readiness;
	for (auto __latency_local_11 : foreach_range(__latency_local_10)) {
		auto row = __latency_local_11.value_copy();
		hash = __latency_fn_type_capability_readiness_semantic_hash_mix_int(hash, cast<int_t<>>(row->source_row_id));
		hash = __latency_fn_type_capability_readiness_semantic_hash_mix_int(hash, cast<int_t<>>(row->consumer_feature_id));
		hash = __latency_fn_type_capability_readiness_semantic_hash_mix_int(hash, cast<int_t<>>(row->source_key_id));
		hash = __latency_fn_type_capability_readiness_semantic_hash_mix_int(hash, cast<int_t<>>(row->capability_id));
		hash = __latency_fn_type_capability_readiness_semantic_hash_mix_int(hash, cast<int_t<>>(row->provider_type_ref_id));
		hash = __latency_fn_type_capability_readiness_semantic_hash_mix_int(hash, cast<int_t<>>(row->status_id));
		hash = __latency_fn_type_capability_readiness_semantic_hash_mix_int(hash, cast<int_t<>>(row->blocked_reason_id));
	}
	return __latency_fn_structure_row_ids_uint32_from_int(hash);
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
void __latency_fn_type_capability_readiness_reserve_capability_readiness_worker_input(shared_p<CapabilityReadinessWorkerInput> input, int_t<> typeRefCapacity, int_t<> typeArgCapacity, int_t<> contractCapacity) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::reserve_capability_readiness_worker_input", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[173]);
	php::vector_reserve(input->type_ref_ids, typeRefCapacity);
	php::vector_reserve(input->type_ref_kind_ids, typeRefCapacity);
	php::vector_reserve(input->type_ref_family_ids, typeRefCapacity);
	php::vector_reserve(input->type_ref_status_ids, typeRefCapacity);
	php::vector_reserve(input->type_arg_parent_type_ref_ids, typeArgCapacity);
	php::vector_reserve(input->type_arg_indices, typeArgCapacity);
	php::vector_reserve(input->type_arg_kind_ids, typeArgCapacity);
	php::vector_reserve(input->type_arg_ref_ids, typeArgCapacity);
	php::vector_reserve(input->type_arg_value_ints, typeArgCapacity);
	php::vector_reserve(input->type_arg_status_ids, typeArgCapacity);
	php::vector_reserve(input->contract_ids, contractCapacity);
	php::vector_reserve(input->contract_reference_ids, contractCapacity);
	php::vector_reserve(input->contract_from_symbol_ids, contractCapacity);
	php::vector_reserve(input->contract_target_symbol_ids, contractCapacity);
	php::vector_reserve(input->contract_target_source_unit_ids, contractCapacity);
	php::vector_reserve(input->contract_argument_count_status_ids, contractCapacity);
	php::vector_reserve(input->contract_return_type_status_ids, contractCapacity);
	php::vector_reserve(input->contract_backend_lowering_status_ids, contractCapacity);
	php::vector_reserve(input->contract_status_ids, contractCapacity);
	php::vector_reserve(input->contract_blocked_reason_ids, contractCapacity);
	php::vector_reserve(input->contract_actual_arg_counts, contractCapacity);
	php::vector_reserve(input->contract_expected_arg_counts, contractCapacity);
	php::vector_reserve(input->contract_return_type_ref_ids, contractCapacity);
	php::vector_reserve(input->contract_backend_adapter_ids, contractCapacity);
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
shared_p<CapabilityReadinessWorkerInput> __latency_fn_type_capability_readiness_capability_readiness_worker_input(int_t<std::uint32_t> ownerRunId, ProjectSymbolIndexRow entrySymbol, TypeRefTable typeRefs, shared_p<ProjectCallableContractArtifact> contracts) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::capability_readiness_worker_input", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[174]);
	shared_p<CapabilityReadinessWorkerInput> input = create<CapabilityReadinessWorkerInput>();
	input->owner_run_id = cast<int_t<>>(ownerRunId);
	input->source_unit_id = cast<int_t<>>(entrySymbol->source_unit_id);
	input->symbol_id = cast<int_t<>>(entrySymbol->symbol_id);
	__latency_fn_type_capability_readiness_reserve_capability_readiness_worker_input(input, cast<int_t<>>(typeRefs->type_count), cast<int_t<>>(typeRefs->arg_count), cast<int_t<>>(contracts->contract_count));
	auto __latency_local_0 = typeRefs->types;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		{
		auto __latency_local_2 = cast<int_t<>>(row->type_ref_id);
		(void) input->type_ref_ids.push_back(__latency_local_2);
		}
		{
		auto __latency_local_3 = cast<int_t<>>(row->type_kind_id);
		(void) input->type_ref_kind_ids.push_back(__latency_local_3);
		}
		{
		auto __latency_local_4 = cast<int_t<>>(row->family_id);
		(void) input->type_ref_family_ids.push_back(__latency_local_4);
		}
		{
		auto __latency_local_5 = cast<int_t<>>(row->status_id);
		(void) input->type_ref_status_ids.push_back(__latency_local_5);
		}
	}
	auto __latency_local_6 = typeRefs->args;
	for (auto __latency_local_7 : foreach_range(__latency_local_6)) {
		auto row = __latency_local_7.value_copy();
		{
		auto __latency_local_8 = cast<int_t<>>(row->parent_type_ref_id);
		(void) input->type_arg_parent_type_ref_ids.push_back(__latency_local_8);
		}
		{
		auto __latency_local_9 = cast<int_t<>>(row->arg_index);
		(void) input->type_arg_indices.push_back(__latency_local_9);
		}
		{
		auto __latency_local_10 = cast<int_t<>>(row->arg_kind_id);
		(void) input->type_arg_kind_ids.push_back(__latency_local_10);
		}
		{
		auto __latency_local_11 = cast<int_t<>>(row->type_arg_ref_id);
		(void) input->type_arg_ref_ids.push_back(__latency_local_11);
		}
		{
		auto __latency_local_12 = cast<int_t<>>(row->value_arg_int);
		(void) input->type_arg_value_ints.push_back(__latency_local_12);
		}
		{
		auto __latency_local_13 = cast<int_t<>>(row->status_id);
		(void) input->type_arg_status_ids.push_back(__latency_local_13);
		}
	}
	auto __latency_local_14 = contracts->rows;
	for (auto __latency_local_15 : foreach_range(__latency_local_14)) {
		auto row = __latency_local_15.value_copy();
		{
		auto __latency_local_16 = cast<int_t<>>(row->contract_id);
		(void) input->contract_ids.push_back(__latency_local_16);
		}
		{
		auto __latency_local_17 = cast<int_t<>>(row->reference_id);
		(void) input->contract_reference_ids.push_back(__latency_local_17);
		}
		{
		auto __latency_local_18 = cast<int_t<>>(row->from_symbol_id);
		(void) input->contract_from_symbol_ids.push_back(__latency_local_18);
		}
		{
		auto __latency_local_19 = cast<int_t<>>(row->target_symbol_id);
		(void) input->contract_target_symbol_ids.push_back(__latency_local_19);
		}
		{
		auto __latency_local_20 = cast<int_t<>>(row->target_source_unit_id);
		(void) input->contract_target_source_unit_ids.push_back(__latency_local_20);
		}
		{
		auto __latency_local_21 = cast<int_t<>>(row->argument_count_status_id);
		(void) input->contract_argument_count_status_ids.push_back(__latency_local_21);
		}
		{
		auto __latency_local_22 = cast<int_t<>>(row->return_type_status_id);
		(void) input->contract_return_type_status_ids.push_back(__latency_local_22);
		}
		{
		auto __latency_local_23 = cast<int_t<>>(row->backend_lowering_status_id);
		(void) input->contract_backend_lowering_status_ids.push_back(__latency_local_23);
		}
		{
		auto __latency_local_24 = cast<int_t<>>(row->status_id);
		(void) input->contract_status_ids.push_back(__latency_local_24);
		}
		{
		auto __latency_local_25 = cast<int_t<>>(row->blocked_reason_id);
		(void) input->contract_blocked_reason_ids.push_back(__latency_local_25);
		}
		{
		auto __latency_local_26 = cast<int_t<>>(row->actual_arg_count);
		(void) input->contract_actual_arg_counts.push_back(__latency_local_26);
		}
		{
		auto __latency_local_27 = cast<int_t<>>(row->expected_arg_count);
		(void) input->contract_expected_arg_counts.push_back(__latency_local_27);
		}
		{
		auto __latency_local_28 = cast<int_t<>>(row->return_type_ref_id);
		(void) input->contract_return_type_ref_ids.push_back(__latency_local_28);
		}
		{
		auto __latency_local_29 = cast<int_t<>>(row->backend_adapter_id);
		(void) input->contract_backend_adapter_ids.push_back(__latency_local_29);
		}
	}
	return input;
}

}

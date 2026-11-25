#include <scpp/lang/php.hpp>
#include "__types/PartitionReadinessArtifact.hpp"
#include "__types/PartitionReadinessRow.hpp"
#include "__types/ProjectCallableContractArtifact.hpp"
#include "__types/ProjectCallableContractRow.hpp"
#include "__types/ProjectReferenceResolution.hpp"
#include "__types/ProjectReferenceResolutionRow.hpp"
#include "__types/ProjectSymbolFunctionImportRow.hpp"
#include "__types/ProjectSymbolIndex.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__types/ReferenceContractWorkerInput.hpp"
#include "__callable/__latency_fn_partition_readiness_append_artifact_row.hpp"
#include "__callable/__latency_fn_partition_readiness_new_artifact.hpp"
#include "__callable/__latency_fn_project_reference_resolution_reference_contract_publication_artifact.hpp"
#include "__callable/__latency_fn_project_reference_resolution_reference_contract_publication_row.hpp"
#include "__callable/__latency_fn_partition_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_project_reference_resolution_reference_contract_publication_published_row_total.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_project_reference_resolution_snapshot_int_at.hpp"
#include "__callable/__latency_fn_project_reference_resolution_snapshot_string_at.hpp"
#include "__callable/__latency_fn_project_reference_resolution_reserve_reference_contract_worker_input.hpp"
#include "__callable/__latency_fn_project_reference_resolution_append_worker_symbol_snapshot.hpp"
#include "__callable/__latency_fn_project_symbol_index_body_shape.hpp"
#include "__callable/__latency_fn_project_symbol_index_name.hpp"
#include "__callable/__latency_fn_project_symbol_index_qualified_name.hpp"
#include "__callable/__latency_fn_project_symbol_index_signature_shape.hpp"
#include "__callable/__latency_fn_project_symbol_index_source_unit_key.hpp"
#include "__callable/__latency_fn_project_reference_resolution_append_worker_function_import_snapshot.hpp"
#include "__callable/__latency_fn_project_symbol_index_function_import_alias_name.hpp"
#include "__callable/__latency_fn_project_symbol_index_function_import_namespace.hpp"
#include "__callable/__latency_fn_project_symbol_index_function_import_target_qualified_name.hpp"
namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
shared_p<PartitionReadinessArtifact> __latency_fn_project_reference_resolution_reference_contract_publication_artifact(int_t<std::uint32_t> ownerRunId, ProjectSymbolIndexRow entrySymbol, shared_p<ProjectReferenceResolution> references, shared_p<ProjectCallableContractArtifact> contracts) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::reference_contract_publication_artifact", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[59]);
	shared_p<PartitionReadinessArtifact> artifact = __latency_fn_partition_readiness_new_artifact(static_cast<int_t<> >(1));
	__latency_fn_partition_readiness_append_artifact_row(artifact, __latency_fn_project_reference_resolution_reference_contract_publication_row(cast<int_t<std::uint32_t>>(ownerRunId), entrySymbol, references, contracts));
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_project_reference_resolution_reference_contract_publication_published_row_total(shared_p<PartitionReadinessArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::reference_contract_publication_published_row_total", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[60]);
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

namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
int_t<> __latency_fn_project_reference_resolution_snapshot_int_at(const vector_t<int_t<>>& values, int_t<> index) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::snapshot_int_at", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[61]);
	if (static_cast<bool>(((index >= static_cast<int_t<> >(0)) && (index < php::count(values))))) {
		return cast<int_t<>>(values.at(index));
	}
	return static_cast<int_t<> >(0);
}

}

namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
string_t __latency_fn_project_reference_resolution_snapshot_string_at(const vector_t<string_t>& values, int_t<> index) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::snapshot_string_at", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[62]);
	if (static_cast<bool>(((index >= static_cast<int_t<> >(0)) && (index < php::count(values))))) {
		return values.at(index);
	}
	return string_t("");
}

}

namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
void __latency_fn_project_reference_resolution_reserve_reference_contract_worker_input(shared_p<ReferenceContractWorkerInput> input, int_t<> symbolCapacity) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::reserve_reference_contract_worker_input", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[63]);
	php::vector_reserve(input->symbol_ids, symbolCapacity);
	php::vector_reserve(input->symbol_source_unit_ids, symbolCapacity);
	php::vector_reserve(input->symbol_source_row_ids, symbolCapacity);
	php::vector_reserve(input->symbol_kind_ids, symbolCapacity);
	php::vector_reserve(input->symbol_scope_ids, symbolCapacity);
	php::vector_reserve(input->symbol_status_ids, symbolCapacity);
	php::vector_reserve(input->symbol_return_type_ref_ids, symbolCapacity);
	php::vector_reserve(input->symbol_parameter_counts, symbolCapacity);
	php::vector_reserve(input->symbol_first_parameter_type_ref_ids, symbolCapacity);
	php::vector_reserve(input->symbol_names, symbolCapacity);
	php::vector_reserve(input->symbol_qualified_names, symbolCapacity);
	php::vector_reserve(input->symbol_signature_shapes, symbolCapacity);
	php::vector_reserve(input->symbol_body_shapes, symbolCapacity);
	php::vector_reserve(input->symbol_source_unit_keys, symbolCapacity);
	php::vector_reserve(input->function_import_source_unit_ids, symbolCapacity);
	php::vector_reserve(input->function_import_source_row_ids, symbolCapacity);
	php::vector_reserve(input->function_import_namespaces, symbolCapacity);
	php::vector_reserve(input->function_import_alias_names, symbolCapacity);
	php::vector_reserve(input->function_import_target_qualified_names, symbolCapacity);
}

}

namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
void __latency_fn_project_reference_resolution_append_worker_symbol_snapshot(shared_p<ReferenceContractWorkerInput> input, shared_p<ProjectSymbolIndex> symbols, ProjectSymbolIndexRow row) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::append_worker_symbol_snapshot", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[64]);
	{
	auto __latency_local_0 = cast<int_t<>>(row->symbol_id);
	(void) input->symbol_ids.push_back(__latency_local_0);
	}
	{
	auto __latency_local_1 = cast<int_t<>>(row->source_unit_id);
	(void) input->symbol_source_unit_ids.push_back(__latency_local_1);
	}
	{
	auto __latency_local_2 = cast<int_t<>>(row->source_row_id);
	(void) input->symbol_source_row_ids.push_back(__latency_local_2);
	}
	{
	auto __latency_local_3 = cast<int_t<>>(row->symbol_kind_id);
	(void) input->symbol_kind_ids.push_back(__latency_local_3);
	}
	{
	auto __latency_local_4 = cast<int_t<>>(row->scope_id);
	(void) input->symbol_scope_ids.push_back(__latency_local_4);
	}
	{
	auto __latency_local_5 = cast<int_t<>>(row->status_id);
	(void) input->symbol_status_ids.push_back(__latency_local_5);
	}
	{
	auto __latency_local_6 = cast<int_t<>>(row->return_type_ref_id);
	(void) input->symbol_return_type_ref_ids.push_back(__latency_local_6);
	}
	{
	auto __latency_local_7 = cast<int_t<>>(row->parameter_count);
	(void) input->symbol_parameter_counts.push_back(__latency_local_7);
	}
	{
	auto __latency_local_8 = cast<int_t<>>(row->first_parameter_type_ref_id);
	(void) input->symbol_first_parameter_type_ref_ids.push_back(__latency_local_8);
	}
	{
	auto __latency_local_9 = __latency_fn_project_symbol_index_name(symbols, row);
	(void) input->symbol_names.push_back(__latency_local_9);
	}
	{
	auto __latency_local_10 = __latency_fn_project_symbol_index_qualified_name(symbols, row);
	(void) input->symbol_qualified_names.push_back(__latency_local_10);
	}
	{
	auto __latency_local_11 = __latency_fn_project_symbol_index_signature_shape(symbols, row);
	(void) input->symbol_signature_shapes.push_back(__latency_local_11);
	}
	{
	auto __latency_local_12 = __latency_fn_project_symbol_index_body_shape(symbols, row);
	(void) input->symbol_body_shapes.push_back(__latency_local_12);
	}
	{
	auto __latency_local_13 = __latency_fn_project_symbol_index_source_unit_key(symbols, row);
	(void) input->symbol_source_unit_keys.push_back(__latency_local_13);
	}
}

}

namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
void __latency_fn_project_reference_resolution_append_worker_function_import_snapshot(shared_p<ReferenceContractWorkerInput> input, shared_p<ProjectSymbolIndex> symbols, ProjectSymbolFunctionImportRow row) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::append_worker_function_import_snapshot", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[65]);
	{
	auto __latency_local_0 = cast<int_t<>>(row->source_unit_id);
	(void) input->function_import_source_unit_ids.push_back(__latency_local_0);
	}
	{
	auto __latency_local_1 = cast<int_t<>>(row->source_row_id);
	(void) input->function_import_source_row_ids.push_back(__latency_local_1);
	}
	{
	auto __latency_local_2 = __latency_fn_project_symbol_index_function_import_namespace(symbols, row);
	(void) input->function_import_namespaces.push_back(__latency_local_2);
	}
	{
	auto __latency_local_3 = __latency_fn_project_symbol_index_function_import_alias_name(symbols, row);
	(void) input->function_import_alias_names.push_back(__latency_local_3);
	}
	{
	auto __latency_local_4 = __latency_fn_project_symbol_index_function_import_target_qualified_name(symbols, row);
	(void) input->function_import_target_qualified_names.push_back(__latency_local_4);
	}
}

}

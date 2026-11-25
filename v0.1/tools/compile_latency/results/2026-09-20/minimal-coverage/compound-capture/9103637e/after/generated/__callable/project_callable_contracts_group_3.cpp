#include <scpp/lang/php.hpp>
#include "__types/ProjectCallableContractArtifact.hpp"
#include "__types/ProjectCallableContractRow.hpp"
#include "__types/ProjectReferenceResolution.hpp"
#include "__types/ProjectReferenceResolutionRow.hpp"
#include "__types/ProjectSymbolIndex.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__callable/__latency_fn_project_callable_contracts_append_from_reference.hpp"
#include "__callable/__latency_fn_project_callable_contracts_row_from_reference.hpp"
#include "__callable/__latency_fn_project_callable_contracts_status_compatible_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_project_callable_contracts_row_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
namespace scpp { extern const int __latency_lines_project_callable_contracts[]; }
namespace scpp {
ProjectCallableContractRow __latency_fn_project_callable_contracts_append_from_reference(shared_p<ProjectCallableContractArtifact> artifact, ProjectReferenceResolutionRow reference, shared_p<ProjectReferenceResolution> references, shared_p<ProjectSymbolIndex> symbols) {
	SCPP_CALL_DEPTH_GUARD("project_callable_contracts::append_from_reference", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_callable_contracts.phs", __latency_lines_project_callable_contracts[34]);
	ProjectCallableContractRow row = __latency_fn_project_callable_contracts_row_from_reference(artifact, reference, references, symbols);
	(void) artifact->rows.append(row);
	artifact->contract_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->rows));
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_project_callable_contracts_status_compatible_id())))) {
		artifact->compatible_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->compatible_count) + static_cast<int_t<> >(1)));
	}
	else {
		artifact->blocked_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->blocked_count) + static_cast<int_t<> >(1)));
	}
	return row;
}

}

namespace scpp { extern const int __latency_lines_project_callable_contracts[]; }
namespace scpp {
ProjectCallableContractRow __latency_fn_project_callable_contracts_row_by_id(shared_p<ProjectCallableContractArtifact> artifact, int_t<std::uint32_t> contractId) {
	SCPP_CALL_DEPTH_GUARD("project_callable_contracts::row_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_callable_contracts.phs", __latency_lines_project_callable_contracts[35]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(contractId, cast<int_t<>>(artifact->contract_count))))) {
		ProjectCallableContractRow row = artifact->rows[__latency_fn_structure_row_ids_dense_index(contractId)];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->contract_id), cast<int_t<>>(contractId)))) {
			return row;
		}
	}
	auto __latency_local_0 = artifact->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->contract_id), cast<int_t<>>(contractId)))) {
			return row;
		}
	}
	ProjectCallableContractRow empty = ProjectCallableContractRow{};
	return empty;
}

}

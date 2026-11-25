#include <scpp/lang/php.hpp>
#include "__callable/__latency_fn_structure_row_ids_is_runtime_generated_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_runtime_generated_max_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_runtime_generated_min_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_is_project_generated_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_project_generated_min_id.hpp"
namespace scpp { extern const int __latency_lines_structure_row_ids[]; }
namespace scpp {
bool_t __latency_fn_structure_row_ids_is_runtime_generated_id(int_t<std::uint32_t> rowId) {
	SCPP_CALL_DEPTH_GUARD("structure_row_ids::is_runtime_generated_id", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/row_ids.phs", __latency_lines_structure_row_ids[16]);
	return ((cast<int_t<>>(rowId) >= cast<int_t<>>(__latency_fn_structure_row_ids_runtime_generated_min_id())) && (cast<int_t<>>(rowId) <= cast<int_t<>>(__latency_fn_structure_row_ids_runtime_generated_max_id())));
}

}

namespace scpp { extern const int __latency_lines_structure_row_ids[]; }
namespace scpp {
bool_t __latency_fn_structure_row_ids_is_project_generated_id(int_t<std::uint32_t> rowId) {
	SCPP_CALL_DEPTH_GUARD("structure_row_ids::is_project_generated_id", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/row_ids.phs", __latency_lines_structure_row_ids[17]);
	return (cast<int_t<>>(rowId) >= cast<int_t<>>(__latency_fn_structure_row_ids_project_generated_min_id()));
}

}

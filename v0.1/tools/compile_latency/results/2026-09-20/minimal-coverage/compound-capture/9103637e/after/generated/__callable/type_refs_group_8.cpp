#include <scpp/lang/php.hpp>
#include "__types/ProjectSymbolIndex.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__types/TypeRefTable.hpp"
#include "__callable/__latency_fn_type_refs_add_type.hpp"
#include "__callable/__latency_fn_type_refs_new_table.hpp"
#include "__callable/__latency_fn_type_refs_table_from_project_symbols.hpp"
namespace scpp { extern const int __latency_lines_type_refs[]; }
namespace scpp {
TypeRefTable __latency_fn_type_refs_table_from_project_symbols(shared_p<ProjectSymbolIndex> symbols) {
	SCPP_CALL_DEPTH_GUARD("type_refs::table_from_project_symbols", "/tmp/scpp-edit-latency-20260919/app/compile/model/type_refs.phs", __latency_lines_type_refs[93]);
	TypeRefTable table = __latency_fn_type_refs_new_table(cast<int_t<>>(symbols->symbol_count), static_cast<int_t<> >(0));
	auto __latency_local_0 = symbols->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto symbol = __latency_local_1.value_copy();
		__latency_fn_type_refs_add_type(table, symbol->return_type_ref_id);
	}
	return table;
}

}

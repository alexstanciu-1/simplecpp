#include <scpp/lang/php.hpp>
#include "__types/ProjectSymbolIndex.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__callable/__latency_fn_project_symbol_index_name_id_by_text.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_project_symbol_index_ensure_function_name_lookup_slots.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_project_symbol_index_ensure_function_qualified_name_lookup_slots.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_project_symbol_index_ensure_function_name_lookup_slots.hpp"
#include "__callable/__latency_fn_project_symbol_index_ensure_function_qualified_name_lookup_slots.hpp"
#include "__callable/__latency_fn_project_symbol_index_record_function_name_symbol.hpp"
#include "__callable/__latency_fn_project_symbol_index_symbol_kind_function_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_project_symbol_index_function_name_match_count_by_name_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_project_symbol_index_function_symbol_by_name_id.hpp"
#include "__callable/__latency_fn_project_symbol_index_row_by_id.hpp"
#include "__callable/__latency_fn_project_symbol_index_function_symbol_by_name.hpp"
#include "__callable/__latency_fn_project_symbol_index_function_symbol_by_name_id.hpp"
#include "__callable/__latency_fn_project_symbol_index_name_id_by_text.hpp"
#include "__callable/__latency_fn_project_symbol_index_record_lookup_probe.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_project_symbol_index_qualified_name_id_by_text.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_project_symbol_index_function_qualified_name_match_count_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_project_symbol_index_function_symbol_by_qualified_name_id.hpp"
#include "__callable/__latency_fn_project_symbol_index_row_by_id.hpp"
namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_project_symbol_index_name_id_by_text(shared_p<ProjectSymbolIndex> index, const string_t& value) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::name_id_by_text", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[26]);
	if (static_cast<bool>(php::condition_truthy(php::isset(index->name_ids_by_text, value)))) {
		return index->name_ids_by_text[value];
	}
	return __latency_fn_structure_row_ids_none_id();
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
void __latency_fn_project_symbol_index_ensure_function_name_lookup_slots(shared_p<ProjectSymbolIndex> index, int_t<std::uint32_t> nameId) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::ensure_function_name_lookup_slots", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[27]);
	while (static_cast<bool>((php::count(index->function_name_symbol_ids) < cast<int_t<>>(nameId)))) {
		{
		auto __latency_local_0 = __latency_fn_structure_row_ids_none_id();
		(void) index->function_name_symbol_ids.append(__latency_local_0);
		}
		{
		auto __latency_local_1 = __latency_fn_structure_row_ids_none_id();
		(void) index->function_name_match_counts.append(__latency_local_1);
		}
	}
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
void __latency_fn_project_symbol_index_ensure_function_qualified_name_lookup_slots(shared_p<ProjectSymbolIndex> index, int_t<std::uint32_t> qualifiedNameId) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::ensure_function_qualified_name_lookup_slots", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[28]);
	while (static_cast<bool>((php::count(index->function_qualified_name_symbol_ids) < cast<int_t<>>(qualifiedNameId)))) {
		{
		auto __latency_local_0 = __latency_fn_structure_row_ids_none_id();
		(void) index->function_qualified_name_symbol_ids.append(__latency_local_0);
		}
		{
		auto __latency_local_1 = __latency_fn_structure_row_ids_none_id();
		(void) index->function_qualified_name_match_counts.append(__latency_local_1);
		}
	}
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
void __latency_fn_project_symbol_index_record_function_name_symbol(shared_p<ProjectSymbolIndex> index, ProjectSymbolIndexRow row) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::record_function_name_symbol", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[29]);
	if (static_cast<bool>((php::identical(cast<int_t<>>(row->name_id), static_cast<int_t<> >(0)) || php::not_identical(cast<int_t<>>(row->symbol_kind_id), cast<int_t<>>(__latency_fn_project_symbol_index_symbol_kind_function_id()))))) {
		return;
	}
	__latency_fn_project_symbol_index_ensure_function_name_lookup_slots(index, row->name_id);
	int_t<> slot = required_cast<int_t<>>((cast<int_t<>>(row->name_id) - static_cast<int_t<> >(1)));
	int_t<> count = required_cast<int_t<>>((cast<int_t<>>(index->function_name_match_counts[slot]) + static_cast<int_t<> >(1)));
	index->function_name_match_counts[slot] = __latency_fn_structure_row_ids_uint32_from_int(count);
	if (static_cast<bool>(php::identical(count, static_cast<int_t<> >(1)))) {
		index->function_name_symbol_ids[slot] = row->symbol_id;
	}
	else {
		index->function_name_symbol_ids[slot] = __latency_fn_structure_row_ids_none_id();
	}
	if (static_cast<bool>((cast<int_t<>>(row->qualified_name_id) > static_cast<int_t<> >(0)))) {
		__latency_fn_project_symbol_index_ensure_function_qualified_name_lookup_slots(index, row->qualified_name_id);
		int_t<> qualifiedSlot = required_cast<int_t<>>((cast<int_t<>>(row->qualified_name_id) - static_cast<int_t<> >(1)));
		int_t<> qualifiedCount = required_cast<int_t<>>((cast<int_t<>>(index->function_qualified_name_match_counts[qualifiedSlot]) + static_cast<int_t<> >(1)));
		index->function_qualified_name_match_counts[qualifiedSlot] = __latency_fn_structure_row_ids_uint32_from_int(qualifiedCount);
		if (static_cast<bool>(php::identical(qualifiedCount, static_cast<int_t<> >(1)))) {
			index->function_qualified_name_symbol_ids[qualifiedSlot] = row->symbol_id;
		}
		else {
			index->function_qualified_name_symbol_ids[qualifiedSlot] = __latency_fn_structure_row_ids_none_id();
		}
	}
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_project_symbol_index_function_name_match_count_by_name_id(shared_p<ProjectSymbolIndex> index, int_t<std::uint32_t> nameId) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::function_name_match_count_by_name_id", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[30]);
	int_t<> slot = required_cast<int_t<>>((cast<int_t<>>(nameId) - static_cast<int_t<> >(1)));
	if (static_cast<bool>(((cast<int_t<>>(nameId) > static_cast<int_t<> >(0)) && (slot < php::count(index->function_name_match_counts))))) {
		return index->function_name_match_counts[slot];
	}
	return __latency_fn_structure_row_ids_none_id();
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
ProjectSymbolIndexRow __latency_fn_project_symbol_index_function_symbol_by_name_id(shared_p<ProjectSymbolIndex> index, int_t<std::uint32_t> nameId) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::function_symbol_by_name_id", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[31]);
	int_t<> slot = required_cast<int_t<>>((cast<int_t<>>(nameId) - static_cast<int_t<> >(1)));
	if (static_cast<bool>(((cast<int_t<>>(nameId) > static_cast<int_t<> >(0)) && (slot < php::count(index->function_name_symbol_ids))))) {
		return __latency_fn_project_symbol_index_row_by_id(index, index->function_name_symbol_ids[slot]);
	}
	ProjectSymbolIndexRow empty = ProjectSymbolIndexRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
ProjectSymbolIndexRow __latency_fn_project_symbol_index_function_symbol_by_name(shared_p<ProjectSymbolIndex> index, const string_t& functionName) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::function_symbol_by_name", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[32]);
	int_t<std::uint32_t> nameId = required_cast<int_t<std::uint32_t>>(__latency_fn_project_symbol_index_name_id_by_text(index, functionName));
	__latency_fn_project_symbol_index_record_lookup_probe(index, __latency_fn_structure_row_ids_none_id());
	return __latency_fn_project_symbol_index_function_symbol_by_name_id(index, cast<int_t<std::uint32_t>>(nameId));
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_project_symbol_index_qualified_name_id_by_text(shared_p<ProjectSymbolIndex> index, const string_t& value) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::qualified_name_id_by_text", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[33]);
	if (static_cast<bool>(php::condition_truthy(php::isset(index->qualified_name_ids_by_text, value)))) {
		return index->qualified_name_ids_by_text[value];
	}
	return __latency_fn_structure_row_ids_none_id();
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_project_symbol_index_function_qualified_name_match_count_by_id(shared_p<ProjectSymbolIndex> index, int_t<std::uint32_t> qualifiedNameId) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::function_qualified_name_match_count_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[34]);
	int_t<> slot = required_cast<int_t<>>((cast<int_t<>>(qualifiedNameId) - static_cast<int_t<> >(1)));
	if (static_cast<bool>(((cast<int_t<>>(qualifiedNameId) > static_cast<int_t<> >(0)) && (slot < php::count(index->function_qualified_name_match_counts))))) {
		return index->function_qualified_name_match_counts[slot];
	}
	return __latency_fn_structure_row_ids_none_id();
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
ProjectSymbolIndexRow __latency_fn_project_symbol_index_function_symbol_by_qualified_name_id(shared_p<ProjectSymbolIndex> index, int_t<std::uint32_t> qualifiedNameId) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::function_symbol_by_qualified_name_id", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[35]);
	int_t<> slot = required_cast<int_t<>>((cast<int_t<>>(qualifiedNameId) - static_cast<int_t<> >(1)));
	if (static_cast<bool>(((cast<int_t<>>(qualifiedNameId) > static_cast<int_t<> >(0)) && (slot < php::count(index->function_qualified_name_symbol_ids))))) {
		return __latency_fn_project_symbol_index_row_by_id(index, index->function_qualified_name_symbol_ids[slot]);
	}
	ProjectSymbolIndexRow empty = ProjectSymbolIndexRow{};
	return empty;
}

}

#include <scpp/lang/php.hpp>
#include "__types/FrontendModel.hpp"
#include "__types/ProjectSymbolFunctionImportRow.hpp"
#include "__types/ProjectSymbolIndex.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__types/ProjectSymbolParameterRow.hpp"
#include "__types/SourceUnitTable.hpp"
#include "__types/SourceUnitTableRow.hpp"
#include "__callable/__latency_fn_project_symbol_index_append_from_declaration_sidecar.hpp"
#include "__callable/__latency_fn_project_symbol_index_append_from_model.hpp"
#include "__callable/__latency_fn_project_symbol_index_append_from_model_by_node_scan.hpp"
#include "__callable/__latency_fn_project_symbol_index_add_u32.hpp"
#include "__callable/__latency_fn_project_symbol_index_append_rebased_row_from_index.hpp"
#include "__callable/__latency_fn_project_symbol_index_body_shape.hpp"
#include "__callable/__latency_fn_project_symbol_index_intern_body_shape.hpp"
#include "__callable/__latency_fn_project_symbol_index_intern_name.hpp"
#include "__callable/__latency_fn_project_symbol_index_intern_qualified_name.hpp"
#include "__callable/__latency_fn_project_symbol_index_intern_signature_shape.hpp"
#include "__callable/__latency_fn_project_symbol_index_intern_source_unit_key.hpp"
#include "__callable/__latency_fn_project_symbol_index_name.hpp"
#include "__callable/__latency_fn_project_symbol_index_parameter_rows_for_symbol.hpp"
#include "__callable/__latency_fn_project_symbol_index_qualified_name.hpp"
#include "__callable/__latency_fn_project_symbol_index_record_function_name_symbol.hpp"
#include "__callable/__latency_fn_project_symbol_index_signature_shape.hpp"
#include "__callable/__latency_fn_source_identity_source_unit_key_from_table.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_project_symbol_index_append_function_import_by_names.hpp"
#include "__callable/__latency_fn_project_symbol_index_append_rebased_function_import_from_index.hpp"
#include "__callable/__latency_fn_project_symbol_index_function_import_alias_name.hpp"
#include "__callable/__latency_fn_project_symbol_index_function_import_namespace.hpp"
#include "__callable/__latency_fn_project_symbol_index_function_import_target_qualified_name.hpp"
#include "__callable/__latency_fn_project_symbol_index_append_imports_for_source_unit_from_index.hpp"
#include "__callable/__latency_fn_project_symbol_index_append_rebased_function_import_from_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_project_symbol_index_append_imports_for_source_unit_from_index.hpp"
#include "__callable/__latency_fn_project_symbol_index_append_rebased_row_from_index.hpp"
#include "__callable/__latency_fn_project_symbol_index_append_rows_for_source_unit_from_index.hpp"
#include "__callable/__latency_fn_project_symbol_index_update_lookup_policy.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_project_symbol_index_append_imports_for_source_unit_from_index.hpp"
#include "__callable/__latency_fn_project_symbol_index_append_rebased_row_from_index.hpp"
#include "__callable/__latency_fn_project_symbol_index_append_rows_for_source_unit_from_index.hpp"
#include "__callable/__latency_fn_project_symbol_index_append_rows_for_source_unit_span_from_index.hpp"
#include "__callable/__latency_fn_project_symbol_index_update_lookup_policy.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_project_symbol_index_row_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
void __latency_fn_project_symbol_index_append_from_model(shared_p<ProjectSymbolIndex> index, shared_p<SourceUnitTable> sourceUnits, SourceUnitTableRow sourceUnit, const string_t& sourceText, shared_p<FrontendModel> model) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::append_from_model", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[77]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_project_symbol_index_append_from_declaration_sidecar(index, sourceUnits, sourceUnit, sourceText, model)))) {
		return;
	}
	__latency_fn_project_symbol_index_append_from_model_by_node_scan(index, sourceUnits, sourceUnit, sourceText, model);
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
ProjectSymbolIndexRow __latency_fn_project_symbol_index_append_rebased_row_from_index(shared_p<ProjectSymbolIndex> target, shared_p<ProjectSymbolIndex> source, shared_p<SourceUnitTable> sourceUnits, SourceUnitTableRow sourceUnit, ProjectSymbolIndexRow sourceRow) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::append_rebased_row_from_index", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[78]);
	ProjectSymbolIndexRow row = sourceRow;
	row->symbol_id = __latency_fn_structure_row_ids_next_dense_id(php::count(target->rows));
	row->source_unit_id = sourceUnit->source_unit_id;
	row->name_id = __latency_fn_project_symbol_index_intern_name(target, __latency_fn_project_symbol_index_name(source, sourceRow));
	row->qualified_name_id = __latency_fn_project_symbol_index_intern_qualified_name(target, __latency_fn_project_symbol_index_qualified_name(source, sourceRow));
	row->signature_shape_id = __latency_fn_project_symbol_index_intern_signature_shape(target, __latency_fn_project_symbol_index_signature_shape(source, sourceRow));
	row->body_shape_id = __latency_fn_project_symbol_index_intern_body_shape(target, __latency_fn_project_symbol_index_body_shape(source, sourceRow));
	row->source_unit_key_id = __latency_fn_project_symbol_index_intern_source_unit_key(target, __latency_fn_source_identity_source_unit_key_from_table(sourceUnits, sourceUnit));
	row->first_parameter_row_id = __latency_fn_structure_row_ids_none_id();
	if (static_cast<bool>((cast<int_t<>>(sourceRow->parameter_count) > static_cast<int_t<> >(0)))) {
		auto __latency_local_0 = __latency_fn_project_symbol_index_parameter_rows_for_symbol(source, sourceRow);
		for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
			auto sourceParameter = __latency_local_1.value_copy();
			ProjectSymbolParameterRow parameter = sourceParameter;
			parameter->parameter_row_id = __latency_fn_structure_row_ids_next_dense_id(php::count(target->parameter_rows));
			parameter->symbol_id = row->symbol_id;
			(void) target->parameter_rows.append(parameter);
			if (static_cast<bool>(php::identical(cast<int_t<>>(row->first_parameter_row_id), static_cast<int_t<> >(0)))) {
				row->first_parameter_row_id = parameter->parameter_row_id;
			}
		}
	}
	__latency_fn_project_symbol_index_record_function_name_symbol(target, row);
	(void) target->rows.append(row);
	target->declaration_rows_indexed = __latency_fn_project_symbol_index_add_u32(target->declaration_rows_indexed, static_cast<int_t<> >(1));
	return row;
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_project_symbol_index_append_rebased_function_import_from_index(shared_p<ProjectSymbolIndex> target, shared_p<ProjectSymbolIndex> source, SourceUnitTableRow sourceUnit, ProjectSymbolFunctionImportRow sourceImport) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::append_rebased_function_import_from_index", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[79]);
	return __latency_fn_project_symbol_index_append_function_import_by_names(target, sourceUnit->source_unit_id, sourceImport->source_row_id, __latency_fn_project_symbol_index_function_import_namespace(source, sourceImport), __latency_fn_project_symbol_index_function_import_alias_name(source, sourceImport), __latency_fn_project_symbol_index_function_import_target_qualified_name(source, sourceImport));
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_project_symbol_index_append_imports_for_source_unit_from_index(shared_p<ProjectSymbolIndex> target, shared_p<ProjectSymbolIndex> source, SourceUnitTableRow sourceUnit) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::append_imports_for_source_unit_from_index", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[80]);
	int_t<> startCount = required_cast<int_t<>>(php::count(target->function_import_rows));
	auto __latency_local_0 = source->function_import_rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto sourceImport = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(sourceImport->source_unit_id), cast<int_t<>>(sourceUnit->source_unit_id)))) {
			__latency_fn_project_symbol_index_append_rebased_function_import_from_index(target, source, sourceUnit, sourceImport);
		}
	}
	return __latency_fn_structure_row_ids_uint32_from_int((php::count(target->function_import_rows) - startCount));
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_project_symbol_index_append_rows_for_source_unit_from_index(shared_p<ProjectSymbolIndex> target, shared_p<ProjectSymbolIndex> source, shared_p<SourceUnitTable> sourceUnits, SourceUnitTableRow sourceUnit) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::append_rows_for_source_unit_from_index", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[81]);
	int_t<> startCount = required_cast<int_t<>>(php::count(target->rows));
	auto __latency_local_0 = source->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto sourceRow = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(sourceRow->source_unit_id), cast<int_t<>>(sourceUnit->source_unit_id)))) {
			__latency_fn_project_symbol_index_append_rebased_row_from_index(target, source, sourceUnits, sourceUnit, sourceRow);
		}
	}
	__latency_fn_project_symbol_index_append_imports_for_source_unit_from_index(target, source, sourceUnit);
	target->symbol_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(target->rows));
	__latency_fn_project_symbol_index_update_lookup_policy(target);
	return __latency_fn_structure_row_ids_uint32_from_int((php::count(target->rows) - startCount));
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_project_symbol_index_append_rows_for_source_unit_span_from_index(shared_p<ProjectSymbolIndex> target, shared_p<ProjectSymbolIndex> source, shared_p<SourceUnitTable> sourceUnits, SourceUnitTableRow sourceUnit, int_t<std::uint32_t> firstSymbolId, int_t<std::uint32_t> symbolCount) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::append_rows_for_source_unit_span_from_index", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[82]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(symbolCount), static_cast<int_t<> >(0)))) {
		return __latency_fn_structure_row_ids_none_id();
	}
	if (static_cast<bool>((!__latency_fn_structure_row_ids_has_dense_id(firstSymbolId, php::count(source->rows))))) {
		return __latency_fn_project_symbol_index_append_rows_for_source_unit_from_index(target, source, sourceUnits, sourceUnit);
	}
	int_t<> startIndex = required_cast<int_t<>>(__latency_fn_structure_row_ids_dense_index(firstSymbolId));
	int_t<> endIndex = required_cast<int_t<>>((startIndex + cast<int_t<>>(symbolCount)));
	if (static_cast<bool>((endIndex > php::count(source->rows)))) {
		return __latency_fn_project_symbol_index_append_rows_for_source_unit_from_index(target, source, sourceUnits, sourceUnit);
	}
	int_t<> index = required_cast<int_t<>>(startIndex);
	while (static_cast<bool>((index < endIndex))) {
		ProjectSymbolIndexRow sourceRow = source->rows[index];
		if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(sourceRow->source_unit_id), cast<int_t<>>(sourceUnit->source_unit_id))))) {
			return __latency_fn_project_symbol_index_append_rows_for_source_unit_from_index(target, source, sourceUnits, sourceUnit);
		}
		index = (index + static_cast<int_t<> >(1));
	}
	int_t<> startCount = required_cast<int_t<>>(php::count(target->rows));
	index = startIndex;
	while (static_cast<bool>((index < endIndex))) {
		ProjectSymbolIndexRow sourceRow = source->rows[index];
		__latency_fn_project_symbol_index_append_rebased_row_from_index(target, source, sourceUnits, sourceUnit, sourceRow);
		index = (index + static_cast<int_t<> >(1));
	}
	__latency_fn_project_symbol_index_append_imports_for_source_unit_from_index(target, source, sourceUnit);
	target->symbol_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(target->rows));
	__latency_fn_project_symbol_index_update_lookup_policy(target);
	return __latency_fn_structure_row_ids_uint32_from_int((php::count(target->rows) - startCount));
}

}

namespace scpp { extern const int __latency_lines_project_symbol_index[]; }
namespace scpp {
ProjectSymbolIndexRow __latency_fn_project_symbol_index_row_by_id(shared_p<ProjectSymbolIndex> index, int_t<std::uint32_t> symbolId) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_index::row_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_index.phs", __latency_lines_project_symbol_index[83]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(symbolId, cast<int_t<>>(index->symbol_count))))) {
		ProjectSymbolIndexRow row = index->rows[__latency_fn_structure_row_ids_dense_index(symbolId)];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->symbol_id), cast<int_t<>>(symbolId)))) {
			return row;
		}
	}
	auto __latency_local_0 = index->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->symbol_id), cast<int_t<>>(symbolId)))) {
			return row;
		}
	}
	ProjectSymbolIndexRow empty = ProjectSymbolIndexRow{};
	return empty;
}

}

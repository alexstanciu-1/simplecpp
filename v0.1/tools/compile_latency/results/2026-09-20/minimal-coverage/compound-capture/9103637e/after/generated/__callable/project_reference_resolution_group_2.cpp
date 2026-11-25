#include <scpp/lang/php.hpp>
#include "__types/FrontendDeclarationPayloadRow.hpp"
#include "__types/FrontendModel.hpp"
#include "__types/FrontendModelKernelCounters.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/FrontendStatementPayloadRow.hpp"
#include "__types/ProjectReferenceResolution.hpp"
#include "__types/ProjectReferenceResolutionRow.hpp"
#include "__types/ProjectSymbolIndex.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__counter/create.hpp"
#include "__callable/__latency_fn_project_reference_resolution_intern_resolved_source_unit_key.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_project_reference_resolution_callee_name.hpp"
#include "__callable/__latency_fn_project_reference_resolution_from_symbol_key.hpp"
#include "__callable/__latency_fn_project_reference_resolution_reference_key.hpp"
#include "__callable/__latency_fn_reference_identity_debug_string_from_parts.hpp"
#include "__callable/__latency_fn_project_reference_resolution_from_symbol_key.hpp"
#include "__callable/__latency_fn_project_reference_resolution_materialized_string.hpp"
#include "__callable/__latency_fn_project_reference_resolution_from_source_unit_key.hpp"
#include "__callable/__latency_fn_project_reference_resolution_materialized_string.hpp"
#include "__callable/__latency_fn_project_reference_resolution_callee_name.hpp"
#include "__callable/__latency_fn_project_reference_resolution_materialized_string.hpp"
#include "__callable/__latency_fn_project_reference_resolution_call_expression_node_key.hpp"
#include "__callable/__latency_fn_project_reference_resolution_callee_name.hpp"
#include "__callable/__latency_fn_project_reference_resolution_materialized_string.hpp"
#include "__callable/__latency_fn_project_reference_resolution_resolved_symbol_key.hpp"
#include "__callable/__latency_fn_project_reference_resolution_materialized_string.hpp"
#include "__callable/__latency_fn_project_reference_resolution_resolved_source_unit_key.hpp"
#include "__callable/__latency_fn_project_reference_resolution_find_symbol_by_key.hpp"
#include "__callable/__latency_fn_project_symbol_index_record_lookup_probe.hpp"
#include "__callable/__latency_fn_project_symbol_index_symbol_key.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_project_reference_resolution_find_function_symbol_by_name.hpp"
#include "__callable/__latency_fn_project_symbol_index_function_symbol_by_name.hpp"
#include "__callable/__latency_fn_frontend_model_tables_declaration_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_project_reference_resolution_call_expression_node_from_statement_value.hpp"
#include "__callable/__latency_fn_project_reference_resolution_call_expression_node_from_symbol.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_expression_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_statement_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_expression_call_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_echo_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_expression_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_kind_statement_return_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_statement_by_id.hpp"
#include "__callable/__latency_fn_project_reference_resolution_call_expression_node_from_statement_value.hpp"
namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_project_reference_resolution_intern_resolved_source_unit_key(shared_p<ProjectReferenceResolution> artifact, const string_t& value) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::intern_resolved_source_unit_key", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[25]);
	int_t<> position = required_cast<int_t<>>(static_cast<int_t<> >(1));
	auto __latency_local_0 = artifact->resolved_source_unit_keys;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto existing = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(existing, value))) {
			return __latency_fn_structure_row_ids_uint32_from_int(position);
		}
		position = (position + static_cast<int_t<> >(1));
	}
	(void) artifact->resolved_source_unit_keys.append(value);
	return __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->resolved_source_unit_keys));
}

}

namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
string_t __latency_fn_project_reference_resolution_reference_key(shared_p<ProjectReferenceResolution> artifact, ProjectReferenceResolutionRow row) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::reference_key", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[26]);
	string_t calleeName = required_cast<string_t>(__latency_fn_project_reference_resolution_callee_name(artifact, row));
	if (static_cast<bool>(php::identical(calleeName, string_t("")))) {
		calleeName = string_t("missing_call_expression");
	}
	return __latency_fn_reference_identity_debug_string_from_parts(__latency_fn_project_reference_resolution_from_symbol_key(artifact, row), calleeName, row->reference_id);
}

}

namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
string_t __latency_fn_project_reference_resolution_from_symbol_key(shared_p<ProjectReferenceResolution> artifact, ProjectReferenceResolutionRow row) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::from_symbol_key", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[27]);
	return __latency_fn_project_reference_resolution_materialized_string(artifact->from_symbol_keys, row->from_symbol_key_id, string_t(""));
}

}

namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
string_t __latency_fn_project_reference_resolution_from_source_unit_key(shared_p<ProjectReferenceResolution> artifact, ProjectReferenceResolutionRow row) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::from_source_unit_key", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[28]);
	return __latency_fn_project_reference_resolution_materialized_string(artifact->from_source_unit_keys, row->from_source_unit_key_id, string_t(""));
}

}

namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
string_t __latency_fn_project_reference_resolution_callee_name(shared_p<ProjectReferenceResolution> artifact, ProjectReferenceResolutionRow row) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::callee_name", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[29]);
	return __latency_fn_project_reference_resolution_materialized_string(artifact->callee_names, row->callee_name_id, string_t(""));
}

}

namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
string_t __latency_fn_project_reference_resolution_call_expression_node_key(shared_p<ProjectReferenceResolution> artifact, ProjectReferenceResolutionRow row) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::call_expression_node_key", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[30]);
	return (string_t("frontend_call_expression_node:") + cast<string_t>(cast<int_t<>>(row->call_expression_node_id)) + string_t(":callee:") + cast<string_t>(__latency_fn_project_reference_resolution_callee_name(artifact, row)));
}

}

namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
string_t __latency_fn_project_reference_resolution_resolved_symbol_key(shared_p<ProjectReferenceResolution> artifact, ProjectReferenceResolutionRow row) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::resolved_symbol_key", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[31]);
	return __latency_fn_project_reference_resolution_materialized_string(artifact->resolved_symbol_keys, row->resolved_symbol_key_id, string_t(""));
}

}

namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
string_t __latency_fn_project_reference_resolution_resolved_source_unit_key(shared_p<ProjectReferenceResolution> artifact, ProjectReferenceResolutionRow row) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::resolved_source_unit_key", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[32]);
	return __latency_fn_project_reference_resolution_materialized_string(artifact->resolved_source_unit_keys, row->resolved_source_unit_key_id, string_t(""));
}

}

namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
ProjectSymbolIndexRow __latency_fn_project_reference_resolution_find_symbol_by_key(shared_p<ProjectSymbolIndex> symbols, const string_t& symbolKey) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::find_symbol_by_key", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[33]);
	int_t<> scannedRows = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = symbols->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto symbol = __latency_local_1.value_copy();
		scannedRows = (scannedRows + static_cast<int_t<> >(1));
		if (static_cast<bool>(php::identical(__latency_fn_project_symbol_index_symbol_key(symbols, symbol), symbolKey))) {
			__latency_fn_project_symbol_index_record_lookup_probe(symbols, __latency_fn_structure_row_ids_uint32_from_int(scannedRows));
			return symbol;
		}
	}
	__latency_fn_project_symbol_index_record_lookup_probe(symbols, __latency_fn_structure_row_ids_uint32_from_int(scannedRows));
	ProjectSymbolIndexRow empty = ProjectSymbolIndexRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
ProjectSymbolIndexRow __latency_fn_project_reference_resolution_find_function_symbol_by_name(shared_p<ProjectSymbolIndex> symbols, const string_t& functionName) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::find_function_symbol_by_name", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[34]);
	return __latency_fn_project_symbol_index_function_symbol_by_name(symbols, functionName);
}

}

namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
FrontendNodeRow __latency_fn_project_reference_resolution_call_expression_node_from_symbol(shared_p<FrontendModel> model, ProjectSymbolIndexRow fromSymbol) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::call_expression_node_from_symbol", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[35]);
	if (static_cast<bool>((cast<int_t<>>(model->parser_error_count) > static_cast<int_t<> >(0)))) {
		FrontendNodeRow empty = FrontendNodeRow{};
		return empty;
	}
	shared_p<FrontendModelKernelCounters> counters = __latency_counter_create();
	FrontendNodeRow declarationNode = __latency_fn_frontend_model_tables_node_by_id(model, fromSymbol->source_row_id, counters);
	if (static_cast<bool>(php::identical(cast<int_t<>>(declarationNode->node_id), static_cast<int_t<> >(0)))) {
		FrontendNodeRow empty = FrontendNodeRow{};
		return empty;
	}
	FrontendDeclarationPayloadRow declaration = __latency_fn_frontend_model_tables_declaration_by_id(model, declarationNode->payload_row_id, counters);
	int_t<std::uint32_t> statementNodeId = required_cast<int_t<std::uint32_t>>(declaration->body_node_id);
	while (static_cast<bool>((cast<int_t<>>(statementNodeId) > static_cast<int_t<> >(0)))) {
		FrontendNodeRow statementNode = __latency_fn_frontend_model_tables_node_by_id(model, statementNodeId, counters);
		FrontendNodeRow callNode = __latency_fn_project_reference_resolution_call_expression_node_from_statement_value(model, statementNode, counters);
		if (static_cast<bool>((cast<int_t<>>(callNode->node_id) > static_cast<int_t<> >(0)))) {
			return callNode;
		}
		statementNodeId = statementNode->next_sibling_node_id;
	}
	FrontendNodeRow empty = FrontendNodeRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
FrontendNodeRow __latency_fn_project_reference_resolution_call_expression_node_from_statement_value(shared_p<FrontendModel> model, FrontendNodeRow statementNode, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::call_expression_node_from_statement_value", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[36]);
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(statementNode->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_statement_id()))))) {
		FrontendNodeRow empty = FrontendNodeRow{};
		return empty;
	}
	if (static_cast<bool>(((php::not_identical(cast<int_t<>>(statementNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_return_id())) && php::not_identical(cast<int_t<>>(statementNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_echo_id()))) && php::not_identical(cast<int_t<>>(statementNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_statement_expression_id()))))) {
		FrontendNodeRow empty = FrontendNodeRow{};
		return empty;
	}
	FrontendStatementPayloadRow statement = __latency_fn_frontend_model_tables_statement_by_id(model, statementNode->payload_row_id, counters);
	FrontendNodeRow valueNode = __latency_fn_frontend_model_tables_node_by_id(model, statement->value_node_id, counters);
	if (static_cast<bool>((php::identical(cast<int_t<>>(valueNode->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_expression_id())) && php::identical(cast<int_t<>>(valueNode->row_kind_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_kind_expression_call_id()))))) {
		return valueNode;
	}
	FrontendNodeRow empty = FrontendNodeRow{};
	return empty;
}

}

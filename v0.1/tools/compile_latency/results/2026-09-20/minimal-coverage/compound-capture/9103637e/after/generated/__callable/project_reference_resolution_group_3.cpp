#include <scpp/lang/php.hpp>
#include "__types/FrontendExpressionPayloadRow.hpp"
#include "__types/FrontendModel.hpp"
#include "__types/FrontendModelKernelCounters.hpp"
#include "__types/FrontendNamePayloadRow.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/ProjectCallableContractArtifact.hpp"
#include "__types/ProjectCallableContractRow.hpp"
#include "__types/ProjectReferenceActualArgumentRow.hpp"
#include "__types/ProjectReferenceResolution.hpp"
#include "__types/ProjectReferenceResolutionRow.hpp"
#include "__types/SourceRangeRow.hpp"
#include "__counter/create.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_project_reference_resolution_call_expression_node_from_body_model.hpp"
#include "__callable/__latency_fn_project_reference_resolution_call_expression_node_from_statement_value.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_frontend_model_tables_expression_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_name_by_id.hpp"
#include "__callable/__latency_fn_project_reference_resolution_callee_name_from_call_expression.hpp"
#include "__callable/__latency_fn_project_symbol_index_source_range_text.hpp"
#include "__callable/__latency_fn_frontend_model_tables_expression_by_id.hpp"
#include "__callable/__latency_fn_project_reference_resolution_argument_count_from_call_expression.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_frontend_model_tables_expression_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_name_by_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_source_range_by_id.hpp"
#include "__callable/__latency_fn_project_reference_resolution_callee_name_source_range_from_call_expression.hpp"
#include "__callable/__latency_fn_project_reference_resolution_callee_name_from_source_range.hpp"
#include "__callable/__latency_fn_project_reference_resolution_callee_name_is_qualified.hpp"
#include "__callable/__latency_fn_project_reference_resolution_normalized_qualified_callee_name.hpp"
#include "__callable/__latency_fn_project_reference_resolution_reference_contract_worker_descriptor_parser_error_scale.hpp"
#include "__callable/__latency_fn_project_reference_resolution_reference_contract_worker_semantic_hash_modulus.hpp"
#include "__callable/__latency_fn_project_reference_resolution_reference_contract_worker_semantic_hash_modulus.hpp"
#include "__callable/__latency_fn_project_reference_resolution_semantic_hash_mix_int.hpp"
#include "__callable/__latency_fn_project_reference_resolution_semantic_hash_mix_int.hpp"
#include "__callable/__latency_fn_project_reference_resolution_semantic_hash_mix_string.hpp"
#include "__callable/__latency_fn_source_buffers_content_hash32.hpp"
#include "__callable/__latency_fn_project_reference_resolution_callee_name.hpp"
#include "__callable/__latency_fn_project_reference_resolution_resolved_source_unit_key.hpp"
#include "__callable/__latency_fn_project_reference_resolution_resolved_symbol_key.hpp"
#include "__callable/__latency_fn_project_reference_resolution_semantic_hash_for_reference_contracts.hpp"
#include "__callable/__latency_fn_project_reference_resolution_semantic_hash_mix_int.hpp"
#include "__callable/__latency_fn_project_reference_resolution_semantic_hash_mix_string.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
FrontendNodeRow __latency_fn_project_reference_resolution_call_expression_node_from_body_model(shared_p<FrontendModel> model) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::call_expression_node_from_body_model", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[37]);
	if (static_cast<bool>((cast<int_t<>>(model->parser_error_count) > static_cast<int_t<> >(0)))) {
		FrontendNodeRow empty = FrontendNodeRow{};
		return empty;
	}
	shared_p<FrontendModelKernelCounters> counters = __latency_counter_create();
	int_t<> nodeId = required_cast<int_t<>>(static_cast<int_t<> >(1));
	while (static_cast<bool>((nodeId <= cast<int_t<>>(model->node_count)))) {
		FrontendNodeRow statementNode = __latency_fn_frontend_model_tables_node_by_id(model, __latency_fn_structure_row_ids_uint32_from_int(nodeId), counters);
		FrontendNodeRow callNode = __latency_fn_project_reference_resolution_call_expression_node_from_statement_value(model, statementNode, counters);
		if (static_cast<bool>((cast<int_t<>>(callNode->node_id) > static_cast<int_t<> >(0)))) {
			return callNode;
		}
		nodeId = (nodeId + static_cast<int_t<> >(1));
	}
	FrontendNodeRow empty = FrontendNodeRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
string_t __latency_fn_project_reference_resolution_callee_name_from_call_expression(shared_p<FrontendModel> model, const string_t& sourceText, FrontendNodeRow callNode) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::callee_name_from_call_expression", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[38]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(callNode->node_id), static_cast<int_t<> >(0)))) {
		return string_t("");
	}
	shared_p<FrontendModelKernelCounters> counters = __latency_counter_create();
	FrontendExpressionPayloadRow expression = __latency_fn_frontend_model_tables_expression_by_id(model, callNode->payload_row_id, counters);
	FrontendNamePayloadRow name = __latency_fn_frontend_model_tables_name_by_id(model, expression->callee_name_id, counters);
	return __latency_fn_project_symbol_index_source_range_text(model, sourceText, name->source_range_id);
}

}

namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_project_reference_resolution_argument_count_from_call_expression(shared_p<FrontendModel> model, FrontendNodeRow callNode) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::argument_count_from_call_expression", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[39]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(callNode->node_id), static_cast<int_t<> >(0)))) {
		return __latency_fn_structure_row_ids_none_id();
	}
	shared_p<FrontendModelKernelCounters> counters = __latency_counter_create();
	FrontendExpressionPayloadRow expression = __latency_fn_frontend_model_tables_expression_by_id(model, callNode->payload_row_id, counters);
	return __latency_fn_structure_row_ids_uint32_from_int(cast<int_t<>>(expression->argument_count));
}

}

namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
SourceRangeRow __latency_fn_project_reference_resolution_callee_name_source_range_from_call_expression(shared_p<FrontendModel> model, FrontendNodeRow callNode) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::callee_name_source_range_from_call_expression", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[40]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(callNode->node_id), static_cast<int_t<> >(0)))) {
		SourceRangeRow empty = SourceRangeRow{};
		return empty;
	}
	shared_p<FrontendModelKernelCounters> counters = __latency_counter_create();
	FrontendExpressionPayloadRow expression = __latency_fn_frontend_model_tables_expression_by_id(model, callNode->payload_row_id, counters);
	FrontendNamePayloadRow name = __latency_fn_frontend_model_tables_name_by_id(model, expression->callee_name_id, counters);
	return __latency_fn_frontend_model_tables_source_range_by_id(model, name->source_range_id, counters);
}

}

namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
string_t __latency_fn_project_reference_resolution_callee_name_from_source_range(const string_t& sourceText, int_t<std::uint32_t> startOffset, int_t<std::uint32_t> length) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::callee_name_from_source_range", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[41]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(length), static_cast<int_t<> >(0)))) {
		return string_t("");
	}
	return str::byte_slice(sourceText, cast<int_t<>>(startOffset), cast<int_t<>>(length));
}

}

namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
bool_t __latency_fn_project_reference_resolution_callee_name_is_qualified(const string_t& calleeName) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::callee_name_is_qualified", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[42]);
	return php::str_contains(calleeName, string_t("\\"));
}

}

namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
string_t __latency_fn_project_reference_resolution_normalized_qualified_callee_name(const string_t& calleeName) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::normalized_qualified_callee_name", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[43]);
	if (static_cast<bool>((str::starts_with(calleeName, string_t("\\")) && (str::byte_length(calleeName) > static_cast<int_t<> >(1))))) {
		return str::byte_slice(calleeName, static_cast<int_t<> >(1), (str::byte_length(calleeName) - static_cast<int_t<> >(1)));
	}
	return calleeName;
}

}

namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
int_t<> __latency_fn_project_reference_resolution_reference_contract_worker_descriptor_parser_error_scale() {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::reference_contract_worker_descriptor_parser_error_scale", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[44]);
	return static_cast<int_t<> >(100);
}

}

namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
int_t<> __latency_fn_project_reference_resolution_reference_contract_worker_semantic_hash_modulus() {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::reference_contract_worker_semantic_hash_modulus", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[45]);
	return static_cast<int_t<> >(20000000);
}

}

namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
int_t<> __latency_fn_project_reference_resolution_semantic_hash_mix_int(int_t<> hash, int_t<> value) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::semantic_hash_mix_int", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[46]);
	int_t<> modulus = required_cast<int_t<>>(__latency_fn_project_reference_resolution_reference_contract_worker_semantic_hash_modulus());
	int_t<> mixed = required_cast<int_t<>>((((hash * static_cast<int_t<> >(131)) + value) % modulus));
	if (static_cast<bool>((mixed < static_cast<int_t<> >(0)))) {
		mixed = (mixed + modulus);
	}
	return mixed;
}

}

namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
int_t<> __latency_fn_project_reference_resolution_semantic_hash_mix_string(int_t<> hash, const string_t& value) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::semantic_hash_mix_string", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[47]);
	return __latency_fn_project_reference_resolution_semantic_hash_mix_int(hash, cast<int_t<>>(__latency_fn_source_buffers_content_hash32(value)));
}

}

namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_project_reference_resolution_semantic_hash_for_reference_contracts(shared_p<ProjectReferenceResolution> references, shared_p<ProjectCallableContractArtifact> contracts) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::semantic_hash_for_reference_contracts", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[48]);
	int_t<> hash = required_cast<int_t<>>(static_cast<int_t<> >(17));
	hash = __latency_fn_project_reference_resolution_semantic_hash_mix_int(hash, cast<int_t<>>(references->reference_count));
	hash = __latency_fn_project_reference_resolution_semantic_hash_mix_int(hash, cast<int_t<>>(references->resolved_count));
	hash = __latency_fn_project_reference_resolution_semantic_hash_mix_int(hash, cast<int_t<>>(references->missing_count));
	hash = __latency_fn_project_reference_resolution_semantic_hash_mix_int(hash, cast<int_t<>>(references->ambiguous_count));
	hash = __latency_fn_project_reference_resolution_semantic_hash_mix_int(hash, cast<int_t<>>(references->unresolved_count));
	auto __latency_local_0 = references->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		hash = __latency_fn_project_reference_resolution_semantic_hash_mix_int(hash, cast<int_t<>>(row->reference_id));
		hash = __latency_fn_project_reference_resolution_semantic_hash_mix_int(hash, cast<int_t<>>(row->from_symbol_id));
		hash = __latency_fn_project_reference_resolution_semantic_hash_mix_int(hash, cast<int_t<>>(row->from_source_unit_id));
		hash = __latency_fn_project_reference_resolution_semantic_hash_mix_int(hash, cast<int_t<>>(row->call_expression_node_id));
		hash = __latency_fn_project_reference_resolution_semantic_hash_mix_int(hash, cast<int_t<>>(row->resolved_symbol_id));
		hash = __latency_fn_project_reference_resolution_semantic_hash_mix_int(hash, cast<int_t<>>(row->resolved_source_unit_id));
		hash = __latency_fn_project_reference_resolution_semantic_hash_mix_int(hash, cast<int_t<>>(row->match_count));
		hash = __latency_fn_project_reference_resolution_semantic_hash_mix_int(hash, cast<int_t<>>(row->resolution_kind_id));
		hash = __latency_fn_project_reference_resolution_semantic_hash_mix_int(hash, cast<int_t<>>(row->status_id));
		hash = __latency_fn_project_reference_resolution_semantic_hash_mix_int(hash, cast<int_t<>>(row->actual_arg_count));
		hash = __latency_fn_project_reference_resolution_semantic_hash_mix_int(hash, cast<int_t<>>(row->first_actual_argument_row_id));
		hash = __latency_fn_project_reference_resolution_semantic_hash_mix_int(hash, cast<int_t<>>(row->actual_argument_row_count));
		hash = __latency_fn_project_reference_resolution_semantic_hash_mix_int(hash, cast<int_t<>>(row->actual_first_argument_type_ref_id));
		hash = __latency_fn_project_reference_resolution_semantic_hash_mix_int(hash, cast<int_t<>>(row->actual_first_argument_status_id));
		hash = __latency_fn_project_reference_resolution_semantic_hash_mix_string(hash, __latency_fn_project_reference_resolution_callee_name(references, row));
		hash = __latency_fn_project_reference_resolution_semantic_hash_mix_string(hash, __latency_fn_project_reference_resolution_resolved_symbol_key(references, row));
		hash = __latency_fn_project_reference_resolution_semantic_hash_mix_string(hash, __latency_fn_project_reference_resolution_resolved_source_unit_key(references, row));
	}
	auto __latency_local_2 = references->actual_arguments;
	for (auto __latency_local_3 : foreach_range(__latency_local_2)) {
		auto argument = __latency_local_3.value_copy();
		hash = __latency_fn_project_reference_resolution_semantic_hash_mix_int(hash, cast<int_t<>>(argument->argument_row_id));
		hash = __latency_fn_project_reference_resolution_semantic_hash_mix_int(hash, cast<int_t<>>(argument->reference_id));
		hash = __latency_fn_project_reference_resolution_semantic_hash_mix_int(hash, cast<int_t<>>(argument->argument_source_row_id));
		hash = __latency_fn_project_reference_resolution_semantic_hash_mix_int(hash, cast<int_t<>>(argument->type_ref_id));
		hash = __latency_fn_project_reference_resolution_semantic_hash_mix_int(hash, cast<int_t<>>(argument->numeric_payload));
		hash = __latency_fn_project_reference_resolution_semantic_hash_mix_int(hash, cast<int_t<>>(argument->position));
		hash = __latency_fn_project_reference_resolution_semantic_hash_mix_int(hash, cast<int_t<>>(argument->literal_status_id));
	}
	hash = __latency_fn_project_reference_resolution_semantic_hash_mix_int(hash, cast<int_t<>>(contracts->contract_count));
	hash = __latency_fn_project_reference_resolution_semantic_hash_mix_int(hash, cast<int_t<>>(contracts->compatible_count));
	hash = __latency_fn_project_reference_resolution_semantic_hash_mix_int(hash, cast<int_t<>>(contracts->blocked_count));
	auto __latency_local_4 = contracts->rows;
	for (auto __latency_local_5 : foreach_range(__latency_local_4)) {
		auto row = __latency_local_5.value_copy();
		hash = __latency_fn_project_reference_resolution_semantic_hash_mix_int(hash, cast<int_t<>>(row->contract_id));
		hash = __latency_fn_project_reference_resolution_semantic_hash_mix_int(hash, cast<int_t<>>(row->reference_id));
		hash = __latency_fn_project_reference_resolution_semantic_hash_mix_int(hash, cast<int_t<>>(row->from_symbol_id));
		hash = __latency_fn_project_reference_resolution_semantic_hash_mix_int(hash, cast<int_t<>>(row->target_symbol_id));
		hash = __latency_fn_project_reference_resolution_semantic_hash_mix_int(hash, cast<int_t<>>(row->target_source_unit_id));
		hash = __latency_fn_project_reference_resolution_semantic_hash_mix_int(hash, cast<int_t<>>(row->expected_arg_count));
		hash = __latency_fn_project_reference_resolution_semantic_hash_mix_int(hash, cast<int_t<>>(row->actual_arg_count));
		hash = __latency_fn_project_reference_resolution_semantic_hash_mix_int(hash, cast<int_t<>>(row->expected_first_parameter_type_ref_id));
		hash = __latency_fn_project_reference_resolution_semantic_hash_mix_int(hash, cast<int_t<>>(row->actual_first_argument_type_ref_id));
		hash = __latency_fn_project_reference_resolution_semantic_hash_mix_int(hash, cast<int_t<>>(row->return_type_ref_id));
		hash = __latency_fn_project_reference_resolution_semantic_hash_mix_int(hash, cast<int_t<>>(row->argument_count_status_id));
		hash = __latency_fn_project_reference_resolution_semantic_hash_mix_int(hash, cast<int_t<>>(row->argument_type_status_id));
		hash = __latency_fn_project_reference_resolution_semantic_hash_mix_int(hash, cast<int_t<>>(row->return_type_status_id));
		hash = __latency_fn_project_reference_resolution_semantic_hash_mix_int(hash, cast<int_t<>>(row->status_id));
		hash = __latency_fn_project_reference_resolution_semantic_hash_mix_int(hash, cast<int_t<>>(row->blocked_reason_id));
	}
	return __latency_fn_structure_row_ids_uint32_from_int(hash);
}

}

#include <scpp/lang/php.hpp>
#include "__types/FrontendDeclarationPayloadRow.hpp"
#include "__types/FrontendExpressionPayloadRow.hpp"
#include "__types/FrontendLiteralPayloadRow.hpp"
#include "__types/FrontendModel.hpp"
#include "__types/FrontendModelKernelCounters.hpp"
#include "__types/FrontendNamePayloadRow.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/FrontendStatementPayloadRow.hpp"
#include "__types/FrontendTypeSyntaxPayloadRow.hpp"
#include "__types/LineStartRow.hpp"
#include "__types/SourceBufferRow.hpp"
#include "__types/SourceRangeExtRow.hpp"
#include "__types/SourceRangeRow.hpp"
#include "__counter/append_call_count.hpp"
#include "__counter/lookup_call_count.hpp"
#include "__counter/materializer_call_count.hpp"
#include "__counter/reserve_call_count.hpp"
#include "__counter/update_call_count.hpp"
#include "__callable/__latency_fn_frontend_model_tables_type_syntax_debug_string.hpp"
#include "__callable/__latency_fn_frontend_model_tables_literal_debug_string.hpp"
#include "__callable/__latency_fn_frontend_model_tables_name_debug_string.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_stable_hash.hpp"
#include "__callable/__latency_fn_frontend_model_tables_statement_stable_hash.hpp"
#include "__callable/__latency_fn_frontend_model_tables_source_buffer_stable_hash.hpp"
#include "__callable/__latency_fn_frontend_model_tables_source_range_stable_hash.hpp"
#include "__callable/__latency_fn_frontend_model_tables_source_range_ext_stable_hash.hpp"
#include "__callable/__latency_fn_frontend_model_tables_line_start_stable_hash.hpp"
#include "__callable/__latency_fn_frontend_model_tables_expression_stable_hash.hpp"
#include "__callable/__latency_fn_frontend_model_tables_type_syntax_stable_hash.hpp"
#include "__callable/__latency_fn_frontend_model_tables_literal_stable_hash.hpp"
#include "__callable/__latency_fn_frontend_model_tables_name_stable_hash.hpp"
#include "__callable/__latency_fn_frontend_model_tables_declaration_stable_hash.hpp"
#include "__callable/__latency_fn_frontend_model_tables_counters_debug_string.hpp"
#include "__callable/__latency_fn_frontend_model_tables_increment_node_family_count.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_declaration_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_expression_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_statement_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_type_syntax_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
string_t __latency_fn_frontend_model_tables_type_syntax_debug_string(FrontendTypeSyntaxPayloadRow row, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::type_syntax_debug_string", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[99]);
	__latency_counter_materializer_call_count(counters) = (__latency_counter_materializer_call_count(counters) + static_cast<int_t<> >(1));
	return (string_t("frontend_type_syntax:") + cast<string_t>(cast<int_t<>>(row->payload_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->base_name_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->type_ref_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->source_range_id)));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
string_t __latency_fn_frontend_model_tables_literal_debug_string(FrontendLiteralPayloadRow row, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::literal_debug_string", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[100]);
	__latency_counter_materializer_call_count(counters) = (__latency_counter_materializer_call_count(counters) + static_cast<int_t<> >(1));
	return (string_t("frontend_literal:") + cast<string_t>(cast<int_t<>>(row->payload_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->literal_kind_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->type_ref_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->source_range_id)));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
string_t __latency_fn_frontend_model_tables_name_debug_string(FrontendNamePayloadRow row, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::name_debug_string", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[101]);
	__latency_counter_materializer_call_count(counters) = (__latency_counter_materializer_call_count(counters) + static_cast<int_t<> >(1));
	return (string_t("frontend_name:") + cast<string_t>(cast<int_t<>>(row->payload_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->name_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->name_role_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->source_range_id)));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_frontend_model_tables_node_stable_hash(FrontendNodeRow row) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::node_stable_hash", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[102]);
	string_t identity = required_cast<string_t>((string_t("frontend_node:v2:") + cast<string_t>(cast<int_t<>>(row->parent_node_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->row_family_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->row_kind_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->payload_kind_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->payload_row_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->source_range_id))));
	return php::stable_hash_string_u64(identity);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_frontend_model_tables_statement_stable_hash(FrontendStatementPayloadRow row) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::statement_stable_hash", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[103]);
	string_t identity = required_cast<string_t>((string_t("frontend_statement:v1:") + cast<string_t>(cast<int_t<>>(row->statement_kind_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->target_node_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->value_node_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->condition_node_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->body_first_node_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->body_last_node_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->else_body_first_node_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->else_body_last_node_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->source_range_id))));
	return php::stable_hash_string_u64(identity);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_frontend_model_tables_source_buffer_stable_hash(SourceBufferRow row) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::source_buffer_stable_hash", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[104]);
	string_t identity = required_cast<string_t>((string_t("source_buffer:v1:") + cast<string_t>(cast<int_t<>>(row->source_unit_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->content_hash)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->byte_length)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->status_id))));
	return php::stable_hash_string_u64(identity);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_frontend_model_tables_source_range_stable_hash(SourceRangeRow row) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::source_range_stable_hash", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[105]);
	string_t identity = required_cast<string_t>((string_t("source_range:v2:") + cast<string_t>(cast<int_t<>>(row->start_offset)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->length)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->flags))));
	return php::stable_hash_string_u64(identity);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_frontend_model_tables_source_range_ext_stable_hash(SourceRangeExtRow row) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::source_range_ext_stable_hash", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[106]);
	string_t identity = required_cast<string_t>((string_t("source_range_ext:v1:") + cast<string_t>(cast<int_t<>>(row->source_range_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->line)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->column)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->extended_length)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->flags))));
	return php::stable_hash_string_u64(identity);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_frontend_model_tables_line_start_stable_hash(LineStartRow row) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::line_start_stable_hash", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[107]);
	string_t identity = required_cast<string_t>((string_t("line_start:v1:") + cast<string_t>(cast<int_t<>>(row->source_buffer_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->line)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->start_offset))));
	return php::stable_hash_string_u64(identity);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_frontend_model_tables_expression_stable_hash(FrontendExpressionPayloadRow row) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::expression_stable_hash", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[108]);
	string_t identity = required_cast<string_t>((string_t("frontend_expression:v1:") + cast<string_t>(cast<int_t<>>(row->expression_kind_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->operator_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->callee_node_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->callee_name_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->left_node_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->right_node_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->third_node_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->first_argument_node_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->argument_count)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->inferred_type_ref_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->source_range_id))));
	return php::stable_hash_string_u64(identity);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_frontend_model_tables_type_syntax_stable_hash(FrontendTypeSyntaxPayloadRow row) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::type_syntax_stable_hash", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[109]);
	string_t identity = required_cast<string_t>((string_t("frontend_type_syntax:v1:") + cast<string_t>(cast<int_t<>>(row->type_syntax_kind_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->base_name_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->type_ref_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->type_family_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->first_type_arg_row_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->type_arg_count)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->value_arg_int)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->source_range_id))));
	return php::stable_hash_string_u64(identity);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_frontend_model_tables_literal_stable_hash(FrontendLiteralPayloadRow row) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::literal_stable_hash", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[110]);
	string_t identity = required_cast<string_t>((string_t("frontend_literal:v1:") + cast<string_t>(cast<int_t<>>(row->literal_kind_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->type_ref_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->numeric_payload)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->literal_status_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->source_range_id))));
	return php::stable_hash_string_u64(identity);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_frontend_model_tables_name_stable_hash(FrontendNamePayloadRow row) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::name_stable_hash", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[111]);
	string_t identity = required_cast<string_t>((string_t("frontend_name:v1:") + cast<string_t>(cast<int_t<>>(row->name_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->source_range_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->name_role_id))));
	return php::stable_hash_string_u64(identity);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_frontend_model_tables_declaration_stable_hash(FrontendDeclarationPayloadRow row) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::declaration_stable_hash", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[112]);
	string_t identity = required_cast<string_t>((string_t("frontend_declaration:v1:") + cast<string_t>(cast<int_t<>>(row->declaration_kind_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->name_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->name_source_range_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->namespace_name_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->namespace_source_range_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->symbol_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->declared_type_ref_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->parameter_list_node_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->body_node_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->body_source_length)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->body_content_hash)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->body_shape_walk_rows)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->source_range_id))));
	return php::stable_hash_string_u64(identity);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
string_t __latency_fn_frontend_model_tables_counters_debug_string(shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::counters_debug_string", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[113]);
	return (string_t("frontend_model_counters:") + cast<string_t>(__latency_counter_reserve_call_count(counters)) + string_t(":") + cast<string_t>(__latency_counter_append_call_count(counters)) + string_t(":") + cast<string_t>(__latency_counter_lookup_call_count(counters)) + string_t(":") + cast<string_t>(__latency_counter_update_call_count(counters)) + string_t(":") + cast<string_t>(__latency_counter_materializer_call_count(counters)));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
void __latency_fn_frontend_model_tables_increment_node_family_count(shared_p<FrontendModel> model, int_t<std::uint16_t> rowFamilyId) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::increment_node_family_count", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[114]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(rowFamilyId), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_declaration_id())))) {
		model->declaration_count = __latency_fn_frontend_model_tables_uint32_from_int((cast<int_t<>>(model->declaration_count) + static_cast<int_t<> >(1)));
	}
	else {
		if (static_cast<bool>(php::identical(cast<int_t<>>(rowFamilyId), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_statement_id())))) {
			model->statement_count = __latency_fn_frontend_model_tables_uint32_from_int((cast<int_t<>>(model->statement_count) + static_cast<int_t<> >(1)));
		}
		else {
			if (static_cast<bool>(php::identical(cast<int_t<>>(rowFamilyId), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_expression_id())))) {
				model->expression_count = __latency_fn_frontend_model_tables_uint32_from_int((cast<int_t<>>(model->expression_count) + static_cast<int_t<> >(1)));
			}
			else {
				if (static_cast<bool>(php::identical(cast<int_t<>>(rowFamilyId), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_type_syntax_id())))) {
					model->type_syntax_count = __latency_fn_frontend_model_tables_uint32_from_int((cast<int_t<>>(model->type_syntax_count) + static_cast<int_t<> >(1)));
				}
			}
		}
	}
}

}

#include <scpp/lang/php.hpp>
#include "__types/FrontendDeclarationPayloadRow.hpp"
#include "__types/FrontendExpressionPayloadRow.hpp"
#include "__types/FrontendLiteralPayloadRow.hpp"
#include "__types/FrontendModelKernelCounters.hpp"
#include "__types/FrontendNamePayloadRow.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/FrontendStatementPayloadRow.hpp"
#include "__types/FrontendTypeSyntaxPayloadRow.hpp"
#include "__types/LineStartRow.hpp"
#include "__types/SourceBufferRow.hpp"
#include "__types/SourceRangeExtRow.hpp"
#include "__types/SourceRangeRow.hpp"
#include "__counter/materializer_call_count.hpp"
#include "__callable/__latency_fn_frontend_model_tables_statement_equals.hpp"
#include "__callable/__latency_fn_frontend_model_tables_expression_equals.hpp"
#include "__callable/__latency_fn_frontend_model_tables_type_syntax_equals.hpp"
#include "__callable/__latency_fn_frontend_model_tables_literal_equals.hpp"
#include "__callable/__latency_fn_frontend_model_tables_name_equals.hpp"
#include "__callable/__latency_fn_frontend_model_tables_source_range_equals.hpp"
#include "__callable/__latency_fn_frontend_model_tables_source_range_ext_equals.hpp"
#include "__callable/__latency_fn_frontend_model_tables_line_start_equals.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_debug_string.hpp"
#include "__callable/__latency_fn_frontend_model_tables_declaration_debug_string.hpp"
#include "__callable/__latency_fn_frontend_model_tables_source_buffer_debug_string.hpp"
#include "__callable/__latency_fn_frontend_model_tables_source_range_debug_string.hpp"
#include "__callable/__latency_fn_frontend_model_tables_source_range_ext_debug_string.hpp"
#include "__callable/__latency_fn_frontend_model_tables_line_start_debug_string.hpp"
#include "__callable/__latency_fn_frontend_model_tables_statement_debug_string.hpp"
#include "__callable/__latency_fn_frontend_model_tables_expression_debug_string.hpp"
namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
bool_t __latency_fn_frontend_model_tables_statement_equals(FrontendStatementPayloadRow left, FrontendStatementPayloadRow right) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::statement_equals", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[83]);
	if (static_cast<bool>(((cast<int_t<>>(left->payload_id) > static_cast<int_t<> >(0)) && (cast<int_t<>>(right->payload_id) > static_cast<int_t<> >(0))))) {
		return bool_t(php::identical(cast<int_t<>>(left->payload_id), cast<int_t<>>(right->payload_id)));
	}
	return ((((((((php::identical(cast<int_t<>>(left->statement_kind_id), cast<int_t<>>(right->statement_kind_id)) && php::identical(cast<int_t<>>(left->target_node_id), cast<int_t<>>(right->target_node_id))) && php::identical(cast<int_t<>>(left->value_node_id), cast<int_t<>>(right->value_node_id))) && php::identical(cast<int_t<>>(left->condition_node_id), cast<int_t<>>(right->condition_node_id))) && php::identical(cast<int_t<>>(left->body_first_node_id), cast<int_t<>>(right->body_first_node_id))) && php::identical(cast<int_t<>>(left->body_last_node_id), cast<int_t<>>(right->body_last_node_id))) && php::identical(cast<int_t<>>(left->else_body_first_node_id), cast<int_t<>>(right->else_body_first_node_id))) && php::identical(cast<int_t<>>(left->else_body_last_node_id), cast<int_t<>>(right->else_body_last_node_id))) && php::identical(cast<int_t<>>(left->source_range_id), cast<int_t<>>(right->source_range_id)));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
bool_t __latency_fn_frontend_model_tables_expression_equals(FrontendExpressionPayloadRow left, FrontendExpressionPayloadRow right) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::expression_equals", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[84]);
	if (static_cast<bool>(((cast<int_t<>>(left->payload_id) > static_cast<int_t<> >(0)) && (cast<int_t<>>(right->payload_id) > static_cast<int_t<> >(0))))) {
		return bool_t(php::identical(cast<int_t<>>(left->payload_id), cast<int_t<>>(right->payload_id)));
	}
	return ((((((((((php::identical(cast<int_t<>>(left->expression_kind_id), cast<int_t<>>(right->expression_kind_id)) && php::identical(cast<int_t<>>(left->operator_id), cast<int_t<>>(right->operator_id))) && php::identical(cast<int_t<>>(left->callee_node_id), cast<int_t<>>(right->callee_node_id))) && php::identical(cast<int_t<>>(left->callee_name_id), cast<int_t<>>(right->callee_name_id))) && php::identical(cast<int_t<>>(left->left_node_id), cast<int_t<>>(right->left_node_id))) && php::identical(cast<int_t<>>(left->right_node_id), cast<int_t<>>(right->right_node_id))) && php::identical(cast<int_t<>>(left->third_node_id), cast<int_t<>>(right->third_node_id))) && php::identical(cast<int_t<>>(left->first_argument_node_id), cast<int_t<>>(right->first_argument_node_id))) && php::identical(cast<int_t<>>(left->argument_count), cast<int_t<>>(right->argument_count))) && php::identical(cast<int_t<>>(left->inferred_type_ref_id), cast<int_t<>>(right->inferred_type_ref_id))) && php::identical(cast<int_t<>>(left->source_range_id), cast<int_t<>>(right->source_range_id)));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
bool_t __latency_fn_frontend_model_tables_type_syntax_equals(FrontendTypeSyntaxPayloadRow left, FrontendTypeSyntaxPayloadRow right) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::type_syntax_equals", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[85]);
	if (static_cast<bool>(((cast<int_t<>>(left->payload_id) > static_cast<int_t<> >(0)) && (cast<int_t<>>(right->payload_id) > static_cast<int_t<> >(0))))) {
		return bool_t(php::identical(cast<int_t<>>(left->payload_id), cast<int_t<>>(right->payload_id)));
	}
	return (((((((php::identical(cast<int_t<>>(left->type_syntax_kind_id), cast<int_t<>>(right->type_syntax_kind_id)) && php::identical(cast<int_t<>>(left->base_name_id), cast<int_t<>>(right->base_name_id))) && php::identical(cast<int_t<>>(left->type_ref_id), cast<int_t<>>(right->type_ref_id))) && php::identical(cast<int_t<>>(left->type_family_id), cast<int_t<>>(right->type_family_id))) && php::identical(cast<int_t<>>(left->first_type_arg_row_id), cast<int_t<>>(right->first_type_arg_row_id))) && php::identical(cast<int_t<>>(left->type_arg_count), cast<int_t<>>(right->type_arg_count))) && php::identical(cast<int_t<>>(left->value_arg_int), cast<int_t<>>(right->value_arg_int))) && php::identical(cast<int_t<>>(left->source_range_id), cast<int_t<>>(right->source_range_id)));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
bool_t __latency_fn_frontend_model_tables_literal_equals(FrontendLiteralPayloadRow left, FrontendLiteralPayloadRow right) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::literal_equals", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[86]);
	if (static_cast<bool>(((cast<int_t<>>(left->payload_id) > static_cast<int_t<> >(0)) && (cast<int_t<>>(right->payload_id) > static_cast<int_t<> >(0))))) {
		return bool_t(php::identical(cast<int_t<>>(left->payload_id), cast<int_t<>>(right->payload_id)));
	}
	return ((((php::identical(cast<int_t<>>(left->literal_kind_id), cast<int_t<>>(right->literal_kind_id)) && php::identical(cast<int_t<>>(left->type_ref_id), cast<int_t<>>(right->type_ref_id))) && php::identical(cast<int_t<>>(left->numeric_payload), cast<int_t<>>(right->numeric_payload))) && php::identical(cast<int_t<>>(left->literal_status_id), cast<int_t<>>(right->literal_status_id))) && php::identical(cast<int_t<>>(left->source_range_id), cast<int_t<>>(right->source_range_id)));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
bool_t __latency_fn_frontend_model_tables_name_equals(FrontendNamePayloadRow left, FrontendNamePayloadRow right) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::name_equals", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[87]);
	if (static_cast<bool>(((cast<int_t<>>(left->payload_id) > static_cast<int_t<> >(0)) && (cast<int_t<>>(right->payload_id) > static_cast<int_t<> >(0))))) {
		return bool_t(php::identical(cast<int_t<>>(left->payload_id), cast<int_t<>>(right->payload_id)));
	}
	return ((php::identical(cast<int_t<>>(left->name_id), cast<int_t<>>(right->name_id)) && php::identical(cast<int_t<>>(left->source_range_id), cast<int_t<>>(right->source_range_id))) && php::identical(cast<int_t<>>(left->name_role_id), cast<int_t<>>(right->name_role_id)));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
bool_t __latency_fn_frontend_model_tables_source_range_equals(SourceRangeRow left, SourceRangeRow right) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::source_range_equals", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[88]);
	if (static_cast<bool>(((cast<int_t<>>(left->source_range_id) > static_cast<int_t<> >(0)) && (cast<int_t<>>(right->source_range_id) > static_cast<int_t<> >(0))))) {
		return bool_t(php::identical(cast<int_t<>>(left->source_range_id), cast<int_t<>>(right->source_range_id)));
	}
	return (php::identical(cast<int_t<>>(left->start_offset), cast<int_t<>>(right->start_offset)) && php::identical(cast<int_t<>>(left->length), cast<int_t<>>(right->length)));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
bool_t __latency_fn_frontend_model_tables_source_range_ext_equals(SourceRangeExtRow left, SourceRangeExtRow right) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::source_range_ext_equals", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[89]);
	return (((php::identical(cast<int_t<>>(left->source_range_id), cast<int_t<>>(right->source_range_id)) && php::identical(cast<int_t<>>(left->line), cast<int_t<>>(right->line))) && php::identical(cast<int_t<>>(left->column), cast<int_t<>>(right->column))) && php::identical(cast<int_t<>>(left->extended_length), cast<int_t<>>(right->extended_length)));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
bool_t __latency_fn_frontend_model_tables_line_start_equals(LineStartRow left, LineStartRow right) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::line_start_equals", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[90]);
	return ((php::identical(cast<int_t<>>(left->source_buffer_id), cast<int_t<>>(right->source_buffer_id)) && php::identical(cast<int_t<>>(left->line), cast<int_t<>>(right->line))) && php::identical(cast<int_t<>>(left->start_offset), cast<int_t<>>(right->start_offset)));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
string_t __latency_fn_frontend_model_tables_node_debug_string(FrontendNodeRow row, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::node_debug_string", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[91]);
	__latency_counter_materializer_call_count(counters) = (__latency_counter_materializer_call_count(counters) + static_cast<int_t<> >(1));
	return (string_t("frontend_node:") + cast<string_t>(cast<int_t<>>(row->node_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->row_family_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->row_kind_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->source_range_id)));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
string_t __latency_fn_frontend_model_tables_declaration_debug_string(FrontendDeclarationPayloadRow row, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::declaration_debug_string", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[92]);
	__latency_counter_materializer_call_count(counters) = (__latency_counter_materializer_call_count(counters) + static_cast<int_t<> >(1));
	return (string_t("frontend_declaration:") + cast<string_t>(cast<int_t<>>(row->payload_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->name_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->symbol_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->declared_type_ref_id)));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
string_t __latency_fn_frontend_model_tables_source_buffer_debug_string(SourceBufferRow row, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::source_buffer_debug_string", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[93]);
	__latency_counter_materializer_call_count(counters) = (__latency_counter_materializer_call_count(counters) + static_cast<int_t<> >(1));
	return (string_t("source_buffer:") + cast<string_t>(cast<int_t<>>(row->source_buffer_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->source_unit_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->byte_length)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->status_id)));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
string_t __latency_fn_frontend_model_tables_source_range_debug_string(SourceRangeRow row, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::source_range_debug_string", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[94]);
	__latency_counter_materializer_call_count(counters) = (__latency_counter_materializer_call_count(counters) + static_cast<int_t<> >(1));
	return (string_t("source_range:") + cast<string_t>(cast<int_t<>>(row->source_range_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->start_offset)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->length)));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
string_t __latency_fn_frontend_model_tables_source_range_ext_debug_string(SourceRangeExtRow row, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::source_range_ext_debug_string", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[95]);
	__latency_counter_materializer_call_count(counters) = (__latency_counter_materializer_call_count(counters) + static_cast<int_t<> >(1));
	return (string_t("source_range_ext:") + cast<string_t>(cast<int_t<>>(row->source_range_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->line)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->column)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->extended_length)));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
string_t __latency_fn_frontend_model_tables_line_start_debug_string(LineStartRow row, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::line_start_debug_string", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[96]);
	__latency_counter_materializer_call_count(counters) = (__latency_counter_materializer_call_count(counters) + static_cast<int_t<> >(1));
	return (string_t("line_start:") + cast<string_t>(cast<int_t<>>(row->source_buffer_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->line)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->start_offset)));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
string_t __latency_fn_frontend_model_tables_statement_debug_string(FrontendStatementPayloadRow row, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::statement_debug_string", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[97]);
	__latency_counter_materializer_call_count(counters) = (__latency_counter_materializer_call_count(counters) + static_cast<int_t<> >(1));
	return (string_t("frontend_statement:") + cast<string_t>(cast<int_t<>>(row->payload_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->statement_kind_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->body_first_node_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->else_body_first_node_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->source_range_id)));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
string_t __latency_fn_frontend_model_tables_expression_debug_string(FrontendExpressionPayloadRow row, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::expression_debug_string", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[98]);
	__latency_counter_materializer_call_count(counters) = (__latency_counter_materializer_call_count(counters) + static_cast<int_t<> >(1));
	return (string_t("frontend_expression:") + cast<string_t>(cast<int_t<>>(row->payload_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->expression_kind_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->operator_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->source_range_id)));
}

}

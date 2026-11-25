#include <scpp/lang/php.hpp>
#include "__types/FrontendDeclarationPayloadRow.hpp"
#include "__types/FrontendModel.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/FrontendNodeSpan.hpp"
#include "__callable/__latency_fn_frontend_model_tables_decrement_node_family_count.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_declaration_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_expression_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_statement_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_type_syntax_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_frontend_model_tables_decrement_node_family_count.hpp"
#include "__callable/__latency_fn_frontend_model_tables_increment_node_family_count.hpp"
#include "__callable/__latency_fn_frontend_model_tables_replace_node_family_count.hpp"
#include "__callable/__latency_fn_frontend_model_tables_ensure_declaration_node_id_slot.hpp"
#include "__callable/__latency_fn_frontend_model_tables_first_node_span.hpp"
#include "__callable/__latency_fn_frontend_model_tables_next_node_span.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_span_is_empty.hpp"
#include "__callable/__latency_fn_frontend_model_tables_record_declaration_node_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_refresh_node_family_counts.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_declaration_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_expression_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_statement_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_row_family_type_syntax_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_span_node_at.hpp"
#include "__callable/__latency_fn_frontend_model_tables_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
void __latency_fn_frontend_model_tables_decrement_node_family_count(shared_p<FrontendModel> model, int_t<std::uint16_t> rowFamilyId) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::decrement_node_family_count", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[115]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(rowFamilyId), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_declaration_id())))) {
		if (static_cast<bool>((cast<int_t<>>(model->declaration_count) > static_cast<int_t<> >(0)))) {
			model->declaration_count = __latency_fn_frontend_model_tables_uint32_from_int((cast<int_t<>>(model->declaration_count) - static_cast<int_t<> >(1)));
		}
	}
	else {
		if (static_cast<bool>(php::identical(cast<int_t<>>(rowFamilyId), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_statement_id())))) {
			if (static_cast<bool>((cast<int_t<>>(model->statement_count) > static_cast<int_t<> >(0)))) {
				model->statement_count = __latency_fn_frontend_model_tables_uint32_from_int((cast<int_t<>>(model->statement_count) - static_cast<int_t<> >(1)));
			}
		}
		else {
			if (static_cast<bool>(php::identical(cast<int_t<>>(rowFamilyId), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_expression_id())))) {
				if (static_cast<bool>((cast<int_t<>>(model->expression_count) > static_cast<int_t<> >(0)))) {
					model->expression_count = __latency_fn_frontend_model_tables_uint32_from_int((cast<int_t<>>(model->expression_count) - static_cast<int_t<> >(1)));
				}
			}
			else {
				if (static_cast<bool>(php::identical(cast<int_t<>>(rowFamilyId), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_type_syntax_id())))) {
					if (static_cast<bool>((cast<int_t<>>(model->type_syntax_count) > static_cast<int_t<> >(0)))) {
						model->type_syntax_count = __latency_fn_frontend_model_tables_uint32_from_int((cast<int_t<>>(model->type_syntax_count) - static_cast<int_t<> >(1)));
					}
				}
			}
		}
	}
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
void __latency_fn_frontend_model_tables_replace_node_family_count(shared_p<FrontendModel> model, int_t<std::uint16_t> previousFamilyId, int_t<std::uint16_t> nextFamilyId) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::replace_node_family_count", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[116]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(previousFamilyId), cast<int_t<>>(nextFamilyId)))) {
		return;
	}
	__latency_fn_frontend_model_tables_decrement_node_family_count(model, cast<int_t<std::uint16_t>>(previousFamilyId));
	__latency_fn_frontend_model_tables_increment_node_family_count(model, cast<int_t<std::uint16_t>>(nextFamilyId));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_tables[]; }
namespace scpp {
void __latency_fn_frontend_model_tables_refresh_node_family_counts(shared_p<FrontendModel> model) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_tables::refresh_node_family_counts", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_model_tables.phs", __latency_lines_frontend_model_tables[117]);
	int_t<> declarations = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> statements = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> expressions = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> typeSyntaxes = required_cast<int_t<>>(static_cast<int_t<> >(0));
	vector_t<int_t<std::uint32_t>> emptyDeclarationNodeIds = {};
	model->declaration_node_ids = emptyDeclarationNodeIds;
	auto __latency_local_0 = model->declarations;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto declaration = __latency_local_1.value_copy();
		__latency_fn_frontend_model_tables_ensure_declaration_node_id_slot(model, declaration->payload_id);
	}
	FrontendNodeSpan span = __latency_fn_frontend_model_tables_first_node_span(model);
	while (static_cast<bool>((!__latency_fn_frontend_model_tables_node_span_is_empty(span)))) {
		int_t<> offset = required_cast<int_t<>>(static_cast<int_t<> >(0));
		while (static_cast<bool>((offset < cast<int_t<>>(span->count)))) {
			FrontendNodeRow row = __latency_fn_frontend_model_tables_span_node_at(model, span, offset);
			if (static_cast<bool>(php::identical(cast<int_t<>>(row->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_declaration_id())))) {
				declarations = (declarations + static_cast<int_t<> >(1));
			}
			else {
				if (static_cast<bool>(php::identical(cast<int_t<>>(row->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_statement_id())))) {
					statements = (statements + static_cast<int_t<> >(1));
				}
				else {
					if (static_cast<bool>(php::identical(cast<int_t<>>(row->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_expression_id())))) {
						expressions = (expressions + static_cast<int_t<> >(1));
					}
					else {
						if (static_cast<bool>(php::identical(cast<int_t<>>(row->row_family_id), cast<int_t<>>(__latency_fn_frontend_model_tables_row_family_type_syntax_id())))) {
							typeSyntaxes = (typeSyntaxes + static_cast<int_t<> >(1));
						}
					}
				}
			}
			__latency_fn_frontend_model_tables_record_declaration_node_id(model, row, row->node_id);
			offset = (offset + static_cast<int_t<> >(1));
		}
		span = __latency_fn_frontend_model_tables_next_node_span(model, span);
	}
	model->declaration_count = __latency_fn_frontend_model_tables_uint32_from_int(declarations);
	model->statement_count = __latency_fn_frontend_model_tables_uint32_from_int(statements);
	model->expression_count = __latency_fn_frontend_model_tables_uint32_from_int(expressions);
	model->type_syntax_count = __latency_fn_frontend_model_tables_uint32_from_int(typeSyntaxes);
}

}

#include <scpp/lang/php.hpp>
#include "__types/FrontendBodySummaryArtifact.hpp"
#include "__types/FrontendBodySummaryRow.hpp"
#include "__types/FrontendDeclarationPayloadRow.hpp"
#include "__types/FrontendModel.hpp"
#include "__types/FrontendModelKernelCounters.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__counter/create.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_append_statement.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_from_declaration.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_new_artifact.hpp"
#include "__callable/__latency_fn_frontend_model_tables_declaration_node_id_by_payload_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_debug_string.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_stable_hash.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint64_from_int.hpp"
#include "__callable/__latency_fn_type_capability_readiness_semantic_hash_mix_int.hpp"
namespace scpp { extern const int __latency_lines_frontend_body_summaries[]; }
namespace scpp {
shared_p<FrontendBodySummaryArtifact> __latency_fn_frontend_body_summaries_from_declaration(shared_p<FrontendModel> model, const string_t& sourceText, FrontendDeclarationPayloadRow declaration) {
	SCPP_CALL_DEPTH_GUARD("frontend_body_summaries::from_declaration", "/tmp/scpp-edit-latency-20260919/app/compile/model/frontend_body_summaries.phs", __latency_lines_frontend_body_summaries[37]);
	shared_p<FrontendModelKernelCounters> counters = __latency_counter_create();
	int_t<std::uint32_t> declarationNodeId = required_cast<int_t<std::uint32_t>>(__latency_fn_frontend_model_tables_declaration_node_id_by_payload_id(model, declaration->payload_id));
	shared_p<FrontendBodySummaryArtifact> artifact = __latency_fn_frontend_body_summaries_new_artifact(model->source_unit_id, cast<int_t<std::uint32_t>>(declarationNodeId), (cast<int_t<>>(model->statement_count) + static_cast<int_t<> >(4)));
	if (static_cast<bool>(((cast<int_t<>>(model->parser_error_count) > static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(declaration->body_node_id), static_cast<int_t<> >(0))))) {
		return artifact;
	}
	int_t<std::uint32_t> bodyNodeId = required_cast<int_t<std::uint32_t>>(declaration->body_node_id);
	while (static_cast<bool>((cast<int_t<>>(bodyNodeId) > static_cast<int_t<> >(0)))) {
		FrontendNodeRow statementNode = __latency_fn_frontend_model_tables_node_by_id(model, bodyNodeId, counters);
		if (static_cast<bool>((!__latency_fn_frontend_body_summaries_append_statement(artifact, model, sourceText, cast<int_t<std::uint32_t>>(declarationNodeId), __latency_fn_structure_row_ids_none_id(), statementNode, bool_t(static_cast<bool_t>(true)), counters)))) {
			return artifact;
		}
		bodyNodeId = statementNode->next_sibling_node_id;
	}
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_frontend_body_summaries[]; }
namespace scpp {
string_t __latency_fn_frontend_body_summaries_debug_string(shared_p<FrontendBodySummaryArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("frontend_body_summaries::debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/model/frontend_body_summaries.phs", __latency_lines_frontend_body_summaries[38]);
	return (string_t("frontend_body_summary:rows=") + cast<string_t>(cast<int_t<>>(artifact->row_count)) + string_t(":locals=") + cast<string_t>(cast<int_t<>>(artifact->local_count)) + string_t(":conditions=") + cast<string_t>(cast<int_t<>>(artifact->condition_count)) + string_t(":dependencies=") + cast<string_t>(cast<int_t<>>(artifact->expression_dependency_count)) + string_t(":blocked=") + cast<string_t>(cast<int_t<>>(artifact->blocked_count)));
}

}

namespace scpp { extern const int __latency_lines_frontend_body_summaries[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_frontend_body_summaries_stable_hash(shared_p<FrontendBodySummaryArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("frontend_body_summaries::stable_hash", "/tmp/scpp-edit-latency-20260919/app/compile/model/frontend_body_summaries.phs", __latency_lines_frontend_body_summaries[39]);
	int_t<> hash = required_cast<int_t<>>(static_cast<int_t<> >(17));
	hash = __latency_fn_type_capability_readiness_semantic_hash_mix_int(hash, cast<int_t<>>(artifact->source_unit_id));
	hash = __latency_fn_type_capability_readiness_semantic_hash_mix_int(hash, cast<int_t<>>(artifact->declaration_node_id));
	hash = __latency_fn_type_capability_readiness_semantic_hash_mix_int(hash, cast<int_t<>>(artifact->row_count));
	hash = __latency_fn_type_capability_readiness_semantic_hash_mix_int(hash, cast<int_t<>>(artifact->local_count));
	hash = __latency_fn_type_capability_readiness_semantic_hash_mix_int(hash, cast<int_t<>>(artifact->condition_count));
	auto __latency_local_0 = artifact->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		hash = __latency_fn_type_capability_readiness_semantic_hash_mix_int(hash, cast<int_t<>>(row->summary_kind_id));
		hash = __latency_fn_type_capability_readiness_semantic_hash_mix_int(hash, cast<int_t<>>(row->statement_node_id));
		hash = __latency_fn_type_capability_readiness_semantic_hash_mix_int(hash, cast<int_t<>>(row->target_local_id));
		hash = __latency_fn_type_capability_readiness_semantic_hash_mix_int(hash, cast<int_t<>>(row->value_type_ref_id));
		hash = __latency_fn_type_capability_readiness_semantic_hash_mix_int(hash, cast<int_t<>>(row->condition_type_ref_id));
	}
	return __latency_fn_structure_row_ids_uint64_from_int(hash);
}

}

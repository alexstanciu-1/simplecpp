#include <scpp/lang/php.hpp>
#include "__types/BackendEmissionDecisionArtifact.hpp"
#include "__types/BackendRequestAuthorizationArtifact.hpp"
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/ControlFlowGraphArtifact.hpp"
#include "__types/FrontendModel.hpp"
#include "__types/FunctionBodyTextEmissionPreflightArtifact.hpp"
#include "__types/LoweringPlan.hpp"
#include "__types/PipelineConfig.hpp"
#include "__types/ProjectSymbolIndex.hpp"
#include "__types/SourceUnitTableRow.hpp"
#include "__types/TokenStream.hpp"
#include "__types/compiler_export_artifacts.hpp"
#include "__callable/__latency_fn_compiler_export_artifacts_append_tokens_tsv__exec.hpp"
#include "__callable/__latency_fn_pure_tdd_stage_artifacts_append_tokens_tsv.hpp"
#include "__callable/__latency_fn_compiler_export_artifacts_append_parse_tsv__exec.hpp"
#include "__callable/__latency_fn_pure_tdd_stage_artifacts_append_parse_tsv.hpp"
#include "__callable/__latency_fn_compiler_export_artifacts_write_stage_text.hpp"
#include "__callable/__latency_fn_pure_tdd_stage_artifacts_write_text.hpp"
#include "__callable/__latency_fn_compiler_export_artifacts_parse_resolve_tsv.hpp"
#include "__callable/__latency_fn_pure_tdd_stage_artifacts_parse_resolve_tsv.hpp"
#include "__callable/__latency_fn_compiler_export_artifacts_cfg_tsv.hpp"
#include "__callable/__latency_fn_control_flow_graphs_tsv.hpp"
#include "__callable/__latency_fn_compiler_export_artifacts_project_cfg_tsv.hpp"
#include "__callable/__latency_fn_control_flow_graphs_project_tsv.hpp"
#include "__callable/__latency_fn_compiler_export_artifacts_branch_dataflow_tsv.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_project_tsv.hpp"
#include "__callable/__latency_fn_compiler_export_artifacts_control_transfer_tsv.hpp"
#include "__callable/__latency_fn_control_flow_transfers_project_tsv.hpp"
#include "__callable/__latency_fn_compiler_export_artifacts_lowering_tsv.hpp"
#include "__callable/__latency_fn_pure_tdd_stage_artifacts_lowering_tsv.hpp"
#include "__callable/__latency_fn_compiler_execution_artifacts_write_execution_jsons_if_requested.hpp"
#include "__callable/__latency_fn_compiler_export_artifacts_write_execution_jsons_if_requested.hpp"
namespace scpp { extern const int __latency_lines_compiler_export_artifacts[]; }
namespace scpp {
bool_t compiler_export_artifacts::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == compiler_export_artifacts::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_compiler_export_artifacts[]; }
namespace scpp {
void __latency_fn_compiler_export_artifacts_append_tokens_tsv__exec(string_t& text, SourceUnitTableRow sourceUnit, shared_p<TokenStream> tokens) {
	__latency_fn_pure_tdd_stage_artifacts_append_tokens_tsv(text, sourceUnit, tokens);
}

}

namespace scpp { extern const int __latency_lines_compiler_export_artifacts[]; }
namespace scpp {
void __latency_fn_compiler_export_artifacts_append_parse_tsv__exec(string_t& text, SourceUnitTableRow sourceUnit, shared_p<FrontendModel> model) {
	__latency_fn_pure_tdd_stage_artifacts_append_parse_tsv(text, sourceUnit, model);
}

}

namespace scpp { extern const int __latency_lines_compiler_export_artifacts[]; }
namespace scpp {
bool_t __latency_fn_compiler_export_artifacts_write_stage_text(shared_p<PipelineConfig> config, const string_t& fileName, const string_t& text) {
	SCPP_CALL_DEPTH_GUARD("compiler_export_artifacts::write_stage_text", "/tmp/scpp-edit-latency-20260919/app/compile/support/compiler_export_artifacts.phs", __latency_lines_compiler_export_artifacts[0]);
	return __latency_fn_pure_tdd_stage_artifacts_write_text(config, fileName, text);
}

}

namespace scpp { extern const int __latency_lines_compiler_export_artifacts[]; }
namespace scpp {
string_t __latency_fn_compiler_export_artifacts_parse_resolve_tsv(shared_p<ProjectSymbolIndex> symbols) {
	SCPP_CALL_DEPTH_GUARD("compiler_export_artifacts::parse_resolve_tsv", "/tmp/scpp-edit-latency-20260919/app/compile/support/compiler_export_artifacts.phs", __latency_lines_compiler_export_artifacts[1]);
	return __latency_fn_pure_tdd_stage_artifacts_parse_resolve_tsv(symbols);
}

}

namespace scpp { extern const int __latency_lines_compiler_export_artifacts[]; }
namespace scpp {
string_t __latency_fn_compiler_export_artifacts_cfg_tsv(shared_p<ControlFlowGraphArtifact> cfg) {
	SCPP_CALL_DEPTH_GUARD("compiler_export_artifacts::cfg_tsv", "/tmp/scpp-edit-latency-20260919/app/compile/support/compiler_export_artifacts.phs", __latency_lines_compiler_export_artifacts[2]);
	return __latency_fn_control_flow_graphs_tsv(cfg);
}

}

namespace scpp { extern const int __latency_lines_compiler_export_artifacts[]; }
namespace scpp {
string_t __latency_fn_compiler_export_artifacts_project_cfg_tsv(shared_p<ProjectSymbolIndex> symbols, shared_p<FrontendModel> model) {
	SCPP_CALL_DEPTH_GUARD("compiler_export_artifacts::project_cfg_tsv", "/tmp/scpp-edit-latency-20260919/app/compile/support/compiler_export_artifacts.phs", __latency_lines_compiler_export_artifacts[3]);
	return __latency_fn_control_flow_graphs_project_tsv(symbols, model);
}

}

namespace scpp { extern const int __latency_lines_compiler_export_artifacts[]; }
namespace scpp {
string_t __latency_fn_compiler_export_artifacts_branch_dataflow_tsv(shared_p<ProjectSymbolIndex> symbols, shared_p<FrontendModel> model, const string_t& sourceText) {
	SCPP_CALL_DEPTH_GUARD("compiler_export_artifacts::branch_dataflow_tsv", "/tmp/scpp-edit-latency-20260919/app/compile/support/compiler_export_artifacts.phs", __latency_lines_compiler_export_artifacts[4]);
	return __latency_fn_control_flow_dataflows_project_tsv(symbols, model, sourceText);
}

}

namespace scpp { extern const int __latency_lines_compiler_export_artifacts[]; }
namespace scpp {
string_t __latency_fn_compiler_export_artifacts_control_transfer_tsv(shared_p<ProjectSymbolIndex> symbols, shared_p<FrontendModel> model) {
	SCPP_CALL_DEPTH_GUARD("compiler_export_artifacts::control_transfer_tsv", "/tmp/scpp-edit-latency-20260919/app/compile/support/compiler_export_artifacts.phs", __latency_lines_compiler_export_artifacts[5]);
	return __latency_fn_control_flow_transfers_project_tsv(symbols, model);
}

}

namespace scpp { extern const int __latency_lines_compiler_export_artifacts[]; }
namespace scpp {
string_t __latency_fn_compiler_export_artifacts_lowering_tsv(shared_p<LoweringPlan> plan, shared_p<BackendRequestAuthorizationArtifact> requests, BackendEmissionDecisionArtifact emission, FunctionBodyTextEmissionPreflightArtifact preflight) {
	SCPP_CALL_DEPTH_GUARD("compiler_export_artifacts::lowering_tsv", "/tmp/scpp-edit-latency-20260919/app/compile/support/compiler_export_artifacts.phs", __latency_lines_compiler_export_artifacts[6]);
	return __latency_fn_pure_tdd_stage_artifacts_lowering_tsv(plan, requests, emission, preflight);
}

}

namespace scpp { extern const int __latency_lines_compiler_export_artifacts[]; }
namespace scpp {
bool_t __latency_fn_compiler_export_artifacts_write_execution_jsons_if_requested(shared_p<CompilerProjectRunReport> report) {
	SCPP_CALL_DEPTH_GUARD("compiler_export_artifacts::write_execution_jsons_if_requested", "/tmp/scpp-edit-latency-20260919/app/compile/support/compiler_export_artifacts.phs", __latency_lines_compiler_export_artifacts[8]);
	return __latency_fn_compiler_execution_artifacts_write_execution_jsons_if_requested(report);
}

}

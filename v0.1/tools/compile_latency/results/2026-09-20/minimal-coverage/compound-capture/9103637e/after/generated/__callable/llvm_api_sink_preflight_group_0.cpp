#include <scpp/lang/php.hpp>
#include "__types/LlvmApiHandleMapSummaryRow.hpp"
#include "__types/LlvmApiModuleBuildRow.hpp"
#include "__types/LlvmApiSinkPreflightArtifact.hpp"
#include "__types/LlvmApiSinkPreflightRow.hpp"
#include "__types/llvm_api_sink_preflight.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_artifact_kind_llvm_api_sink_preflight_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_source_model_backend_emission_decision_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_preflight_model_llvm_api_sink_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_sink_id_llvm_api_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_sink_mode_row_only_preflight_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_status_blocked_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_blocked_reason_lowering_plan_blocked_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_blocked_reason_no_emission_decisions_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_status_blocked_id.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_status_name.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_status_ready_id.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_blocked_reason_lowering_plan_blocked_id.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_blocked_reason_name.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_blocked_reason_no_emission_decisions_id.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_artifact_kind_llvm_api_sink_preflight_id.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_new_artifact.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_preflight_model_llvm_api_sink_id.hpp"
#include "__callable/__latency_fn_llvm_api_sink_preflight_source_model_backend_emission_decision_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
namespace scpp { extern const int __latency_lines_llvm_api_sink_preflight[]; }
namespace scpp {
bool_t llvm_api_sink_preflight::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == llvm_api_sink_preflight::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_llvm_api_sink_preflight[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_llvm_api_sink_preflight_artifact_kind_llvm_api_sink_preflight_id() {
	SCPP_CALL_DEPTH_GUARD("llvm_api_sink_preflight::artifact_kind_llvm_api_sink_preflight_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_api_sink_preflight.phs", __latency_lines_llvm_api_sink_preflight[0]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_llvm_api_sink_preflight[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_llvm_api_sink_preflight_source_model_backend_emission_decision_id() {
	SCPP_CALL_DEPTH_GUARD("llvm_api_sink_preflight::source_model_backend_emission_decision_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_api_sink_preflight.phs", __latency_lines_llvm_api_sink_preflight[1]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_llvm_api_sink_preflight[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_llvm_api_sink_preflight_preflight_model_llvm_api_sink_id() {
	SCPP_CALL_DEPTH_GUARD("llvm_api_sink_preflight::preflight_model_llvm_api_sink_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_api_sink_preflight.phs", __latency_lines_llvm_api_sink_preflight[2]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_llvm_api_sink_preflight[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_llvm_api_sink_preflight_sink_id_llvm_api_id() {
	SCPP_CALL_DEPTH_GUARD("llvm_api_sink_preflight::sink_id_llvm_api_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_api_sink_preflight.phs", __latency_lines_llvm_api_sink_preflight[3]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_llvm_api_sink_preflight[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_llvm_api_sink_preflight_sink_mode_row_only_preflight_id() {
	SCPP_CALL_DEPTH_GUARD("llvm_api_sink_preflight::sink_mode_row_only_preflight_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_api_sink_preflight.phs", __latency_lines_llvm_api_sink_preflight[4]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_llvm_api_sink_preflight[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_llvm_api_sink_preflight_status_ready_id() {
	SCPP_CALL_DEPTH_GUARD("llvm_api_sink_preflight::status_ready_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_api_sink_preflight.phs", __latency_lines_llvm_api_sink_preflight[5]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_llvm_api_sink_preflight[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_llvm_api_sink_preflight_status_blocked_id() {
	SCPP_CALL_DEPTH_GUARD("llvm_api_sink_preflight::status_blocked_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_api_sink_preflight.phs", __latency_lines_llvm_api_sink_preflight[6]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_llvm_api_sink_preflight[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_llvm_api_sink_preflight_blocked_reason_none_id() {
	SCPP_CALL_DEPTH_GUARD("llvm_api_sink_preflight::blocked_reason_none_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_api_sink_preflight.phs", __latency_lines_llvm_api_sink_preflight[7]);
	return __latency_fn_structure_row_ids_none_kind_id();
}

}

namespace scpp { extern const int __latency_lines_llvm_api_sink_preflight[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_llvm_api_sink_preflight_blocked_reason_lowering_plan_blocked_id() {
	SCPP_CALL_DEPTH_GUARD("llvm_api_sink_preflight::blocked_reason_lowering_plan_blocked_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_api_sink_preflight.phs", __latency_lines_llvm_api_sink_preflight[8]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_llvm_api_sink_preflight[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_llvm_api_sink_preflight_blocked_reason_no_emission_decisions_id() {
	SCPP_CALL_DEPTH_GUARD("llvm_api_sink_preflight::blocked_reason_no_emission_decisions_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_api_sink_preflight.phs", __latency_lines_llvm_api_sink_preflight[9]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_llvm_api_sink_preflight[]; }
namespace scpp {
string_t __latency_fn_llvm_api_sink_preflight_status_name(int_t<std::uint16_t> id) {
	SCPP_CALL_DEPTH_GUARD("llvm_api_sink_preflight::status_name", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_api_sink_preflight.phs", __latency_lines_llvm_api_sink_preflight[10]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_llvm_api_sink_preflight_status_ready_id())))) {
		return string_t("ready");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_llvm_api_sink_preflight_status_blocked_id())))) {
		return string_t("blocked");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_llvm_api_sink_preflight[]; }
namespace scpp {
string_t __latency_fn_llvm_api_sink_preflight_blocked_reason_name(int_t<std::uint16_t> id) {
	SCPP_CALL_DEPTH_GUARD("llvm_api_sink_preflight::blocked_reason_name", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_api_sink_preflight.phs", __latency_lines_llvm_api_sink_preflight[11]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_llvm_api_sink_preflight_blocked_reason_none_id())))) {
		return string_t("");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_llvm_api_sink_preflight_blocked_reason_lowering_plan_blocked_id())))) {
		return string_t("lowering_plan_blocked");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_llvm_api_sink_preflight_blocked_reason_no_emission_decisions_id())))) {
		return string_t("no_emission_decisions");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_llvm_api_sink_preflight[]; }
namespace scpp {
LlvmApiSinkPreflightArtifact __latency_fn_llvm_api_sink_preflight_new_artifact(int_t<> rowCapacity) {
	SCPP_CALL_DEPTH_GUARD("llvm_api_sink_preflight::new_artifact", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_api_sink_preflight.phs", __latency_lines_llvm_api_sink_preflight[12]);
	LlvmApiSinkPreflightArtifact artifact = LlvmApiSinkPreflightArtifact{};
	artifact->artifact_kind_id = __latency_fn_llvm_api_sink_preflight_artifact_kind_llvm_api_sink_preflight_id();
	artifact->schema_version = __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
	artifact->source_model_id = __latency_fn_llvm_api_sink_preflight_source_model_backend_emission_decision_id();
	artifact->preflight_model_id = __latency_fn_llvm_api_sink_preflight_preflight_model_llvm_api_sink_id();
	php::vector_reserve(artifact->preflights, rowCapacity);
	php::vector_reserve(artifact->module_builds, rowCapacity);
	php::vector_reserve(artifact->handle_summaries, rowCapacity);
	return artifact;
}

}

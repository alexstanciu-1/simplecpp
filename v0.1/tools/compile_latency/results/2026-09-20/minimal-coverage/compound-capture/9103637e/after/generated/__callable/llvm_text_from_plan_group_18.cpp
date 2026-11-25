#include <scpp/lang/php.hpp>
#include "__types/BackendSinkBoundaryRow.hpp"
#include "__types/FunctionBodyTextEmissionPreflightRow.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_sink_debug_string.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_sink_kind_name.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_status_name.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_preflight_debug_string.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_status_name.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_sink_stable_hash.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_preflight_stable_hash.hpp"
namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
string_t __latency_fn_llvm_text_from_plan_sink_debug_string(BackendSinkBoundaryRow row) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::sink_debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[161]);
	string_t text = required_cast<string_t>(string_t("backend_sink:"));
	text = (cast<string_t>(text) + cast<string_t>(__latency_fn_llvm_text_from_plan_sink_kind_name(row->sink_kind_id)));
	text = (cast<string_t>(text) + string_t(":") + cast<string_t>(__latency_fn_llvm_text_from_plan_status_name(row->status_id)));
	return text;
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
string_t __latency_fn_llvm_text_from_plan_preflight_debug_string(FunctionBodyTextEmissionPreflightRow row) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::preflight_debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[162]);
	string_t text = required_cast<string_t>(string_t("function_body_text_preflight:"));
	text = (cast<string_t>(text) + cast<string_t>(cast<int_t<>>(row->preflight_id)));
	text = (cast<string_t>(text) + string_t(":") + cast<string_t>(__latency_fn_llvm_text_from_plan_status_name(row->status_id)));
	return text;
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_llvm_text_from_plan_sink_stable_hash(BackendSinkBoundaryRow row) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::sink_stable_hash", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[163]);
	string_t identity = required_cast<string_t>(string_t("backend_sink_boundary:v2:"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->sink_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->sink_kind_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->status_id)));
	return php::stable_hash_string_u64(identity);
}

}

namespace scpp { extern const int __latency_lines_llvm_text_from_plan[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_llvm_text_from_plan_preflight_stable_hash(FunctionBodyTextEmissionPreflightRow row) {
	SCPP_CALL_DEPTH_GUARD("llvm_text_from_plan::preflight_stable_hash", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_text_from_plan.phs", __latency_lines_llvm_text_from_plan[164]);
	string_t identity = required_cast<string_t>(string_t("function_body_text_preflight:v2:"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->preflight_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->symbol_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->source_unit_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->text_sink_policy_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->status_id)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(row->blocked_reason_id)));
	return php::stable_hash_string_u64(identity);
}

}

#include <scpp/lang/php.hpp>
#include "__types/LlvmApiToolchainProbeRow.hpp"
#include "__callable/__latency_fn_llvm_api_toolchain_probe_candidate_source_name.hpp"
#include "__callable/__latency_fn_llvm_api_toolchain_probe_probe_debug_string.hpp"
#include "__callable/__latency_fn_llvm_api_toolchain_probe_status_name.hpp"
#include "__callable/__latency_fn_llvm_api_toolchain_probe_probe_stable_hash.hpp"
namespace scpp { extern const int __latency_lines_llvm_api_toolchain_probe[]; }
namespace scpp {
string_t __latency_fn_llvm_api_toolchain_probe_probe_debug_string(LlvmApiToolchainProbeRow row) {
	SCPP_CALL_DEPTH_GUARD("llvm_api_toolchain_probe::probe_debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_api_toolchain_probe.phs", __latency_lines_llvm_api_toolchain_probe[23]);
	return (string_t("llvm_api_toolchain_probe:") + cast<string_t>(cast<int_t<>>(row->toolchain_id)) + string_t(":") + cast<string_t>(__latency_fn_llvm_api_toolchain_probe_candidate_source_name(row->candidate_source_id)) + string_t(":") + cast<string_t>(__latency_fn_llvm_api_toolchain_probe_status_name(row->status_id)));
}

}

namespace scpp { extern const int __latency_lines_llvm_api_toolchain_probe[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_llvm_api_toolchain_probe_probe_stable_hash(LlvmApiToolchainProbeRow row) {
	SCPP_CALL_DEPTH_GUARD("llvm_api_toolchain_probe::probe_stable_hash", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_api_toolchain_probe.phs", __latency_lines_llvm_api_toolchain_probe[24]);
	string_t identity = required_cast<string_t>((string_t("llvm_api_toolchain_probe:v1:") + cast<string_t>(cast<int_t<>>(row->toolchain_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->candidate_source_id)) + string_t(":") + cast<string_t>(row->command_name_hash) + string_t(":") + cast<string_t>(cast<int_t<>>(row->version_major)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->version_minor)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->command_status_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->header_status_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->library_status_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->status_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->blocked_reason_id))));
	return php::stable_hash_string_u64(identity);
}

}

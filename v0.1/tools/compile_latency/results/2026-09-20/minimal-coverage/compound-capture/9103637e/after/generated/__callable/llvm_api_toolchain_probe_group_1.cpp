#include <scpp/lang/php.hpp>
#include "__types/LlvmApiToolchainProbeArtifact.hpp"
#include "__types/LlvmApiToolchainProbeRow.hpp"
#include "__callable/__latency_fn_llvm_api_toolchain_probe_status_blocked_id.hpp"
#include "__callable/__latency_fn_llvm_api_toolchain_probe_status_name.hpp"
#include "__callable/__latency_fn_llvm_api_toolchain_probe_status_ready_id.hpp"
#include "__callable/__latency_fn_llvm_api_toolchain_probe_blocked_reason_command_missing_id.hpp"
#include "__callable/__latency_fn_llvm_api_toolchain_probe_blocked_reason_header_missing_id.hpp"
#include "__callable/__latency_fn_llvm_api_toolchain_probe_blocked_reason_library_missing_id.hpp"
#include "__callable/__latency_fn_llvm_api_toolchain_probe_blocked_reason_name.hpp"
#include "__callable/__latency_fn_llvm_api_toolchain_probe_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_llvm_api_toolchain_probe_artifact_kind_llvm_api_toolchain_probe_id.hpp"
#include "__callable/__latency_fn_llvm_api_toolchain_probe_new_artifact.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_llvm_api_toolchain_probe_check_status_ready_id.hpp"
#include "__callable/__latency_fn_llvm_api_toolchain_probe_status_blocked_id.hpp"
#include "__callable/__latency_fn_llvm_api_toolchain_probe_status_from_checks.hpp"
#include "__callable/__latency_fn_llvm_api_toolchain_probe_status_ready_id.hpp"
#include "__callable/__latency_fn_llvm_api_toolchain_probe_blocked_reason_command_missing_id.hpp"
#include "__callable/__latency_fn_llvm_api_toolchain_probe_blocked_reason_from_checks.hpp"
#include "__callable/__latency_fn_llvm_api_toolchain_probe_blocked_reason_header_missing_id.hpp"
#include "__callable/__latency_fn_llvm_api_toolchain_probe_blocked_reason_library_missing_id.hpp"
#include "__callable/__latency_fn_llvm_api_toolchain_probe_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_llvm_api_toolchain_probe_check_status_ready_id.hpp"
#include "__callable/__latency_fn_llvm_api_toolchain_probe_status_from_checks.hpp"
#include "__callable/__latency_fn_llvm_api_toolchain_probe_status_ready_id.hpp"
#include "__callable/__latency_fn_llvm_api_toolchain_probe_blocked_reason_from_checks.hpp"
#include "__callable/__latency_fn_llvm_api_toolchain_probe_probe_row_from_candidate.hpp"
#include "__callable/__latency_fn_llvm_api_toolchain_probe_status_from_checks.hpp"
#include "__callable/__latency_fn_llvm_api_toolchain_probe_append_probe.hpp"
#include "__callable/__latency_fn_llvm_api_toolchain_probe_candidate_source_explicit_path_id.hpp"
#include "__callable/__latency_fn_llvm_api_toolchain_probe_candidate_source_unversioned_command_id.hpp"
#include "__callable/__latency_fn_llvm_api_toolchain_probe_candidate_source_versioned_command_id.hpp"
#include "__callable/__latency_fn_llvm_api_toolchain_probe_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_llvm_api_toolchain_probe_probe_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
namespace scpp { extern const int __latency_lines_llvm_api_toolchain_probe[]; }
namespace scpp {
string_t __latency_fn_llvm_api_toolchain_probe_status_name(int_t<std::uint16_t> id) {
	SCPP_CALL_DEPTH_GUARD("llvm_api_toolchain_probe::status_name", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_api_toolchain_probe.phs", __latency_lines_llvm_api_toolchain_probe[15]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_llvm_api_toolchain_probe_status_ready_id())))) {
		return string_t("ready");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_llvm_api_toolchain_probe_status_blocked_id())))) {
		return string_t("blocked");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_llvm_api_toolchain_probe[]; }
namespace scpp {
string_t __latency_fn_llvm_api_toolchain_probe_blocked_reason_name(int_t<std::uint16_t> id) {
	SCPP_CALL_DEPTH_GUARD("llvm_api_toolchain_probe::blocked_reason_name", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_api_toolchain_probe.phs", __latency_lines_llvm_api_toolchain_probe[16]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_llvm_api_toolchain_probe_blocked_reason_none_id())))) {
		return string_t("");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_llvm_api_toolchain_probe_blocked_reason_command_missing_id())))) {
		return string_t("command_missing");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_llvm_api_toolchain_probe_blocked_reason_header_missing_id())))) {
		return string_t("header_missing");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_llvm_api_toolchain_probe_blocked_reason_library_missing_id())))) {
		return string_t("library_missing");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_llvm_api_toolchain_probe[]; }
namespace scpp {
LlvmApiToolchainProbeArtifact __latency_fn_llvm_api_toolchain_probe_new_artifact(int_t<> rowCapacity) {
	SCPP_CALL_DEPTH_GUARD("llvm_api_toolchain_probe::new_artifact", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_api_toolchain_probe.phs", __latency_lines_llvm_api_toolchain_probe[17]);
	LlvmApiToolchainProbeArtifact artifact = LlvmApiToolchainProbeArtifact{};
	artifact->artifact_kind_id = __latency_fn_llvm_api_toolchain_probe_artifact_kind_llvm_api_toolchain_probe_id();
	artifact->schema_version = __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
	php::vector_reserve(artifact->probes, rowCapacity);
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_llvm_api_toolchain_probe[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_llvm_api_toolchain_probe_status_from_checks(int_t<std::uint16_t> commandStatusId, int_t<std::uint16_t> headerStatusId, int_t<std::uint16_t> libraryStatusId) {
	SCPP_CALL_DEPTH_GUARD("llvm_api_toolchain_probe::status_from_checks", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_api_toolchain_probe.phs", __latency_lines_llvm_api_toolchain_probe[18]);
	if (static_cast<bool>(((php::identical(cast<int_t<>>(commandStatusId), cast<int_t<>>(__latency_fn_llvm_api_toolchain_probe_check_status_ready_id())) && php::identical(cast<int_t<>>(headerStatusId), cast<int_t<>>(__latency_fn_llvm_api_toolchain_probe_check_status_ready_id()))) && php::identical(cast<int_t<>>(libraryStatusId), cast<int_t<>>(__latency_fn_llvm_api_toolchain_probe_check_status_ready_id()))))) {
		return __latency_fn_llvm_api_toolchain_probe_status_ready_id();
	}
	return __latency_fn_llvm_api_toolchain_probe_status_blocked_id();
}

}

namespace scpp { extern const int __latency_lines_llvm_api_toolchain_probe[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_llvm_api_toolchain_probe_blocked_reason_from_checks(int_t<std::uint16_t> commandStatusId, int_t<std::uint16_t> headerStatusId, int_t<std::uint16_t> libraryStatusId) {
	SCPP_CALL_DEPTH_GUARD("llvm_api_toolchain_probe::blocked_reason_from_checks", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_api_toolchain_probe.phs", __latency_lines_llvm_api_toolchain_probe[19]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(__latency_fn_llvm_api_toolchain_probe_status_from_checks(cast<int_t<std::uint16_t>>(commandStatusId), cast<int_t<std::uint16_t>>(headerStatusId), cast<int_t<std::uint16_t>>(libraryStatusId))), cast<int_t<>>(__latency_fn_llvm_api_toolchain_probe_status_ready_id())))) {
		return __latency_fn_llvm_api_toolchain_probe_blocked_reason_none_id();
	}
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(commandStatusId), cast<int_t<>>(__latency_fn_llvm_api_toolchain_probe_check_status_ready_id()))))) {
		return __latency_fn_llvm_api_toolchain_probe_blocked_reason_command_missing_id();
	}
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(headerStatusId), cast<int_t<>>(__latency_fn_llvm_api_toolchain_probe_check_status_ready_id()))))) {
		return __latency_fn_llvm_api_toolchain_probe_blocked_reason_header_missing_id();
	}
	return __latency_fn_llvm_api_toolchain_probe_blocked_reason_library_missing_id();
}

}

namespace scpp { extern const int __latency_lines_llvm_api_toolchain_probe[]; }
namespace scpp {
LlvmApiToolchainProbeRow __latency_fn_llvm_api_toolchain_probe_probe_row_from_candidate(int_t<std::uint32_t> toolchainId, int_t<std::uint16_t> candidateSourceId, const string_t& commandName, int_t<std::uint16_t> versionMajor, int_t<std::uint16_t> versionMinor, int_t<std::uint16_t> commandStatusId, int_t<std::uint16_t> headerStatusId, int_t<std::uint16_t> libraryStatusId) {
	SCPP_CALL_DEPTH_GUARD("llvm_api_toolchain_probe::probe_row_from_candidate", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_api_toolchain_probe.phs", __latency_lines_llvm_api_toolchain_probe[20]);
	LlvmApiToolchainProbeRow row = LlvmApiToolchainProbeRow{};
	row->toolchain_id = toolchainId;
	row->candidate_source_id = candidateSourceId;
	row->command_name_hash = php::stable_hash_string_u64(commandName);
	row->version_major = versionMajor;
	row->version_minor = versionMinor;
	row->command_status_id = commandStatusId;
	row->header_status_id = headerStatusId;
	row->library_status_id = libraryStatusId;
	row->status_id = __latency_fn_llvm_api_toolchain_probe_status_from_checks(cast<int_t<std::uint16_t>>(commandStatusId), cast<int_t<std::uint16_t>>(headerStatusId), cast<int_t<std::uint16_t>>(libraryStatusId));
	row->blocked_reason_id = __latency_fn_llvm_api_toolchain_probe_blocked_reason_from_checks(cast<int_t<std::uint16_t>>(commandStatusId), cast<int_t<std::uint16_t>>(headerStatusId), cast<int_t<std::uint16_t>>(libraryStatusId));
	return row;
}

}

namespace scpp { extern const int __latency_lines_llvm_api_toolchain_probe[]; }
namespace scpp {
void __latency_fn_llvm_api_toolchain_probe_append_probe(LlvmApiToolchainProbeArtifact& artifact, LlvmApiToolchainProbeRow row) {
	SCPP_CALL_DEPTH_GUARD("llvm_api_toolchain_probe::append_probe", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_api_toolchain_probe.phs", __latency_lines_llvm_api_toolchain_probe[21]);
	row->toolchain_id = __latency_fn_structure_row_ids_next_dense_id(php::count(artifact->probes));
	(void) artifact->probes.append(row);
	artifact->probe_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->probes));
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->candidate_source_id), cast<int_t<>>(__latency_fn_llvm_api_toolchain_probe_candidate_source_explicit_path_id())))) {
		artifact->explicit_path_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->explicit_path_count) + static_cast<int_t<> >(1)));
	}
	else {
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->candidate_source_id), cast<int_t<>>(__latency_fn_llvm_api_toolchain_probe_candidate_source_versioned_command_id())))) {
			artifact->versioned_command_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->versioned_command_count) + static_cast<int_t<> >(1)));
		}
		else {
			if (static_cast<bool>(php::identical(cast<int_t<>>(row->candidate_source_id), cast<int_t<>>(__latency_fn_llvm_api_toolchain_probe_candidate_source_unversioned_command_id())))) {
				artifact->unversioned_command_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->unversioned_command_count) + static_cast<int_t<> >(1)));
			}
		}
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_llvm_api_toolchain_probe_status_ready_id())))) {
		artifact->ready_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->ready_count) + static_cast<int_t<> >(1)));
		return;
	}
	artifact->blocked_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->blocked_count) + static_cast<int_t<> >(1)));
}

}

namespace scpp { extern const int __latency_lines_llvm_api_toolchain_probe[]; }
namespace scpp {
LlvmApiToolchainProbeRow __latency_fn_llvm_api_toolchain_probe_probe_by_id(LlvmApiToolchainProbeArtifact artifact, int_t<std::uint32_t> toolchainId) {
	SCPP_CALL_DEPTH_GUARD("llvm_api_toolchain_probe::probe_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/llvm_api_toolchain_probe.phs", __latency_lines_llvm_api_toolchain_probe[22]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(toolchainId, cast<int_t<>>(artifact->probe_count))))) {
		LlvmApiToolchainProbeRow row = artifact->probes[__latency_fn_structure_row_ids_dense_index(toolchainId)];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->toolchain_id), cast<int_t<>>(toolchainId)))) {
			return row;
		}
	}
	auto __latency_local_0 = artifact->probes;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->toolchain_id), cast<int_t<>>(toolchainId)))) {
			return row;
		}
	}
	LlvmApiToolchainProbeRow empty = LlvmApiToolchainProbeRow{};
	return empty;
}

}

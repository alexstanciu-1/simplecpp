#include <scpp/lang/php.hpp>
#include "__types/ProjectManifest.hpp"
#include "__types/ProjectManifestSourceRow.hpp"
#include "__types/SourceUnitTableRow.hpp"
#include "__types/source_identity.hpp"
#include "__callable/__latency_fn_source_identity_manifest_source_unit_key.hpp"
#include "__callable/__latency_fn_source_identity_manifest_source_relative_path.hpp"
#include "__callable/__latency_fn_source_identity_manifest_source_unit_key.hpp"
#include "__callable/__latency_fn_source_identity_manifest_source_unit_key_from_manifest.hpp"
#include "__callable/__latency_fn_source_identity_manifest_source_relative_path.hpp"
#include "__callable/__latency_fn_source_identity_manifest_source_relative_path_from_manifest.hpp"
#include "__callable/__latency_fn_source_identity_manifest_source_equals.hpp"
#include "__callable/__latency_fn_source_identity_manifest_source_debug_string.hpp"
#include "__callable/__latency_fn_source_identity_manifest_source_debug_string.hpp"
#include "__callable/__latency_fn_source_identity_manifest_source_debug_string_from_manifest.hpp"
#include "__callable/__latency_fn_source_identity_manifest_source_relative_path_from_manifest.hpp"
#include "__callable/__latency_fn_source_identity_manifest_source_unit_key_from_manifest.hpp"
#include "__callable/__latency_fn_source_identity_manifest_source_relative_path.hpp"
#include "__callable/__latency_fn_source_identity_manifest_source_stable_hash.hpp"
#include "__callable/__latency_fn_source_identity_manifest_source_unit_key.hpp"
#include "__callable/__latency_fn_source_identity_manifest_source_relative_path_from_manifest.hpp"
#include "__callable/__latency_fn_source_identity_manifest_source_stable_hash_from_manifest.hpp"
#include "__callable/__latency_fn_source_identity_manifest_source_unit_key_from_manifest.hpp"
#include "__callable/__latency_fn_source_identity_source_unit_key.hpp"
#include "__callable/__latency_fn_source_identity_source_unit_relative_path.hpp"
#include "__callable/__latency_fn_source_identity_source_unit_path.hpp"
namespace scpp { extern const int __latency_lines_source_identity[]; }
namespace scpp {
bool_t source_identity::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == source_identity::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_source_identity[]; }
namespace scpp {
string_t __latency_fn_source_identity_manifest_source_unit_key(ProjectManifestSourceRow row) {
	SCPP_CALL_DEPTH_GUARD("source_identity::manifest_source_unit_key", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_identity.phs", __latency_lines_source_identity[0]);
	if (static_cast<bool>((cast<int_t<>>(row->source_unit_key_id) > static_cast<int_t<> >(0)))) {
		return (string_t("source_unit_key_id:") + cast<string_t>(row->source_unit_key_id));
	}
	return string_t("");
}

}

namespace scpp { extern const int __latency_lines_source_identity[]; }
namespace scpp {
string_t __latency_fn_source_identity_manifest_source_relative_path(ProjectManifestSourceRow row) {
	SCPP_CALL_DEPTH_GUARD("source_identity::manifest_source_relative_path", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_identity.phs", __latency_lines_source_identity[1]);
	if (static_cast<bool>((cast<int_t<>>(row->relative_path_id) > static_cast<int_t<> >(0)))) {
		return (string_t("relative_path_id:") + cast<string_t>(row->relative_path_id));
	}
	return string_t("");
}

}

namespace scpp { extern const int __latency_lines_source_identity[]; }
namespace scpp {
string_t __latency_fn_source_identity_manifest_source_unit_key_from_manifest(shared_p<ProjectManifest> manifest, ProjectManifestSourceRow row) {
	SCPP_CALL_DEPTH_GUARD("source_identity::manifest_source_unit_key_from_manifest", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_identity.phs", __latency_lines_source_identity[2]);
	int_t<> id = required_cast<int_t<>>(cast<int_t<>>(row->source_unit_key_id));
	if (static_cast<bool>(((id > static_cast<int_t<> >(0)) && (id <= php::count(manifest->source_unit_keys))))) {
		return manifest->source_unit_keys[(id - static_cast<int_t<> >(1))];
	}
	return __latency_fn_source_identity_manifest_source_unit_key(row);
}

}

namespace scpp { extern const int __latency_lines_source_identity[]; }
namespace scpp {
string_t __latency_fn_source_identity_manifest_source_relative_path_from_manifest(shared_p<ProjectManifest> manifest, ProjectManifestSourceRow row) {
	SCPP_CALL_DEPTH_GUARD("source_identity::manifest_source_relative_path_from_manifest", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_identity.phs", __latency_lines_source_identity[3]);
	int_t<> id = required_cast<int_t<>>(cast<int_t<>>(row->relative_path_id));
	if (static_cast<bool>(((id > static_cast<int_t<> >(0)) && (id <= php::count(manifest->source_relative_paths))))) {
		return manifest->source_relative_paths[(id - static_cast<int_t<> >(1))];
	}
	return __latency_fn_source_identity_manifest_source_relative_path(row);
}

}

namespace scpp { extern const int __latency_lines_source_identity[]; }
namespace scpp {
bool_t __latency_fn_source_identity_manifest_source_equals(ProjectManifestSourceRow left, ProjectManifestSourceRow right) {
	SCPP_CALL_DEPTH_GUARD("source_identity::manifest_source_equals", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_identity.phs", __latency_lines_source_identity[4]);
	if (static_cast<bool>((((cast<int_t<>>(left->source_id) > static_cast<int_t<> >(0)) && (cast<int_t<>>(right->source_id) > static_cast<int_t<> >(0))) && php::not_identical(cast<int_t<>>(left->source_id), cast<int_t<>>(right->source_id))))) {
		return bool_t(static_cast<bool_t>(false));
	}
	if (static_cast<bool>((php::identical(cast<int_t<>>(left->source_unit_key_id), static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(right->source_unit_key_id), static_cast<int_t<> >(0))))) {
		return bool_t(static_cast<bool_t>(false));
	}
	return (((php::identical(left->source_unit_key_id, right->source_unit_key_id) && php::identical(left->relative_path_id, right->relative_path_id)) && php::identical(left->language_id, right->language_id)) && php::identical(left->status_id, right->status_id));
}

}

namespace scpp { extern const int __latency_lines_source_identity[]; }
namespace scpp {
string_t __latency_fn_source_identity_manifest_source_debug_string(ProjectManifestSourceRow row) {
	SCPP_CALL_DEPTH_GUARD("source_identity::manifest_source_debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_identity.phs", __latency_lines_source_identity[5]);
	if (static_cast<bool>((cast<int_t<>>(row->source_id) > static_cast<int_t<> >(0)))) {
		return (string_t("manifest_source_id:") + cast<string_t>(row->source_id));
	}
	return string_t("");
}

}

namespace scpp { extern const int __latency_lines_source_identity[]; }
namespace scpp {
string_t __latency_fn_source_identity_manifest_source_debug_string_from_manifest(shared_p<ProjectManifest> manifest, ProjectManifestSourceRow row) {
	SCPP_CALL_DEPTH_GUARD("source_identity::manifest_source_debug_string_from_manifest", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_identity.phs", __latency_lines_source_identity[6]);
	string_t sourceUnitKey = required_cast<string_t>(__latency_fn_source_identity_manifest_source_unit_key_from_manifest(manifest, row));
	if (static_cast<bool>(php::condition_truthy(php::not_identical(sourceUnitKey, string_t(""))))) {
		return sourceUnitKey;
	}
	string_t relativePath = required_cast<string_t>(__latency_fn_source_identity_manifest_source_relative_path_from_manifest(manifest, row));
	if (static_cast<bool>(php::condition_truthy(php::not_identical(relativePath, string_t(""))))) {
		return (string_t("source:") + cast<string_t>(relativePath));
	}
	return __latency_fn_source_identity_manifest_source_debug_string(row);
}

}

namespace scpp { extern const int __latency_lines_source_identity[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_source_identity_manifest_source_stable_hash(ProjectManifestSourceRow row) {
	SCPP_CALL_DEPTH_GUARD("source_identity::manifest_source_stable_hash", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_identity.phs", __latency_lines_source_identity[7]);
	string_t identity = required_cast<string_t>((string_t("project_manifest_source_identity:v1:") + cast<string_t>(__latency_fn_source_identity_manifest_source_unit_key(row)) + string_t(":") + cast<string_t>(__latency_fn_source_identity_manifest_source_relative_path(row)) + string_t(":") + cast<string_t>(row->language_id) + string_t(":") + cast<string_t>(row->status_id)));
	return php::stable_hash_string_u64(identity);
}

}

namespace scpp { extern const int __latency_lines_source_identity[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_source_identity_manifest_source_stable_hash_from_manifest(shared_p<ProjectManifest> manifest, ProjectManifestSourceRow row) {
	SCPP_CALL_DEPTH_GUARD("source_identity::manifest_source_stable_hash_from_manifest", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_identity.phs", __latency_lines_source_identity[8]);
	string_t identity = required_cast<string_t>((string_t("project_manifest_source_identity:v1:") + cast<string_t>(__latency_fn_source_identity_manifest_source_unit_key_from_manifest(manifest, row)) + string_t(":") + cast<string_t>(__latency_fn_source_identity_manifest_source_relative_path_from_manifest(manifest, row)) + string_t(":") + cast<string_t>(row->language_id) + string_t(":") + cast<string_t>(row->status_id)));
	return php::stable_hash_string_u64(identity);
}

}

namespace scpp { extern const int __latency_lines_source_identity[]; }
namespace scpp {
string_t __latency_fn_source_identity_source_unit_key(SourceUnitTableRow row) {
	SCPP_CALL_DEPTH_GUARD("source_identity::source_unit_key", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_identity.phs", __latency_lines_source_identity[9]);
	if (static_cast<bool>((cast<int_t<>>(row->source_unit_key_id) > static_cast<int_t<> >(0)))) {
		return (string_t("source_unit_key_id:") + cast<string_t>(row->source_unit_key_id));
	}
	return string_t("");
}

}

namespace scpp { extern const int __latency_lines_source_identity[]; }
namespace scpp {
string_t __latency_fn_source_identity_source_unit_relative_path(SourceUnitTableRow row) {
	SCPP_CALL_DEPTH_GUARD("source_identity::source_unit_relative_path", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_identity.phs", __latency_lines_source_identity[10]);
	if (static_cast<bool>((cast<int_t<>>(row->relative_path_id) > static_cast<int_t<> >(0)))) {
		return (string_t("relative_path_id:") + cast<string_t>(row->relative_path_id));
	}
	return string_t("");
}

}

namespace scpp { extern const int __latency_lines_source_identity[]; }
namespace scpp {
string_t __latency_fn_source_identity_source_unit_path(SourceUnitTableRow row) {
	SCPP_CALL_DEPTH_GUARD("source_identity::source_unit_path", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_identity.phs", __latency_lines_source_identity[11]);
	if (static_cast<bool>((cast<int_t<>>(row->path_id) > static_cast<int_t<> >(0)))) {
		return (string_t("path_id:") + cast<string_t>(row->path_id));
	}
	return string_t("");
}

}

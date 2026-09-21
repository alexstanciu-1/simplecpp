#include <scpp/lang/php.hpp>
#include "__types/ProjectManifest.hpp"
#include "__types/ProjectManifestSourceRow.hpp"
#include "__types/project_manifest.hpp"
#include "__callable/__latency_fn_project_manifest_language_phs_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_project_manifest_source_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_project_manifest_language_name.hpp"
#include "__callable/__latency_fn_project_manifest_language_phs_id.hpp"
#include "__callable/__latency_fn_project_manifest_source_status_name.hpp"
#include "__callable/__latency_fn_project_manifest_source_status_ready_id.hpp"
#include "__callable/__latency_fn_project_manifest_reserve_sources.hpp"
#include "__callable/__latency_fn_project_manifest_manifest_source_unit_key.hpp"
#include "__callable/__latency_fn_project_manifest_language_phs_id.hpp"
#include "__callable/__latency_fn_project_manifest_source_row.hpp"
#include "__callable/__latency_fn_project_manifest_source_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_project_manifest_add_source.hpp"
#include "__callable/__latency_fn_project_manifest_manifest_source_unit_key.hpp"
#include "__callable/__latency_fn_project_manifest_source_row.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_project_manifest_clean_value.hpp"
#include "__callable/__latency_fn_project_manifest_add_source.hpp"
#include "__callable/__latency_fn_project_manifest_clean_value.hpp"
#include "__callable/__latency_fn_project_manifest_from_project_dir.hpp"
#include "__callable/__latency_fn_project_manifest_reserve_sources.hpp"
#include "__callable/__latency_fn_source_files_content_or_empty.hpp"
namespace scpp { extern const int __latency_lines_project_manifest[]; }
namespace scpp {
bool_t project_manifest::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == project_manifest::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_project_manifest[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_project_manifest_language_phs_id() {
	SCPP_CALL_DEPTH_GUARD("project_manifest::language_phs_id", "/tmp/scpp-edit-latency-20260919/app/compile/pipeline/project_manifest.phs", __latency_lines_project_manifest[0]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_project_manifest[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_project_manifest_source_status_ready_id() {
	SCPP_CALL_DEPTH_GUARD("project_manifest::source_status_ready_id", "/tmp/scpp-edit-latency-20260919/app/compile/pipeline/project_manifest.phs", __latency_lines_project_manifest[1]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_project_manifest[]; }
namespace scpp {
string_t __latency_fn_project_manifest_language_name(int_t<std::uint16_t> id) {
	SCPP_CALL_DEPTH_GUARD("project_manifest::language_name", "/tmp/scpp-edit-latency-20260919/app/compile/pipeline/project_manifest.phs", __latency_lines_project_manifest[2]);
	if (static_cast<bool>(php::identical(id, __latency_fn_project_manifest_language_phs_id()))) {
		return string_t("phs");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_project_manifest[]; }
namespace scpp {
string_t __latency_fn_project_manifest_source_status_name(int_t<std::uint16_t> id) {
	SCPP_CALL_DEPTH_GUARD("project_manifest::source_status_name", "/tmp/scpp-edit-latency-20260919/app/compile/pipeline/project_manifest.phs", __latency_lines_project_manifest[3]);
	if (static_cast<bool>(php::identical(id, __latency_fn_project_manifest_source_status_ready_id()))) {
		return string_t("ready");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_project_manifest[]; }
namespace scpp {
void __latency_fn_project_manifest_reserve_sources(shared_p<ProjectManifest> manifest, int_t<> sourceCapacity) {
	SCPP_CALL_DEPTH_GUARD("project_manifest::reserve_sources", "/tmp/scpp-edit-latency-20260919/app/compile/pipeline/project_manifest.phs", __latency_lines_project_manifest[4]);
	php::vector_reserve(manifest->sources, sourceCapacity);
	php::vector_reserve(manifest->source_relative_paths, sourceCapacity);
	php::vector_reserve(manifest->source_unit_keys, sourceCapacity);
}

}

namespace scpp { extern const int __latency_lines_project_manifest[]; }
namespace scpp {
string_t __latency_fn_project_manifest_manifest_source_unit_key(const string_t& relativePath) {
	SCPP_CALL_DEPTH_GUARD("project_manifest::manifest_source_unit_key", "/tmp/scpp-edit-latency-20260919/app/compile/pipeline/project_manifest.phs", __latency_lines_project_manifest[5]);
	string_t key = required_cast<string_t>(relativePath);
	key = str::replace(string_t("\\"), string_t("/"), key);
	key = str::replace(string_t(".phs"), string_t(""), key);
	key = str::replace(string_t("/"), string_t(":"), key);
	return (string_t("source:") + cast<string_t>(key));
}

}

namespace scpp { extern const int __latency_lines_project_manifest[]; }
namespace scpp {
ProjectManifestSourceRow __latency_fn_project_manifest_source_row(int_t<> id, const string_t& relativePath) {
	SCPP_CALL_DEPTH_GUARD("project_manifest::source_row", "/tmp/scpp-edit-latency-20260919/app/compile/pipeline/project_manifest.phs", __latency_lines_project_manifest[6]);
	ProjectManifestSourceRow row = ProjectManifestSourceRow{};
	row->source_id = __latency_fn_structure_row_ids_uint32_from_int(id);
	row->language_id = __latency_fn_project_manifest_language_phs_id();
	row->status_id = __latency_fn_project_manifest_source_status_ready_id();
	row->relative_path_id = __latency_fn_structure_row_ids_uint32_from_int(id);
	row->source_unit_key_id = __latency_fn_structure_row_ids_uint32_from_int(id);
	return row;
}

}

namespace scpp { extern const int __latency_lines_project_manifest[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_project_manifest_add_source(shared_p<ProjectManifest> manifest, const string_t& relativePath) {
	SCPP_CALL_DEPTH_GUARD("project_manifest::add_source", "/tmp/scpp-edit-latency-20260919/app/compile/pipeline/project_manifest.phs", __latency_lines_project_manifest[7]);
	int_t<> nextId = required_cast<int_t<>>((php::count(manifest->sources) + static_cast<int_t<> >(1)));
	(void) manifest->source_relative_paths.append(relativePath);
	{
	auto __latency_local_0 = __latency_fn_project_manifest_manifest_source_unit_key(relativePath);
	(void) manifest->source_unit_keys.append(__latency_local_0);
	}
	{
	auto __latency_local_1 = __latency_fn_project_manifest_source_row(nextId, relativePath);
	(void) manifest->sources.append(__latency_local_1);
	}
	manifest->source_count = php::count(manifest->sources);
	if (static_cast<bool>(php::identical(manifest->entry_source_path, string_t("")))) {
		manifest->entry_source_path = relativePath;
	}
	return __latency_fn_structure_row_ids_uint32_from_int(nextId);
}

}

namespace scpp { extern const int __latency_lines_project_manifest[]; }
namespace scpp {
string_t __latency_fn_project_manifest_clean_value(const string_t& value) {
	SCPP_CALL_DEPTH_GUARD("project_manifest::clean_value", "/tmp/scpp-edit-latency-20260919/app/compile/pipeline/project_manifest.phs", __latency_lines_project_manifest[8]);
	string_t clean = required_cast<string_t>(str::trim(value));
	clean = str::replace(string_t("\""), string_t(""), clean);
	return clean;
}

}

namespace scpp { extern const int __latency_lines_project_manifest[]; }
namespace scpp {
shared_p<ProjectManifest> __latency_fn_project_manifest_from_project_dir(const string_t& projectDir) {
	SCPP_CALL_DEPTH_GUARD("project_manifest::from_project_dir", "/tmp/scpp-edit-latency-20260919/app/compile/pipeline/project_manifest.phs", __latency_lines_project_manifest[9]);
	shared_p<ProjectManifest> manifest = create<ProjectManifest>();
	manifest->project_dir = projectDir;
	manifest->manifest_path = (cast<string_t>(projectDir) + string_t("/project.manifest"));
	__latency_fn_project_manifest_reserve_sources(manifest, static_cast<int_t<> >(2));
	string_t text = required_cast<string_t>(__latency_fn_source_files_content_or_empty(manifest->manifest_path));
	vector_t<string_t> lines = required_cast<vector_t<string_t>>(php::explode(string_t("\n"), text));
	auto& __latency_local_0 = lines;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto line = __latency_local_1.value_copy();
		string_t trimmed = required_cast<string_t>(str::trim(line));
		if (static_cast<bool>(php::identical(trimmed, string_t("")))) {
			continue;
		}
		vector_t<string_t> parts = required_cast<vector_t<string_t>>(php::explode(string_t("="), trimmed));
		if (static_cast<bool>((php::count(parts) < static_cast<int_t<> >(2)))) {
			continue;
		}
		string_t key = required_cast<string_t>(str::trim(parts.at(static_cast<int_t<> >(0))));
		string_t value = required_cast<string_t>(__latency_fn_project_manifest_clean_value(parts.at(static_cast<int_t<> >(1))));
		if (static_cast<bool>(php::identical(key, string_t("source")))) {
			__latency_fn_project_manifest_add_source(manifest, value);
		}
		else {
			if (static_cast<bool>(php::identical(key, string_t("entry")))) {
				if (static_cast<bool>(php::condition_truthy(php::str_contains(value, string_t(".phs"))))) {
					manifest->entry_source_path = value;
				}
				else {
					manifest->entry_function = value;
				}
			}
		}
	}
	if (static_cast<bool>(php::identical(manifest->source_count, static_cast<int_t<> >(0)))) {
		__latency_fn_project_manifest_add_source(manifest, string_t("main.phs"));
	}
	return manifest;
}

}

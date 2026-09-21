#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
#include "__types/ProjectManifestSourceRow.hpp"
namespace scpp {
class ProjectManifest {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	int_t<> artifact_kind_id = static_cast<int_t<> >(1);
	int_t<> schema_version = static_cast<int_t<> >(1);
	string_t manifest_path = string_t("");
	string_t project_dir = string_t("");
	string_t entry_function = string_t("run");
	string_t entry_source_path = string_t("");
	int_t<> source_count = static_cast<int_t<> >(0);
	vector_t<ProjectManifestSourceRow> sources = vector_t<ProjectManifestSourceRow>{};
	vector_t<string_t> source_relative_paths = vector_t<string_t>{};
	vector_t<string_t> source_unit_keys = vector_t<string_t>{};
};
}

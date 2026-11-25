#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
#include "__types/ProjectSymbolBodyLookupRow.hpp"
namespace scpp {
class ProjectSymbolBodyLookupArtifact {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	int_t<> artifact_kind_id = static_cast<int_t<> >(1);
	int_t<> schema_version = static_cast<int_t<> >(1);
	int_t<> source_model_id = static_cast<int_t<> >(1);
	int_t<> lookup_model_id = static_cast<int_t<> >(1);
	int_t<> row_count = static_cast<int_t<> >(0);
	int_t<> ready_count = static_cast<int_t<> >(0);
	int_t<> blocked_count = static_cast<int_t<> >(0);
	vector_t<ProjectSymbolBodyLookupRow> rows = vector_t<ProjectSymbolBodyLookupRow>{};
};
}

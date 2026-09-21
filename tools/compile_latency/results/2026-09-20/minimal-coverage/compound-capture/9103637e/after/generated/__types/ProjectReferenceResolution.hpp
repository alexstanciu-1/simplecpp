#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
#include "__types/ProjectReferenceActualArgumentRow.hpp"
#include "__types/ProjectReferenceResolutionRow.hpp"
namespace scpp {
class ProjectReferenceResolution {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	int_t<std::uint16_t> artifact_kind_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(1));
	int_t<std::uint16_t> schema_version = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(1));
	int_t<std::uint16_t> source_model_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(1));
	int_t<std::uint16_t> resolution_model_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(1));
	int_t<std::uint16_t> acceptance_status_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(1));
	int_t<std::uint32_t> reference_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> resolved_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> unresolved_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> ambiguous_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> missing_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	vector_t<ProjectReferenceResolutionRow> rows = vector_t<ProjectReferenceResolutionRow>{};
	vector_t<string_t> from_symbol_keys = vector_t<string_t>{};
	vector_t<string_t> from_source_unit_keys = vector_t<string_t>{};
	vector_t<string_t> callee_names = vector_t<string_t>{};
	vector_t<string_t> resolved_symbol_keys = vector_t<string_t>{};
	vector_t<string_t> resolved_source_unit_keys = vector_t<string_t>{};
	vector_t<ProjectReferenceActualArgumentRow> actual_arguments = vector_t<ProjectReferenceActualArgumentRow>{};
};
}

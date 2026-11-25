#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
#include "__types/ProjectFrontendSourceRow.hpp"
namespace scpp {
class FrontendModel;
class ProjectFrontendModel {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	int_t<> artifact_kind_id = static_cast<int_t<> >(1);
	int_t<> schema_version = static_cast<int_t<> >(1);
	int_t<> source_model_id = static_cast<int_t<> >(1);
	string_t entry_source_unit_key = string_t("");
	int_t<> source_unit_count = static_cast<int_t<> >(0);
	int_t<std::uint32_t> token_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> token_segment_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> token_segment_reserved_bytes = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> token_segment_slack_bytes = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> token_segmented_source_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> token_vector_source_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> frontend_node_segment_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> frontend_node_segment_reserved_bytes = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> frontend_node_segment_slack_bytes = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> frontend_node_segmented_source_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> frontend_node_vector_source_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<> total_row_count = static_cast<int_t<> >(0);
	int_t<> total_declaration_count = static_cast<int_t<> >(0);
	int_t<> total_statement_count = static_cast<int_t<> >(0);
	int_t<> total_expression_count = static_cast<int_t<> >(0);
	vector_t<ProjectFrontendSourceRow> source_rows = vector_t<ProjectFrontendSourceRow>{};
	vector_t<string_t> source_unit_keys = vector_t<string_t>{};
	vector_t<string_t> relative_paths = vector_t<string_t>{};
	vector_t<string_t> paths = vector_t<string_t>{};
	vector_t<int_t<std::uint32_t>> frontend_state_ids = vector_t<int_t<std::uint32_t>>{};
	vector_t<int_t<std::uint32_t>> frontend_payload_table_ids = vector_t<int_t<std::uint32_t>>{};
	vector_t<shared_p<FrontendModel>> models = vector_t<shared_p<FrontendModel>>{};
};
}

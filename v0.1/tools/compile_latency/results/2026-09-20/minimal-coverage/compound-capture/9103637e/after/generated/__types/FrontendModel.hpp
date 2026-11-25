#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
#include "__types/FrontendDeclarationPayloadRow.hpp"
#include "__types/FrontendExpressionPayloadRow.hpp"
#include "__types/FrontendLiteralPayloadRow.hpp"
#include "__types/FrontendNamePayloadRow.hpp"
#include "__types/FrontendStatementPayloadRow.hpp"
#include "__types/FrontendTypeSyntaxPayloadRow.hpp"
#include "__types/LineStartRow.hpp"
#include "__types/SourceBufferRow.hpp"
#include "__types/SourceRangeExtRow.hpp"
#include "__types/SourceRangeRow.hpp"
#include "__types/source_buffers.hpp"
namespace scpp {
class FrontendNodeList;
class FrontendModel {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	int_t<std::uint16_t> artifact_kind_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(1));
	int_t<std::uint16_t> schema_version = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(1));
	int_t<std::uint16_t> source_language_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(1));
	int_t<std::uint32_t> source_unit_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> source_buffer_id = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> node_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> declaration_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> statement_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> expression_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> type_syntax_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> literal_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> name_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> source_range_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> line_start_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> parser_diagnostic_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> parser_error_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	vector_t<SourceBufferRow> source_buffers = vector_t<SourceBufferRow>{};
	vector_t<SourceRangeRow> source_ranges = vector_t<SourceRangeRow>{};
	vector_t<SourceRangeExtRow> source_range_exts = vector_t<SourceRangeExtRow>{};
	vector_t<LineStartRow> line_starts = vector_t<LineStartRow>{};
	shared_p<FrontendNodeList> node_rows;
	vector_t<int_t<std::uint32_t>> declaration_node_ids = vector_t<int_t<std::uint32_t>>{};
	vector_t<FrontendDeclarationPayloadRow> declarations = vector_t<FrontendDeclarationPayloadRow>{};
	vector_t<FrontendStatementPayloadRow> statements = vector_t<FrontendStatementPayloadRow>{};
	vector_t<FrontendExpressionPayloadRow> expressions = vector_t<FrontendExpressionPayloadRow>{};
	vector_t<FrontendTypeSyntaxPayloadRow> type_syntaxes = vector_t<FrontendTypeSyntaxPayloadRow>{};
	vector_t<FrontendLiteralPayloadRow> literals = vector_t<FrontendLiteralPayloadRow>{};
	vector_t<FrontendNamePayloadRow> names = vector_t<FrontendNamePayloadRow>{};
	FrontendModel();
};
}

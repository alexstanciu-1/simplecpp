#include <scpp/lang/php.hpp>
#include "__types/FrontendModel.hpp"
#include "__types/FrontendModelKernelCounters.hpp"
#include "__types/LineStartRow.hpp"
#include "__types/SourceBufferRow.hpp"
#include "__types/SourceUnitTableRow.hpp"
#include "__types/source_buffers.hpp"
#include "__callable/__latency_fn_source_buffers_content_hash32.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_source_buffers_content_hash32_slice.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_source_buffers_content_hash32.hpp"
#include "__callable/__latency_fn_source_buffers_row_from_source_unit.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_source_buffer.hpp"
#include "__callable/__latency_fn_source_buffers_append_from_source_unit.hpp"
#include "__callable/__latency_fn_source_buffers_row_from_source_unit.hpp"
#include "__callable/__latency_fn_frontend_model_tables_append_line_start.hpp"
#include "__callable/__latency_fn_source_buffers_append_line_starts.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_source_buffers_source_buffer_adoption_smoke_ok.hpp"
namespace scpp { extern const int __latency_lines_source_buffers[]; }
namespace scpp {
bool_t source_buffers::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == source_buffers::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_source_buffers[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_source_buffers_content_hash32(const string_t& sourceText) {
	SCPP_CALL_DEPTH_GUARD("source_buffers::content_hash32", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_buffers.phs", __latency_lines_source_buffers[0]);
	int_t<> hash = required_cast<int_t<>>(static_cast<int_t<> >(2166136261));
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> length = required_cast<int_t<>>(str::byte_length(sourceText));
	while (static_cast<bool>((index < length))) {
		hash = (((hash * static_cast<int_t<> >(16777619)) + php::string_byte_at(sourceText, index)) % static_cast<int_t<> >(4294967295));
		index = (index + static_cast<int_t<> >(1));
	}
	return __latency_fn_structure_row_ids_uint32_from_int(hash);
}

}

namespace scpp { extern const int __latency_lines_source_buffers[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_source_buffers_content_hash32_slice(const string_t& sourceText, int_t<std::uint32_t> startOffset, int_t<std::uint32_t> length) {
	SCPP_CALL_DEPTH_GUARD("source_buffers::content_hash32_slice", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_buffers.phs", __latency_lines_source_buffers[1]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(length), static_cast<int_t<> >(0)))) {
		return __latency_fn_structure_row_ids_none_id();
	}
	int_t<> sourceLength = required_cast<int_t<>>(str::byte_length(sourceText));
	int_t<> start = required_cast<int_t<>>(cast<int_t<>>(startOffset));
	int_t<> end = required_cast<int_t<>>((start + cast<int_t<>>(length)));
	if (static_cast<bool>((((start < static_cast<int_t<> >(0)) || (start >= sourceLength)) || (end > sourceLength)))) {
		return __latency_fn_structure_row_ids_none_id();
	}
	int_t<> hash = required_cast<int_t<>>(static_cast<int_t<> >(2166136261));
	int_t<> index = required_cast<int_t<>>(start);
	while (static_cast<bool>((index < end))) {
		hash = (((hash * static_cast<int_t<> >(16777619)) + php::string_byte_at(sourceText, index)) % static_cast<int_t<> >(4294967295));
		index = (index + static_cast<int_t<> >(1));
	}
	return __latency_fn_structure_row_ids_uint32_from_int(hash);
}

}

namespace scpp { extern const int __latency_lines_source_buffers[]; }
namespace scpp {
SourceBufferRow __latency_fn_source_buffers_row_from_source_unit(SourceUnitTableRow sourceUnit, const string_t& sourceText) {
	SCPP_CALL_DEPTH_GUARD("source_buffers::row_from_source_unit", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_buffers.phs", __latency_lines_source_buffers[2]);
	SourceBufferRow row = SourceBufferRow{};
	row->source_buffer_id = __latency_fn_structure_row_ids_none_id();
	row->source_unit_id = sourceUnit->source_unit_id;
	row->content_hash = __latency_fn_source_buffers_content_hash32(sourceText);
	row->byte_length = __latency_fn_structure_row_ids_uint32_from_int(str::byte_length(sourceText));
	row->status_id = sourceUnit->status_id;
	return row;
}

}

namespace scpp { extern const int __latency_lines_source_buffers[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_source_buffers_append_from_source_unit(shared_p<FrontendModel> model, SourceUnitTableRow sourceUnit, const string_t& sourceText, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("source_buffers::append_from_source_unit", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_buffers.phs", __latency_lines_source_buffers[3]);
	SourceBufferRow row = __latency_fn_source_buffers_row_from_source_unit(sourceUnit, sourceText);
	return __latency_fn_frontend_model_tables_append_source_buffer(model, row, counters);
}

}

namespace scpp { extern const int __latency_lines_source_buffers[]; }
namespace scpp {
void __latency_fn_source_buffers_append_line_starts(shared_p<FrontendModel> model, int_t<std::uint32_t> sourceBufferId, const string_t& sourceText, shared_p<FrontendModelKernelCounters> counters) {
	SCPP_CALL_DEPTH_GUARD("source_buffers::append_line_starts", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_buffers.phs", __latency_lines_source_buffers[4]);
	LineStartRow lineStart = LineStartRow{.source_buffer_id = sourceBufferId, .line = __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)), .start_offset = __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0))};
	__latency_fn_frontend_model_tables_append_line_start(model, lineStart, counters);
	int_t<> line = required_cast<int_t<>>(static_cast<int_t<> >(2));
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> length = required_cast<int_t<>>(str::byte_length(sourceText));
	while (static_cast<bool>((index < length))) {
		if (static_cast<bool>((php::identical(php::string_byte_at(sourceText, index), static_cast<int_t<> >(10)) && ((index + static_cast<int_t<> >(1)) < length)))) {
			LineStartRow nextLineStart = LineStartRow{.source_buffer_id = sourceBufferId, .line = __latency_fn_structure_row_ids_uint32_from_int(line), .start_offset = __latency_fn_structure_row_ids_uint32_from_int((index + static_cast<int_t<> >(1)))};
			__latency_fn_frontend_model_tables_append_line_start(model, nextLineStart, counters);
			line = (line + static_cast<int_t<> >(1));
		}
		index = (index + static_cast<int_t<> >(1));
	}
}

}

namespace scpp { extern const int __latency_lines_source_buffers[]; }
namespace scpp {
bool_t __latency_fn_source_buffers_source_buffer_adoption_smoke_ok() {
	SCPP_CALL_DEPTH_GUARD("source_buffers::source_buffer_adoption_smoke_ok", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_buffers.phs", __latency_lines_source_buffers[5]);
	string_t sourceText = required_cast<string_t>(string_t("alpha\nbeta\n"));
	int_t<> sourceLength = required_cast<int_t<>>(str::byte_length(sourceText));
	source::source_buffer buffer = required_cast<source::source_buffer>(source::source_buffer_take(sourceText));
	source::byte_span span = required_cast<source::byte_span>(source::source_buffer_span(buffer, static_cast<int_t<> >(0), static_cast<int_t<> >(5)));
	string_t spanText = required_cast<string_t>(source::byte_span_to_string(span));
	string_t released = required_cast<string_t>(source::source_buffer_release(buffer));
	return (((php::identical(str::byte_length(sourceText), static_cast<int_t<> >(0)) && php::identical(cast<int_t<>>(source::source_buffer_byte_len(buffer)), static_cast<int_t<> >(0))) && php::identical(str::byte_length(released), sourceLength)) && php::identical(spanText, string_t("alpha")));
}

}

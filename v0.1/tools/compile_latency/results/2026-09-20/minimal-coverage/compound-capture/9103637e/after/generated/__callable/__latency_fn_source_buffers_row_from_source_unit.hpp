#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct SourceBufferRow;
struct SourceUnitTableRow;
SourceBufferRow __latency_fn_source_buffers_row_from_source_unit(SourceUnitTableRow sourceUnit, const string_t& sourceText);
}

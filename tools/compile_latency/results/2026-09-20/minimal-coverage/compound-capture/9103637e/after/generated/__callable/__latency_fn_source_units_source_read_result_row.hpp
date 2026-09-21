#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct SourceReadDescriptorRow;
struct SourceReadResultRow;
SourceReadResultRow __latency_fn_source_units_source_read_result_row(SourceReadDescriptorRow descriptor, const string_t& sourceText, bool_t readOk);
}

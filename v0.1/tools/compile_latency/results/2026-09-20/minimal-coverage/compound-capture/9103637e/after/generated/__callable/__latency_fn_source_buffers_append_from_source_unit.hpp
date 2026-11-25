#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class FrontendModelKernelCounters;
struct SourceUnitTableRow;
int_t<std::uint32_t> __latency_fn_source_buffers_append_from_source_unit(shared_p<FrontendModel> model, SourceUnitTableRow sourceUnit, const string_t& sourceText, shared_p<FrontendModelKernelCounters> counters);
}

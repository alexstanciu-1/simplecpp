#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
struct FrontendNodeRow;
struct SourceRangeRow;
SourceRangeRow __latency_fn_project_reference_resolution_callee_name_source_range_from_call_expression(shared_p<FrontendModel> model, FrontendNodeRow callNode);
}

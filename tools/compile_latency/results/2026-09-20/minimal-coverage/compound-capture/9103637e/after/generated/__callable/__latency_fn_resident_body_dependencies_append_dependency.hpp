#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct ResidentBodyDependencyRow;
int_t<std::uint32_t> __latency_fn_resident_body_dependencies_append_dependency(shared_p<CompilerProjectRunReport>& report, ResidentBodyDependencyRow row);
}

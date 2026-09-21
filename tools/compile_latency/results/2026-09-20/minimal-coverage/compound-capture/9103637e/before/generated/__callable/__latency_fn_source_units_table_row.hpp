#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct ProjectManifestSourceRow;
struct SourceUnitTableRow;
SourceUnitTableRow __latency_fn_source_units_table_row(const ProjectManifestSourceRow& source, const string_t& sourceText);
}

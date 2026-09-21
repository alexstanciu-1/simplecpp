#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class ProjectManifest;
class SourceReadTable;
class SourceUnitTable;
shared_p<SourceUnitTable> __latency_fn_source_units_table_from_source_read_table(const shared_p<ProjectManifest>& manifest, const shared_p<SourceReadTable>& sourceReads);
}

#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class ProjectManifest;
class SourceUnitTable;
shared_p<SourceUnitTable> __latency_fn_source_units_table_from_manifest(shared_p<ProjectManifest> manifest);
}

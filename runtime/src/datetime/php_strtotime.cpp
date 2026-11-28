#include "datetime_native.hpp"
#include "timelib/timelib.h"
#include "timelib/timelib_private.h"
#include <algorithm>
#include <memory>
#include <vector>

namespace scpp::detail::datetime {
namespace {
struct zone_deleter {
    void operator()(timelib_tzinfo* zone) const { timelib_tzinfo_dtor(zone); }
};

// timelib borrows lookup results. Keep at most one parsed zone per bundled ID
// per thread, including when callers vary the spelling's case.
timelib_tzinfo* lookup_zone(const char* name, const timelib_tzdb* db, int* error) {
    const auto end = db->index + db->index_size;
    const auto entry = std::lower_bound(db->index, end, name,
        [](const timelib_tzdb_index_entry& item, const char* key) {
            return timelib_strcasecmp(item.id, key) < 0;
        });
    if (entry == end || timelib_strcasecmp(entry->id, name) != 0) {
        *error = TIMELIB_ERROR_NO_SUCH_TIMEZONE;
        return nullptr;
    }
    thread_local std::vector<std::unique_ptr<timelib_tzinfo, zone_deleter>> zones(db->index_size);
    auto& zone = zones[entry - db->index];
    *error = TIMELIB_ERROR_NO_ERROR;
    if (!zone) zone.reset(timelib_parse_tzfile(entry->id, db, error));
    return zone.get();
}
}

parse_result php_strtotime(std::string_view text, std::int64_t base) {
    if (text.empty()) return {0, "empty date"};
    const auto db = timelib_builtin_db();
    int error = 0;
    auto* utc = lookup_zone("UTC", db, &error);
    if (!utc) return {0, "UTC zone unavailable"};

    timelib_error_container* diagnostics = nullptr;
    auto parsed = timelib_strtotime(text.data(), text.size(), &diagnostics, db, lookup_zone);
    const bool failed = diagnostics->error_count != 0;
    timelib_error_container_dtor(diagnostics);
    using owned_time = std::unique_ptr<timelib_time, decltype(&timelib_time_dtor)>;
    owned_time result(parsed, timelib_time_dtor);
    if (failed) return {0, "invalid date"};

    owned_time reference(timelib_time_ctor(), timelib_time_dtor);
    reference->zone_type = TIMELIB_ZONETYPE_ID;
    reference->tz_info = utc;
    timelib_unixtime2local(reference.get(), base);
    timelib_fill_holes(result.get(), reference.get(), TIMELIB_NO_CLONE);
    timelib_update_ts(result.get(), utc);
    const auto seconds = timelib_date_to_int(result.get(), &error);
    return {seconds, error ? "timestamp outside integer range" : nullptr};
}
}

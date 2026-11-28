Private timelib 2022.17 sources from PHP tag `php-8.5.12`, directory `ext/date/lib`. See LICENSE.rst.

The UTC calendar uses `dow.c` (compiled as `dow.cpp`) and the first three routines of upstream `unixtime2tm.c` (compiled as `utc.cpp`). The remaining routines are in `unixtime2tm.c` for local time conversion. Keep that split when refreshing upstream so UTC-only callers link independently.

The relative parser uses the upstream generated `parse_date.c`, abbreviation tables and bundled timezone database `2026.5` in `timezonedb.h`. Building the checked-in C sources needs neither re2c nor host zoneinfo.

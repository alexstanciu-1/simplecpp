#pragma once
#include "api.hpp"
#include <scpp/async_int64.hpp>

scpp::async::int64_task read_value(trace& log, bool fail);

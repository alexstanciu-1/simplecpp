#pragma once
#include "api.hpp"
#include "tasks/int64.hpp"

scpp::async::int64_task read_value(trace& log, bool fail);
#include "tasks/string.hpp"
scpp::async::string_task produce_text(scpp::string value, const char*& payload, bool fail);

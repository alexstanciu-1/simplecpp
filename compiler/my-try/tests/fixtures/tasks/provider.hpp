#pragma once
#include "modules/tasks/tasks.hpp"
namespace tasks_probe {
inline scpp::shared_p<scpp::tasks::batch> batch() {
 scpp::vector_t<scpp::int_t<>> values;
 values.push_back(scpp::int_t<>(21));
 return scpp::tasks::start(values, scpp::int_t<>(1), [](scpp::int_t<> value) { return scpp::int_t<>(value.native_value() * 2); });
}
inline scpp::shared_p<scpp::tasks::context> context() {
 auto value = scpp::shared<scpp::tasks::context>();
 value->state = scpp::shared<scpp::tasks::detail::batch_state>();
 return value;
}
inline scpp::string_t context_status(const scpp::shared_p<scpp::tasks::context>& value) {
 std::lock_guard<std::mutex> guard(value->state->status_mutex);
 return value->state->status;
}
inline scpp::shared_p<scpp::tasks::error> error() {
 auto value = scpp::shared<scpp::tasks::error>();
 value->message = scpp::string_t("failed");
 value->kind = scpp::string_t("exception");
 value->key = scpp::mixed_t(scpp::int_t<>(3));
 value->worker_id = scpp::int_t<>(2);
 value->source_file = scpp::string_t("probe.phs");
 value->source_line = scpp::int_t<>(7);
 return value;
}
}

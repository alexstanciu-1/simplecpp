#include "tasks.hpp"

namespace scpp::tasks {

namespace {

[[nodiscard]] shared_p<detail::batch_state> state_or_throw(const shared_p<batch> &resource, const char *name)
{
	if (!resource.has_value().native_value() || !resource->state.has_value().native_value()) {
		throw runtime_error(
			std::string(name) + "(): invalid task batch",
			"invalid_task_batch",
			"scpp::tasks",
			name
		);
	}
	return resource->state;
}

} // namespace

[[nodiscard]] int_t<> progress_info::total() const
{
	return !state.has_value().native_value() ? int_t<>(0) : int_t<>(state->total.load(std::memory_order_relaxed));
}

[[nodiscard]] int_t<> progress_info::completed() const
{
	return !state.has_value().native_value() ? int_t<>(0) : int_t<>(state->completed.load(std::memory_order_relaxed));
}

[[nodiscard]] int_t<> progress_info::queued() const
{
	return !state.has_value().native_value() ? int_t<>(0) : int_t<>(state->queued.load(std::memory_order_relaxed));
}

[[nodiscard]] int_t<> progress_info::active() const
{
	return !state.has_value().native_value() ? int_t<>(0) : int_t<>(state->active.load(std::memory_order_relaxed));
}

[[nodiscard]] int_t<> progress_info::errors() const
{
	return !state.has_value().native_value() ? int_t<>(0) : int_t<>(state->errors.load(std::memory_order_relaxed));
}

[[nodiscard]] bool_t progress_info::stop_requested() const
{
	return bool_t(state.has_value().native_value() && state->stop_requested.load());
}

[[nodiscard]] string_t progress_info::status() const
{
	if (!state.has_value().native_value()) {
		return string_t("");
	}
	std::lock_guard<std::mutex> lock(state->status_mutex);
	return state->status;
}

[[nodiscard]] bool_t done(const shared_p<batch> &resource)
{
	return bool_t(state_or_throw(resource, "task_done")->done.load());
}

void cancel(const shared_p<batch> &resource)
{
	const auto state = state_or_throw(resource, "task_cancel");
	if (!state->done.load()) {
		state->stop_requested.store(true);
	}
}

[[nodiscard]] mixed_t join(const shared_p<batch> &resource)
{
	(void) state_or_throw(resource, "task_join");
	if (!resource->coordinator.has_value().native_value()) {
		throw runtime_error(
			"task_join(): invalid task batch coordinator",
			"invalid_task_batch",
			"scpp::tasks",
			"task_join"
		);
	}

	bool should_join = false;
	{
		std::lock_guard<std::mutex> lock(resource->lifecycle_mutex);
		should_join = !resource->joined.native_value();
		resource->joined = bool_t(true);
	}
	if (should_join && resource->coordinator->joinable()) {
		resource->coordinator->join();
	}

	std::lock_guard<std::mutex> lock(resource->lifecycle_mutex);
	if (resource->error) {
		std::rethrow_exception(resource->error);
	}
	return resource->result;
}

[[nodiscard]] string_t status(const shared_p<batch> &resource)
{
	const auto state = state_or_throw(resource, "task_status");
	std::lock_guard<std::mutex> lock(state->status_mutex);
	return state->status;
}

[[nodiscard]] shared_p<progress_info> progress(const shared_p<batch> &resource)
{
	auto info = shared<progress_info>();
	info->state = state_or_throw(resource, "task_progress");
	return info;
}

void set_status(const shared_p<context> &resource, const string_t &value)
{
	if (!resource.has_value().native_value() || !resource->state.has_value().native_value()) {
		throw runtime_error(
			"task_set_status(): invalid task context",
			"invalid_task_context",
			"scpp::tasks",
			"task_set_status"
		);
	}
	std::lock_guard<std::mutex> lock(resource->state->status_mutex);
	resource->state->status = value;
}

} // namespace scpp::tasks

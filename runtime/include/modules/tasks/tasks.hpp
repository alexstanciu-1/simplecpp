#pragma once

#include "scpp/bool_t.hpp"
#include "scpp/hash_t.hpp"
#include "scpp/int_t.hpp"
#include "scpp/memory.hpp"
#include "scpp/mixed_t.hpp"
#include "scpp/nullable.hpp"
#include "scpp/null_t.hpp"
#include "scpp/runtime_error.hpp"
#include "scpp/shared_p.hpp"
#include "scpp/string_t.hpp"
#include "scpp/vector_t.hpp"

#include <algorithm>
#include <atomic>
#include <chrono>
#include <condition_variable>
#include <cstddef>
#include <cstdint>
#include <exception>
#include <functional>
#include <mutex>
#include <optional>
#include <thread>
#include <type_traits>
#include <utility>
#include <vector>

#ifndef SCPP_HAS_TASKS
#define SCPP_HAS_TASKS 0
#endif

namespace scpp::tasks {

class context;

namespace detail {

struct batch_state final {
	std::atomic<bool> done{false};
	std::atomic<bool> stop_requested{false};
	std::atomic<std::int64_t> total{0};
	std::atomic<std::int64_t> completed{0};
	std::atomic<std::int64_t> queued{0};
	std::atomic<std::int64_t> active{0};
	std::atomic<std::int64_t> errors{0};
	mutable std::mutex status_mutex;
	string_t status;
};

#if defined(__cpp_lib_jthread) && __cpp_lib_jthread >= 201911L
using worker_thread = std::jthread;
#else
using worker_thread = std::thread;
#endif

template <typename T>
struct task_value {
	using type = std::decay_t<T>;
};

template <typename T>
using task_value_t = typename task_value<T>::type;

template <typename TCallback, typename TItem, bool HasContext = std::is_invocable_v<TCallback, TItem, shared_p<context>>>
struct callback_result {
	using type = std::invoke_result_t<TCallback, TItem, shared_p<context>>;
};

template <typename TCallback, typename TItem>
struct callback_result<TCallback, TItem, false> {
	using type = std::invoke_result_t<TCallback, TItem>;
};

template <typename TCallback, typename TItem>
using callback_result_t = typename callback_result<TCallback, TItem>::type;

struct timeout_policy final {
	bool enabled = false;
	std::chrono::steady_clock::time_point deadline{};
};

template <typename TResult>
struct result_vector {
	using type = vector_t<task_value_t<TResult>>;
};

template <>
struct result_vector<void> {
	using type = vector_t<null_t>;
};

template <typename TResult>
using result_vector_t = typename result_vector<TResult>::type;

template <typename TResult, typename TKey>
struct result_hash {
	using type = hash_t<task_value_t<TResult>, TKey>;
};

template <typename TKey>
struct result_hash<void, TKey> {
	using type = hash_t<null_t, TKey>;
};

template <typename TResult, typename TKey>
using result_hash_t = typename result_hash<TResult, TKey>::type;

template <typename TResult>
struct result_slot {
	using type = std::optional<task_value_t<TResult>>;
};

template <>
struct result_slot<void> {
	using type = null_t;
};

template <typename TResult>
using result_slot_t = typename result_slot<TResult>::type;

template <typename TCallback, typename TItem>
decltype(auto) invoke_callback(TCallback &callback, TItem item, const shared_p<context> &worker_context)
{
	if constexpr (std::is_invocable_v<TCallback, TItem, shared_p<context>>) {
		return callback(item, worker_context);
	} else {
		return callback(item);
	}
}

template <typename TPublishCallback, typename TValue>
decltype(auto) invoke_publish_callback(TPublishCallback &callback, TValue &&value, const shared_p<context> &worker_context)
{
	if constexpr (std::is_invocable_v<TPublishCallback, TValue, shared_p<context>>) {
		return callback(std::forward<TValue>(value), worker_context);
	} else {
		return callback(std::forward<TValue>(value));
	}
}

[[nodiscard]] inline std::int64_t elapsed_micros_since(std::chrono::steady_clock::time_point started)
{
	return std::chrono::duration_cast<std::chrono::microseconds>(std::chrono::steady_clock::now() - started).count();
}

[[nodiscard]] inline timeout_policy make_timeout_policy(const int_t<> &timeout_ms)
{
	const auto native_timeout = timeout_ms.native_value();
	if (native_timeout <= 0) {
		return timeout_policy{};
	}
	return timeout_policy{
		true,
		std::chrono::steady_clock::now() + std::chrono::milliseconds(native_timeout),
	};
}

[[nodiscard]] inline bool is_timed_out(const timeout_policy &timeout)
{
	return timeout.enabled && std::chrono::steady_clock::now() >= timeout.deadline;
}

[[noreturn]] inline void throw_timeout()
{
	throw runtime_error(
		"task_run(): task batch timed out",
		"tasks_timeout",
		"scpp::tasks",
		"task_run"
	);
}

} // namespace detail

struct publish_metrics_snapshot final {
	std::int64_t lock_wait_us = 0;
	std::int64_t lock_hold_us = 0;
	std::int64_t callback_us = 0;
	std::int64_t batch_count = 0;
	std::int64_t published_count = 0;
	std::int64_t max_batch_size = 0;
	std::int64_t failed_try_lock_count = 0;
	std::int64_t deferred_flush_count = 0;
};

void reset_publish_metrics();
void record_publish_lock_wait(std::int64_t elapsed_us);
void record_publish_lock_hold(std::int64_t elapsed_us);
void record_publish_callback(std::int64_t elapsed_us, std::int64_t batch_size);
void record_publish_failed_try_lock();
void record_publish_deferred_flush();
[[nodiscard]] publish_metrics_snapshot publish_metrics();
void configure_publish_try_lock(const bool_t &enabled);
[[nodiscard]] bool publish_try_lock_enabled();
[[nodiscard]] int_t<> publish_lock_wait_us();
[[nodiscard]] int_t<> publish_lock_hold_us();
[[nodiscard]] int_t<> publish_callback_us();
[[nodiscard]] int_t<> publish_batch_count();
[[nodiscard]] int_t<> publish_published_count();
[[nodiscard]] int_t<> publish_max_batch_size();
[[nodiscard]] int_t<> publish_failed_try_lock_count();
[[nodiscard]] int_t<> publish_deferred_flush_count();

class batch final {
public:
	shared_p<detail::batch_state> state = shared<detail::batch_state>();
	std::mutex lifecycle_mutex;
	unique_p<detail::worker_thread> coordinator = null;
	mixed_t result = null;
	std::exception_ptr error = nullptr;
	bool_t joined = bool_t(false);
};

class context final {
public:
	shared_p<detail::batch_state> state = null;
	int_t<> worker_id = int_t<>(0);
};

class progress_info final {
public:
	shared_p<detail::batch_state> state = null;

	[[nodiscard]] int_t<> total() const;
	[[nodiscard]] int_t<> completed() const;
	[[nodiscard]] int_t<> queued() const;
	[[nodiscard]] int_t<> active() const;
	[[nodiscard]] int_t<> errors() const;
	[[nodiscard]] bool_t stop_requested() const;
	[[nodiscard]] string_t status() const;
};

class error final {
public:
	string_t message = string_t("");
	string_t kind = string_t("");
	mixed_t key = null;
	int_t<> worker_id = int_t<>(0);
	bool_t timeout = bool_t(false);
	string_t source_file = string_t("");
	int_t<> source_line = int_t<>(0);
};

namespace detail {

template <typename TErrorHandler, typename TItem>
decltype(auto) invoke_error_handler(TErrorHandler &handler, TItem item, const shared_p<error> &event)
{
	return handler(item, event);
}

inline shared_p<error> make_error_event(
	const std::exception &exception,
	const mixed_t &key,
	std::size_t worker_index,
	bool_t timeout = bool_t(false)
)
{
	auto event = shared<error>();
	event->message = string_t(exception.what());
	if (const auto *runtime_exception = dynamic_cast<const runtime_error *>(&exception);
		runtime_exception != nullptr && runtime_exception->code() == "tasks_timeout") {
		event->kind = string_t("timeout");
		event->timeout = bool_t(true);
	} else {
		event->kind = string_t("exception");
		event->timeout = timeout;
	}
	event->key = key;
	event->worker_id = int_t<>(static_cast<std::int64_t>(worker_index));
	return event;
}

inline shared_p<error> make_unknown_error_event(const mixed_t &key, std::size_t worker_index)
{
	auto event = shared<error>();
	event->message = string_t("unknown task error");
	event->kind = string_t("unknown");
	event->key = key;
	event->worker_id = int_t<>(static_cast<std::int64_t>(worker_index));
	return event;
}

template <typename TValue>
[[nodiscard]] mixed_t box_vector_result(const vector_t<TValue> &values)
{
	auto table = unique<hash_t<mixed_t>>();
	for (const auto &value : values.native_value()) {
		(void) table->append(mixed_t(value));
	}
	return mixed_t(std::move(table));
}

template <typename TValue>
[[nodiscard]] mixed_t box_vector_result(vector_t<TValue> &&values)
{
	auto table = unique<hash_t<mixed_t>>();
	for (auto &value : values.native_value()) {
		(void) table->append(mixed_t(std::move(value)));
	}
	return mixed_t(std::move(table));
}

template <>
[[nodiscard]] inline mixed_t box_vector_result<null_t>(const vector_t<null_t> &values)
{
	auto table = unique<hash_t<mixed_t>>();
	for (std::size_t index = 0; index < values.size(); ++index) {
		(void) table->append(null);
	}
	return mixed_t(std::move(table));
}

template <>
[[nodiscard]] inline mixed_t box_vector_result<null_t>(vector_t<null_t> &&values)
{
	auto table = unique<hash_t<mixed_t>>();
	for (std::size_t index = 0; index < values.size(); ++index) {
		(void) table->append(null);
	}
	return mixed_t(std::move(table));
}

template <typename TResultCollection, typename TValue>
void append_result(TResultCollection &result, TValue &&value)
{
	(void) result.append(std::forward<TValue>(value));
}

template <typename TResultCollection, typename TKey, typename TValue>
void set_result(TResultCollection &result, TKey &&key, TValue &&value)
{
	result.set(std::forward<TKey>(key), std::forward<TValue>(value));
}

template <typename TResultValue, typename TValue>
void set_result(vector_t<TResultValue> &result, const int_t<> &key, TValue &&value)
{
	const auto native_key = key.native_value();
	if (native_key < 0) {
		throw runtime_error(
			"task_run(): negative vector result key",
			"invalid_task_result_key",
			"scpp::tasks",
			"task_run"
		);
	}
	const auto index = static_cast<std::size_t>(native_key);
	if (index == result.size()) {
		(void) result.append(std::forward<TValue>(value));
		return;
	}
	if (index > result.size()) {
		throw runtime_error(
			"task_run(): sparse vector result key",
			"invalid_task_result_key",
			"scpp::tasks",
			"task_run"
		);
	}
	result[index] = std::forward<TValue>(value);
}

template <typename TResultValue, typename TValue>
void set_result(vector_t<TResultValue> &result, int_t<> &&key, TValue &&value)
{
	set_result(result, static_cast<const int_t<> &>(key), std::forward<TValue>(value));
}

template <typename TResultCollection, typename TKey, typename TValue>
void set_result(TResultCollection &result, nullable<TKey> key, TValue &&value)
{
	if (key.has_value().native_value()) {
		set_result(result, std::move(key.value()), std::forward<TValue>(value));
		return;
	}
	append_result(result, std::forward<TValue>(value));
}

template <typename TValue, typename TKey>
[[nodiscard]] mixed_t box_hash_result(const hash_t<TValue, TKey> &values)
{
	auto table = unique<hash_t<mixed_t>>();
	for (auto it = values.begin_entries(); it != values.end_entries(); ++it) {
		const auto entry = *it;
		table->set(entry.key(), mixed_t(entry.value_copy()));
	}
	return mixed_t(std::move(table));
}

template <typename TValue, typename TKey>
[[nodiscard]] mixed_t box_hash_result(hash_t<TValue, TKey> &&values)
{
	auto table = unique<hash_t<mixed_t>>();
	for (auto it = values.begin_entries(); it != values.end_entries(); ++it) {
		const auto entry = *it;
		table->set(entry.key(), mixed_t(std::move(entry.value_ref())));
	}
	return mixed_t(std::move(table));
}

template <typename TKey>
[[nodiscard]] mixed_t box_hash_result(const hash_t<null_t, TKey> &values)
{
	auto table = unique<hash_t<mixed_t>>();
	for (auto it = values.begin_entries(); it != values.end_entries(); ++it) {
		const auto entry = *it;
		table->set(entry.key(), null);
	}
	return mixed_t(std::move(table));
}

template <typename TKey>
[[nodiscard]] mixed_t box_hash_result(hash_t<null_t, TKey> &&values)
{
	auto table = unique<hash_t<mixed_t>>();
	for (auto it = values.begin_entries(); it != values.end_entries(); ++it) {
		const auto entry = *it;
		table->set(entry.key(), null);
	}
	return mixed_t(std::move(table));
}

template <typename TValue>
[[nodiscard]] mixed_t box_result_target(const vector_t<TValue> &values)
{
	return box_vector_result(values);
}

template <typename TValue>
[[nodiscard]] mixed_t box_result_target(vector_t<TValue> &&values)
{
	return box_vector_result(std::move(values));
}

template <typename TValue, typename TKey>
[[nodiscard]] mixed_t box_result_target(const hash_t<TValue, TKey> &values)
{
	return box_hash_result(values);
}

template <typename TValue, typename TKey>
[[nodiscard]] mixed_t box_result_target(hash_t<TValue, TKey> &&values)
{
	return box_hash_result(std::move(values));
}

[[nodiscard]] inline const hash_t<mixed_t> *task_input_table_or_null(const mixed_t &items) noexcept
{
	if (const auto *table = items.table_if(); table != nullptr) {
		return table;
	}
	if (const auto *shared_table = items.shared_table_if();
		shared_table != nullptr && shared_table->has_value().native_value()) {
		return shared_table->get();
	}
	if (const auto *dynamic_table = items.dynamic_if();
		dynamic_table != nullptr && dynamic_table->has_value().native_value()) {
		return dynamic_table->get();
	}
	return nullptr;
}

[[nodiscard]] inline const hash_t<mixed_t> &require_task_input_table(const mixed_t &items)
{
	if (const auto *table = task_input_table_or_null(items); table != nullptr) {
		return *table;
	}
	throw runtime_error(
		"task_run(): mixed/dynamic input must resolve to a vector-like or hash-like collection",
		"invalid_task_input_transfer",
		"scpp::tasks",
		"task_run"
	);
}

[[nodiscard]] inline vector_t<mixed_t> mixed_table_to_vector_input(const hash_t<mixed_t> &table)
{
	vector_t<mixed_t> values;
	for (auto it = table.begin_entries(); it != table.end_entries(); ++it) {
		const auto entry = *it;
		values.push_back(entry.value_copy());
	}
	return values;
}

[[nodiscard]] inline hash_t<mixed_t, mixed_t> mixed_table_to_hash_input(const hash_t<mixed_t> &table)
{
	hash_t<mixed_t, mixed_t> values;
	for (auto it = table.begin_entries(); it != table.end_entries(); ++it) {
		const auto entry = *it;
		values.set(entry.key(), entry.value_copy());
	}
	return values;
}

} // namespace detail

[[nodiscard]] bool_t done(const shared_p<batch> &resource);
void cancel(const shared_p<batch> &resource);
[[nodiscard]] mixed_t join(const shared_p<batch> &resource);
[[nodiscard]] string_t status(const shared_p<batch> &resource);
[[nodiscard]] shared_p<progress_info> progress(const shared_p<batch> &resource);
void set_status(const shared_p<context> &resource, const string_t &value);
void configure_default_worker_pool(const int_t<> &workers);
void shutdown_default_worker_pool();
[[nodiscard]] int_t<> default_worker_pool_size();
[[nodiscard]] int_t<> default_worker_pool_live_workers();
[[nodiscard]] int_t<> default_worker_pool_created_workers();
void configure_publish_try_lock(const bool_t &enabled);
[[nodiscard]] int_t<> publish_lock_wait_us();
[[nodiscard]] int_t<> publish_lock_hold_us();
[[nodiscard]] int_t<> publish_callback_us();
[[nodiscard]] int_t<> publish_batch_count();
[[nodiscard]] int_t<> publish_published_count();
[[nodiscard]] int_t<> publish_max_batch_size();
[[nodiscard]] int_t<> publish_failed_try_lock_count();
[[nodiscard]] int_t<> publish_deferred_flush_count();

namespace detail {

using worker_batch_body = std::function<void(std::size_t)>;

void execute_worker_batch(std::size_t worker_count, const worker_batch_body &body);

} // namespace detail

#if SCPP_HAS_TASKS

template <typename TItem, typename TCallback, typename TErrorHandler>
[[nodiscard]] auto run(const vector_t<TItem> &items, const int_t<> &workers, TCallback callback, null_t, null_t, TErrorHandler error_handler, const int_t<> &timeout_ms)
	-> detail::result_vector_t<detail::callback_result_t<TCallback, TItem>>;

template <typename TItem, typename TWorkCallback, typename TPublishCallback, typename TErrorHandler>
[[nodiscard]] int_t<> run_publish(const vector_t<TItem> &items, const int_t<> &workers, TWorkCallback work_callback, TPublishCallback publish_callback, TErrorHandler error_handler, const int_t<> &timeout_ms);

template <typename TItem, typename TCallback, typename TIndexCallback>
[[nodiscard]] auto run(const vector_t<TItem> &items, const int_t<> &workers, TCallback callback, TIndexCallback index_callback, null_t, null_t, const int_t<> &timeout_ms)
	-> hash_t<detail::task_value_t<detail::callback_result_t<TCallback, TItem>>, detail::task_value_t<std::invoke_result_t<TIndexCallback, TItem>>>;

template <typename TItem, typename TCallback, typename TResultCollection, typename TErrorHandler>
[[nodiscard]] TResultCollection run(const vector_t<TItem> &items, const int_t<> &workers, TCallback callback, null_t, TResultCollection result, TErrorHandler error_handler, const int_t<> &timeout_ms)
	requires(!std::is_same_v<std::decay_t<TResultCollection>, null_t>);

template <typename TItem, typename TCallback, typename TIndexCallback, typename TResultCollection, typename TErrorHandler>
[[nodiscard]] TResultCollection run(const vector_t<TItem> &items, const int_t<> &workers, TCallback callback, TIndexCallback index_callback, TResultCollection result, TErrorHandler error_handler, const int_t<> &timeout_ms)
	requires(!std::is_same_v<std::decay_t<TResultCollection>, null_t>);

template <typename TItem, typename TKey, typename TCallback, typename TErrorHandler>
[[nodiscard]] auto run(const hash_t<TItem, TKey> &items, const int_t<> &workers, TCallback callback, null_t, null_t, TErrorHandler error_handler, const int_t<> &timeout_ms)
	-> detail::result_hash_t<detail::callback_result_t<TCallback, TItem>, TKey>;

template <typename TItem, typename TKey, typename TCallback, typename TIndexCallback>
[[nodiscard]] auto run(const hash_t<TItem, TKey> &items, const int_t<> &workers, TCallback callback, TIndexCallback index_callback, null_t, null_t, const int_t<> &timeout_ms)
	-> hash_t<detail::task_value_t<detail::callback_result_t<TCallback, TItem>>, detail::task_value_t<std::invoke_result_t<TIndexCallback, TItem>>>;

template <typename TItem, typename TKey, typename TCallback, typename TResultCollection, typename TErrorHandler>
[[nodiscard]] TResultCollection run(const hash_t<TItem, TKey> &items, const int_t<> &workers, TCallback callback, null_t, TResultCollection result, TErrorHandler error_handler, const int_t<> &timeout_ms)
	requires(!std::is_same_v<std::decay_t<TResultCollection>, null_t>);

template <typename TItem, typename TKey, typename TCallback, typename TIndexCallback, typename TResultCollection, typename TErrorHandler>
[[nodiscard]] TResultCollection run(const hash_t<TItem, TKey> &items, const int_t<> &workers, TCallback callback, TIndexCallback index_callback, TResultCollection result, TErrorHandler error_handler, const int_t<> &timeout_ms)
	requires(!std::is_same_v<std::decay_t<TResultCollection>, null_t>);

template <typename TItem, typename TCallback>
[[nodiscard]] shared_p<batch> start(const vector_t<TItem> &, const int_t<> &, TCallback)
;

template <typename TItem, typename TCallback, typename TErrorHandler>
[[nodiscard]] shared_p<batch> start(const vector_t<TItem> &, const int_t<> &, TCallback, null_t, null_t, TErrorHandler, const int_t<> &)
;

template <typename TItem, typename TCallback, typename TResultCollection, typename TErrorHandler>
[[nodiscard]] shared_p<batch> start(const vector_t<TItem> &, const int_t<> &, TCallback, null_t, TResultCollection, TErrorHandler, const int_t<> &)
	requires(!std::is_same_v<std::decay_t<TResultCollection>, null_t>);

template <typename TItem, typename TCallback, typename TIndexCallback, typename TResultCollection, typename TErrorHandler>
[[nodiscard]] shared_p<batch> start(const vector_t<TItem> &, const int_t<> &, TCallback, TIndexCallback, TResultCollection, TErrorHandler, const int_t<> &)
	requires(!std::is_same_v<std::decay_t<TResultCollection>, null_t>);

template <typename TItem, typename TKey, typename TCallback>
[[nodiscard]] shared_p<batch> start(const hash_t<TItem, TKey> &, const int_t<> &, TCallback)
;

template <typename TItem, typename TKey, typename TCallback, typename TErrorHandler>
[[nodiscard]] shared_p<batch> start(const hash_t<TItem, TKey> &, const int_t<> &, TCallback, null_t, null_t, TErrorHandler, const int_t<> &)
;

template <typename TItem, typename TKey, typename TCallback, typename TResultCollection, typename TErrorHandler>
[[nodiscard]] shared_p<batch> start(const hash_t<TItem, TKey> &, const int_t<> &, TCallback, null_t, TResultCollection, TErrorHandler, const int_t<> &)
	requires(!std::is_same_v<std::decay_t<TResultCollection>, null_t>);

template <typename TItem, typename TKey, typename TCallback, typename TIndexCallback, typename TResultCollection, typename TErrorHandler>
[[nodiscard]] shared_p<batch> start(const hash_t<TItem, TKey> &, const int_t<> &, TCallback, TIndexCallback, TResultCollection, TErrorHandler, const int_t<> &)
	requires(!std::is_same_v<std::decay_t<TResultCollection>, null_t>);

template <typename TItem, typename TCallback>
[[nodiscard]] auto run(const vector_t<TItem> &items, const int_t<> &workers, TCallback callback)
	-> detail::result_vector_t<detail::callback_result_t<TCallback, TItem>>
{
	return run(items, workers, callback, null, null, null, int_t<>(0));
}

template <typename TItem, typename TCallback, typename TErrorHandler>
[[nodiscard]] auto run(const vector_t<TItem> &items, const int_t<> &workers, TCallback callback, null_t, null_t, TErrorHandler error_handler)
	-> detail::result_vector_t<detail::callback_result_t<TCallback, TItem>>
{
	return run(items, workers, callback, null, null, error_handler, int_t<>(0));
}

template <typename TItem, typename TCallback, typename TIndexCallback>
[[nodiscard]] auto run(const vector_t<TItem> &items, const int_t<> &workers, TCallback callback, TIndexCallback index_callback, null_t, null_t)
	-> hash_t<detail::task_value_t<detail::callback_result_t<TCallback, TItem>>, detail::task_value_t<std::invoke_result_t<TIndexCallback, TItem>>>
{
	return run(items, workers, callback, index_callback, null, null, int_t<>(0));
}

template <typename TItem, typename TCallback, typename TResultCollection, typename TErrorHandler>
[[nodiscard]] TResultCollection run(const vector_t<TItem> &items, const int_t<> &workers, TCallback callback, null_t, TResultCollection result, TErrorHandler error_handler)
	requires(!std::is_same_v<std::decay_t<TResultCollection>, null_t>)
{
	return run(items, workers, callback, null, std::move(result), error_handler, int_t<>(0));
}

template <typename TItem, typename TCallback, typename TIndexCallback, typename TResultCollection, typename TErrorHandler>
[[nodiscard]] TResultCollection run(const vector_t<TItem> &items, const int_t<> &workers, TCallback callback, TIndexCallback index_callback, TResultCollection result, TErrorHandler error_handler)
	requires(!std::is_same_v<std::decay_t<TResultCollection>, null_t>)
{
	return run(items, workers, callback, index_callback, std::move(result), error_handler, int_t<>(0));
}

namespace detail {

template <typename TItem, typename TCallback, typename TErrorHandler>
[[nodiscard]] auto run_vector_with_state(
	const vector_t<TItem> &items,
	const int_t<> &workers,
	TCallback callback,
	TErrorHandler error_handler,
	const int_t<> &timeout_ms,
	const shared_p<batch_state> &state
)
	-> result_vector_t<callback_result_t<TCallback, TItem>>
{
	using result_t = callback_result_t<TCallback, TItem>;
	using output_t = result_vector_t<result_t>;

	const auto item_count = items.size();
	output_t results;
	if (item_count == 0) {
		return results;
	}

	const auto requested_workers = workers.native_value();
	const std::size_t native_workers = requested_workers <= 0
		? std::size_t{1}
		: static_cast<std::size_t>(requested_workers);
	const std::size_t worker_count = std::min(native_workers, item_count);

	std::vector<std::exception_ptr> errors(worker_count);
	std::atomic<std::size_t> next_index{0};
	const auto timeout = detail::make_timeout_policy(timeout_ms);
	state->total.store(static_cast<std::int64_t>(item_count), std::memory_order_relaxed);
	state->queued.store(static_cast<std::int64_t>(item_count), std::memory_order_relaxed);
	std::atomic<std::size_t> void_result_count{0};
	std::vector<result_slot_t<result_t>> value_results;
	if constexpr (!std::is_void_v<result_t>) {
		value_results.resize(item_count);
	}

	execute_worker_batch(worker_count, [&](std::size_t worker_index) {
			auto worker_context = shared<context>();
			worker_context->state = state;
			worker_context->worker_id = int_t<>(static_cast<std::int64_t>(worker_index));
			bool active_item = false;
			try {
				while (true) {
					if (is_timed_out(timeout)) {
						state->stop_requested.store(true);
						break;
					}
					if (state->stop_requested.load()) {
						break;
					}
					const std::size_t index = next_index.fetch_add(1, std::memory_order_relaxed);
					if (index >= item_count) {
						break;
					}
					state->queued.fetch_sub(1, std::memory_order_relaxed);
					state->active.fetch_add(1, std::memory_order_relaxed);
					active_item = true;

					try {
						if constexpr (std::is_void_v<result_t>) {
							invoke_callback(callback, items.at(index), worker_context);
							if (is_timed_out(timeout)) {
								state->stop_requested.store(true);
								throw_timeout();
							}
							void_result_count.fetch_add(1, std::memory_order_relaxed);
						} else {
							auto value = invoke_callback(callback, items.at(index), worker_context);
							if (is_timed_out(timeout)) {
								state->stop_requested.store(true);
								throw_timeout();
							}
							value_results.at(index) = std::move(value);
						}
					} catch (const std::exception &exception) {
						state->errors.fetch_add(1, std::memory_order_relaxed);
						if constexpr (std::is_same_v<std::decay_t<TErrorHandler>, null_t>) {
							state->stop_requested.store(true);
							throw;
						} else {
							auto event = make_error_event(exception, mixed_t(int_t<>(static_cast<std::int64_t>(index))), worker_index);
							if constexpr (std::is_void_v<result_t>) {
								invoke_error_handler(error_handler, items.at(index), event);
							} else if constexpr (std::is_void_v<std::invoke_result_t<TErrorHandler, TItem, shared_p<error>>>) {
								invoke_error_handler(error_handler, items.at(index), event);
							} else {
								auto value = invoke_error_handler(error_handler, items.at(index), event);
								value_results.at(index) = std::move(value);
							}
						}
					} catch (...) {
						state->errors.fetch_add(1, std::memory_order_relaxed);
						if constexpr (std::is_same_v<std::decay_t<TErrorHandler>, null_t>) {
							state->stop_requested.store(true);
							throw;
						} else {
							auto event = make_unknown_error_event(mixed_t(int_t<>(static_cast<std::int64_t>(index))), worker_index);
							if constexpr (std::is_void_v<result_t>) {
								invoke_error_handler(error_handler, items.at(index), event);
							} else if constexpr (std::is_void_v<std::invoke_result_t<TErrorHandler, TItem, shared_p<error>>>) {
								invoke_error_handler(error_handler, items.at(index), event);
							} else {
								auto value = invoke_error_handler(error_handler, items.at(index), event);
								value_results.at(index) = std::move(value);
							}
						}
					}
					state->active.fetch_sub(1, std::memory_order_relaxed);
					active_item = false;
					state->completed.fetch_add(1, std::memory_order_relaxed);
				}
			} catch (...) {
				if (active_item) {
					state->active.fetch_sub(1, std::memory_order_relaxed);
				}
				errors.at(worker_index) = std::current_exception();
			}
	});

	for (const auto &entry : errors) {
		if (entry) {
			std::rethrow_exception(entry);
		}
	}

	state->done.store(true);

	if constexpr (std::is_void_v<result_t>) {
		const auto count = void_result_count.load(std::memory_order_relaxed);
		for (std::size_t index = 0; index < count; ++index) {
			results.push_back(null);
		}
	} else {
		for (auto &entry : value_results) {
			if (entry.has_value()) {
				results.push_back(std::move(entry.value()));
			}
		}
	}

	return results;
}

template <typename TItem, typename TWorkCallback, typename TPublishCallback, typename TErrorHandler>
[[nodiscard]] int_t<> run_publish_vector_with_state(
	const vector_t<TItem> &items,
	const int_t<> &workers,
	TWorkCallback work_callback,
	TPublishCallback publish_callback,
	TErrorHandler error_handler,
	const int_t<> &timeout_ms,
	const shared_p<batch_state> &state
)
{
	using result_t = callback_result_t<TWorkCallback, TItem>;
	static_assert(!std::is_void_v<result_t>, "task_run_publish(): work callback must return a value");

	reset_publish_metrics();
	const auto item_count = items.size();
	if (item_count == 0) {
		return int_t<>(0);
	}

	const auto requested_workers = workers.native_value();
	const std::size_t native_workers = requested_workers <= 0
		? std::size_t{1}
		: static_cast<std::size_t>(requested_workers);
	const std::size_t worker_count = std::min(native_workers, item_count);

	std::vector<std::exception_ptr> errors(worker_count);
	std::atomic<std::size_t> next_index{0};
	const auto timeout = detail::make_timeout_policy(timeout_ms);
	state->total.store(static_cast<std::int64_t>(item_count), std::memory_order_relaxed);
	state->queued.store(static_cast<std::int64_t>(item_count), std::memory_order_relaxed);

	std::mutex publish_mutex;
	std::vector<result_slot_t<result_t>> value_results(item_count);
	std::size_t next_publish_index = 0;
	std::size_t published_count = 0;
	auto publish_ready_values = [&](const shared_p<context> &worker_context) {
		vector_t<task_value_t<result_t>> ready_values;
		while (next_publish_index < item_count && value_results.at(next_publish_index).has_value()) {
			ready_values.push_back(std::move(value_results.at(next_publish_index).value()));
			value_results.at(next_publish_index).reset();
			++next_publish_index;
			++published_count;
		}
		if (ready_values.size() > 0) {
			const auto callback_started = std::chrono::steady_clock::now();
			const auto batch_size = static_cast<std::int64_t>(ready_values.size());
			invoke_publish_callback(publish_callback, std::move(ready_values), worker_context);
			record_publish_callback(detail::elapsed_micros_since(callback_started), batch_size);
		}
	};
	using pending_result_t = std::pair<std::size_t, task_value_t<result_t>>;
	auto flush_pending_values = [&](std::vector<pending_result_t> &pending_values, const shared_p<context> &worker_context, bool blocking) -> bool {
		if (pending_values.empty()) {
			return true;
		}
		const bool deferred_flush = publish_try_lock_enabled() && (blocking || pending_values.size() > 1);
		const auto wait_started = std::chrono::steady_clock::now();
		if (blocking) {
			std::unique_lock<std::mutex> lock(publish_mutex);
			record_publish_lock_wait(detail::elapsed_micros_since(wait_started));
			const auto hold_started = std::chrono::steady_clock::now();
			for (auto &pending : pending_values) {
				value_results.at(pending.first) = std::move(pending.second);
			}
			pending_values.clear();
			publish_ready_values(worker_context);
			record_publish_lock_hold(detail::elapsed_micros_since(hold_started));
			if (deferred_flush) {
				record_publish_deferred_flush();
			}
			return true;
		}

		std::unique_lock<std::mutex> lock(publish_mutex, std::try_to_lock);
		if (!lock.owns_lock()) {
			record_publish_failed_try_lock();
			return false;
		}
		record_publish_lock_wait(detail::elapsed_micros_since(wait_started));
		const auto hold_started = std::chrono::steady_clock::now();
		for (auto &pending : pending_values) {
			value_results.at(pending.first) = std::move(pending.second);
		}
		pending_values.clear();
		publish_ready_values(worker_context);
		record_publish_lock_hold(detail::elapsed_micros_since(hold_started));
		if (deferred_flush) {
			record_publish_deferred_flush();
		}
		return true;
	};

	execute_worker_batch(worker_count, [&](std::size_t worker_index) {
		auto worker_context = shared<context>();
		worker_context->state = state;
		worker_context->worker_id = int_t<>(static_cast<std::int64_t>(worker_index));
		std::vector<pending_result_t> pending_values;
		bool active_item = false;
		try {
			while (true) {
				if (is_timed_out(timeout)) {
					state->stop_requested.store(true);
					break;
				}
				if (state->stop_requested.load()) {
					break;
				}
				const std::size_t index = next_index.fetch_add(1, std::memory_order_relaxed);
				if (index >= item_count) {
					break;
				}
				state->queued.fetch_sub(1, std::memory_order_relaxed);
				state->active.fetch_add(1, std::memory_order_relaxed);
				active_item = true;

				try {
					auto value = invoke_callback(work_callback, items.at(index), worker_context);
					if (is_timed_out(timeout)) {
						state->stop_requested.store(true);
						throw_timeout();
					}
					pending_values.emplace_back(index, std::move(value));
					flush_pending_values(pending_values, worker_context, !publish_try_lock_enabled());
				} catch (const std::exception &exception) {
					state->errors.fetch_add(1, std::memory_order_relaxed);
					if constexpr (std::is_same_v<std::decay_t<TErrorHandler>, null_t>) {
						state->stop_requested.store(true);
						throw;
					} else {
						auto event = make_error_event(exception, mixed_t(int_t<>(static_cast<std::int64_t>(index))), worker_index);
						if constexpr (!std::is_void_v<std::invoke_result_t<TErrorHandler, TItem, shared_p<error>>>) {
							auto value = invoke_error_handler(error_handler, items.at(index), event);
							pending_values.emplace_back(index, std::move(value));
							flush_pending_values(pending_values, worker_context, !publish_try_lock_enabled());
						}
					}
				} catch (...) {
					state->errors.fetch_add(1, std::memory_order_relaxed);
					if constexpr (std::is_same_v<std::decay_t<TErrorHandler>, null_t>) {
						state->stop_requested.store(true);
						throw;
					} else {
						auto event = make_unknown_error_event(mixed_t(int_t<>(static_cast<std::int64_t>(index))), worker_index);
						if constexpr (!std::is_void_v<std::invoke_result_t<TErrorHandler, TItem, shared_p<error>>>) {
							auto value = invoke_error_handler(error_handler, items.at(index), event);
							pending_values.emplace_back(index, std::move(value));
							flush_pending_values(pending_values, worker_context, !publish_try_lock_enabled());
						}
					}
				}
				state->active.fetch_sub(1, std::memory_order_relaxed);
				active_item = false;
				state->completed.fetch_add(1, std::memory_order_relaxed);
			}
			flush_pending_values(pending_values, worker_context, true);
		} catch (...) {
			if (active_item) {
				state->active.fetch_sub(1, std::memory_order_relaxed);
			}
			errors.at(worker_index) = std::current_exception();
		}
	});

	for (const auto &entry : errors) {
		if (entry) {
			std::rethrow_exception(entry);
		}
	}
	state->done.store(true);
	return int_t<>(static_cast<std::int64_t>(published_count));
}

} // namespace detail

template <typename TItem, typename TCallback, typename TErrorHandler>
[[nodiscard]] auto run(const vector_t<TItem> &items, const int_t<> &workers, TCallback callback, null_t, null_t, TErrorHandler error_handler, const int_t<> &timeout_ms)
	-> detail::result_vector_t<detail::callback_result_t<TCallback, TItem>>
{
	return detail::run_vector_with_state(items, workers, callback, error_handler, timeout_ms, shared<detail::batch_state>());
}

template <typename TItem, typename TWorkCallback, typename TPublishCallback, typename TErrorHandler>
[[nodiscard]] int_t<> run_publish(const vector_t<TItem> &items, const int_t<> &workers, TWorkCallback work_callback, TPublishCallback publish_callback, TErrorHandler error_handler, const int_t<> &timeout_ms)
{
	return detail::run_publish_vector_with_state(items, workers, work_callback, publish_callback, error_handler, timeout_ms, shared<detail::batch_state>());
}

template <typename TItem, typename TWorkCallback, typename TPublishCallback>
[[nodiscard]] int_t<> run_publish(const vector_t<TItem> &items, const int_t<> &workers, TWorkCallback work_callback, TPublishCallback publish_callback)
{
	return run_publish(items, workers, work_callback, publish_callback, null, int_t<>(0));
}

template <typename TItem, typename TCallback, typename TIndexCallback>
[[nodiscard]] auto run(const vector_t<TItem> &items, const int_t<> &workers, TCallback callback, TIndexCallback index_callback, null_t, null_t, const int_t<> &timeout_ms)
	-> hash_t<detail::task_value_t<detail::callback_result_t<TCallback, TItem>>, detail::task_value_t<std::invoke_result_t<TIndexCallback, TItem>>>
{
	using result_t = detail::callback_result_t<TCallback, TItem>;
	using value_t = detail::task_value_t<result_t>;
	using key_t = detail::task_value_t<std::invoke_result_t<TIndexCallback, TItem>>;

	auto values = detail::run_vector_with_state(items, workers, callback, null, timeout_ms, shared<detail::batch_state>());
	hash_t<value_t, key_t> keyed;
	for (std::size_t index = 0; index < values.size(); ++index) {
		key_t key = index_callback(items.at(index));
		keyed.set(std::move(key), std::move(values.at(index)));
	}
	return keyed;
}

template <typename TItem, typename TCallback, typename TResultCollection, typename TErrorHandler>
[[nodiscard]] TResultCollection run(const vector_t<TItem> &items, const int_t<> &workers, TCallback callback, null_t, TResultCollection result, TErrorHandler error_handler, const int_t<> &timeout_ms)
	requires(!std::is_same_v<std::decay_t<TResultCollection>, null_t>)
{
	auto values = detail::run_vector_with_state(items, workers, callback, error_handler, timeout_ms, shared<detail::batch_state>());
	for (auto &value : values.native_value()) {
		detail::append_result(result, std::move(value));
	}
	return result;
}

template <typename TItem, typename TCallback, typename TIndexCallback, typename TResultCollection, typename TErrorHandler>
[[nodiscard]] TResultCollection run(const vector_t<TItem> &items, const int_t<> &workers, TCallback callback, TIndexCallback index_callback, TResultCollection result, TErrorHandler error_handler, const int_t<> &timeout_ms)
	requires(!std::is_same_v<std::decay_t<TResultCollection>, null_t>)
{
	auto values = detail::run_vector_with_state(items, workers, callback, error_handler, timeout_ms, shared<detail::batch_state>());
	for (std::size_t index = 0; index < values.size(); ++index) {
		auto key = index_callback(items.at(index));
		detail::set_result(result, std::move(key), std::move(values.at(index)));
	}
	return result;
}

template <typename TItem, typename TCallback>
[[nodiscard]] shared_p<batch> start(const vector_t<TItem> &items, const int_t<> &workers, TCallback callback)
{
	return start(items, workers, callback, null, null, null, int_t<>(0));
}

template <typename TItem, typename TCallback, typename TErrorHandler>
[[nodiscard]] shared_p<batch> start(const vector_t<TItem> &items, const int_t<> &workers, TCallback callback, null_t, null_t, TErrorHandler error_handler)
{
	return start(items, workers, callback, null, null, error_handler, int_t<>(0));
}

template <typename TItem, typename TCallback, typename TResultCollection, typename TErrorHandler>
[[nodiscard]] shared_p<batch> start(const vector_t<TItem> &items, const int_t<> &workers, TCallback callback, null_t, TResultCollection result, TErrorHandler error_handler)
	requires(!std::is_same_v<std::decay_t<TResultCollection>, null_t>)
{
	return start(items, workers, callback, null, std::move(result), error_handler, int_t<>(0));
}

template <typename TItem, typename TCallback, typename TIndexCallback, typename TResultCollection, typename TErrorHandler>
[[nodiscard]] shared_p<batch> start(const vector_t<TItem> &items, const int_t<> &workers, TCallback callback, TIndexCallback index_callback, TResultCollection result, TErrorHandler error_handler)
	requires(!std::is_same_v<std::decay_t<TResultCollection>, null_t>)
{
	return start(items, workers, callback, index_callback, std::move(result), error_handler, int_t<>(0));
}

template <typename TItem, typename TCallback, typename TErrorHandler>
[[nodiscard]] shared_p<batch> start(const vector_t<TItem> &items, const int_t<> &workers, TCallback callback, null_t, null_t, TErrorHandler error_handler, const int_t<> &timeout_ms)
{
	auto resource = shared<batch>();
	resource->state->total.store(static_cast<std::int64_t>(items.size()), std::memory_order_relaxed);
	resource->state->queued.store(static_cast<std::int64_t>(items.size()), std::memory_order_relaxed);
	resource->coordinator = unique<detail::worker_thread>([resource, items, workers, callback, error_handler, timeout_ms]() mutable {
		try {
			auto values = detail::run_vector_with_state(items, workers, callback, error_handler, timeout_ms, resource->state);
			std::lock_guard<std::mutex> lock(resource->lifecycle_mutex);
			resource->result = detail::box_vector_result(std::move(values));
			resource->state->done.store(true);
		} catch (...) {
			std::lock_guard<std::mutex> lock(resource->lifecycle_mutex);
			resource->error = std::current_exception();
			resource->state->done.store(true);
			resource->state->stop_requested.store(true);
		}
	});
	return resource;
}

template <typename TItem, typename TCallback, typename TResultCollection, typename TErrorHandler>
[[nodiscard]] shared_p<batch> start(const vector_t<TItem> &items, const int_t<> &workers, TCallback callback, null_t, TResultCollection result, TErrorHandler error_handler, const int_t<> &timeout_ms)
	requires(!std::is_same_v<std::decay_t<TResultCollection>, null_t>)
{
	auto resource = shared<batch>();
	resource->state->total.store(static_cast<std::int64_t>(items.size()), std::memory_order_relaxed);
	resource->state->queued.store(static_cast<std::int64_t>(items.size()), std::memory_order_relaxed);
	resource->coordinator = unique<detail::worker_thread>([resource, items, workers, callback, result = std::move(result), error_handler, timeout_ms]() mutable {
		try {
			auto values = detail::run_vector_with_state(items, workers, callback, error_handler, timeout_ms, resource->state);
			for (auto &value : values.native_value()) {
				detail::append_result(result, std::move(value));
			}
			std::lock_guard<std::mutex> lock(resource->lifecycle_mutex);
			resource->result = detail::box_result_target(std::move(result));
			resource->state->done.store(true);
		} catch (...) {
			std::lock_guard<std::mutex> lock(resource->lifecycle_mutex);
			resource->error = std::current_exception();
			resource->state->done.store(true);
			resource->state->stop_requested.store(true);
		}
	});
	return resource;
}

template <typename TItem, typename TCallback, typename TIndexCallback, typename TResultCollection, typename TErrorHandler>
[[nodiscard]] shared_p<batch> start(const vector_t<TItem> &items, const int_t<> &workers, TCallback callback, TIndexCallback index_callback, TResultCollection result, TErrorHandler error_handler, const int_t<> &timeout_ms)
	requires(!std::is_same_v<std::decay_t<TResultCollection>, null_t>)
{
	auto resource = shared<batch>();
	resource->state->total.store(static_cast<std::int64_t>(items.size()), std::memory_order_relaxed);
	resource->state->queued.store(static_cast<std::int64_t>(items.size()), std::memory_order_relaxed);
	resource->coordinator = unique<detail::worker_thread>([resource, items, workers, callback, index_callback, result = std::move(result), error_handler, timeout_ms]() mutable {
		try {
			auto values = detail::run_vector_with_state(items, workers, callback, error_handler, timeout_ms, resource->state);
			for (std::size_t index = 0; index < values.size(); ++index) {
				auto key = index_callback(items.at(index));
				detail::set_result(result, std::move(key), std::move(values.at(index)));
			}
			std::lock_guard<std::mutex> lock(resource->lifecycle_mutex);
			resource->result = detail::box_result_target(std::move(result));
			resource->state->done.store(true);
		} catch (...) {
			std::lock_guard<std::mutex> lock(resource->lifecycle_mutex);
			resource->error = std::current_exception();
			resource->state->done.store(true);
			resource->state->stop_requested.store(true);
		}
	});
	return resource;
}

template <typename TItem, typename TKey, typename TCallback>
[[nodiscard]] auto run(const hash_t<TItem, TKey> &items, const int_t<> &workers, TCallback callback)
	-> detail::result_hash_t<detail::callback_result_t<TCallback, TItem>, TKey>
{
	return run(items, workers, callback, null, null, null, int_t<>(0));
}

template <typename TItem, typename TKey, typename TCallback, typename TErrorHandler>
[[nodiscard]] auto run(const hash_t<TItem, TKey> &items, const int_t<> &workers, TCallback callback, null_t, null_t, TErrorHandler error_handler)
	-> detail::result_hash_t<detail::callback_result_t<TCallback, TItem>, TKey>
{
	return run(items, workers, callback, null, null, error_handler, int_t<>(0));
}

template <typename TItem, typename TKey, typename TCallback, typename TIndexCallback>
[[nodiscard]] auto run(const hash_t<TItem, TKey> &items, const int_t<> &workers, TCallback callback, TIndexCallback index_callback, null_t, null_t)
	-> hash_t<detail::task_value_t<detail::callback_result_t<TCallback, TItem>>, detail::task_value_t<std::invoke_result_t<TIndexCallback, TItem>>>
{
	return run(items, workers, callback, index_callback, null, null, int_t<>(0));
}

template <typename TItem, typename TKey, typename TCallback, typename TResultCollection, typename TErrorHandler>
[[nodiscard]] TResultCollection run(const hash_t<TItem, TKey> &items, const int_t<> &workers, TCallback callback, null_t, TResultCollection result, TErrorHandler error_handler)
	requires(!std::is_same_v<std::decay_t<TResultCollection>, null_t>)
{
	return run(items, workers, callback, null, std::move(result), error_handler, int_t<>(0));
}

template <typename TItem, typename TKey, typename TCallback, typename TIndexCallback, typename TResultCollection, typename TErrorHandler>
[[nodiscard]] TResultCollection run(const hash_t<TItem, TKey> &items, const int_t<> &workers, TCallback callback, TIndexCallback index_callback, TResultCollection result, TErrorHandler error_handler)
	requires(!std::is_same_v<std::decay_t<TResultCollection>, null_t>)
{
	return run(items, workers, callback, index_callback, std::move(result), error_handler, int_t<>(0));
}

template <typename TItem, typename TKey, typename TCallback, typename TErrorHandler>
[[nodiscard]] auto run_hash_with_state(
	const hash_t<TItem, TKey> &items,
	const int_t<> &workers,
	TCallback callback,
	TErrorHandler error_handler,
	const int_t<> &timeout_ms,
	const shared_p<detail::batch_state> &state
)
	-> detail::result_hash_t<detail::callback_result_t<TCallback, TItem>, TKey>
{
	using result_t = detail::callback_result_t<TCallback, TItem>;
	using output_t = detail::result_hash_t<result_t, TKey>;

	std::vector<std::pair<TKey, TItem>> input;
	input.reserve(items.size());
	for (auto it = items.begin_entries(); it != items.end_entries(); ++it) {
		const auto entry = *it;
		input.emplace_back(entry.key(), entry.value_copy());
	}

	const auto item_count = input.size();
	output_t results;
	if (item_count == 0) {
		return results;
	}

	const auto requested_workers = workers.native_value();
	const std::size_t native_workers = requested_workers <= 0
		? std::size_t{1}
		: static_cast<std::size_t>(requested_workers);
	const std::size_t worker_count = std::min(native_workers, item_count);

	std::vector<std::exception_ptr> errors(worker_count);
	std::atomic<std::size_t> next_index{0};
	const auto timeout = detail::make_timeout_policy(timeout_ms);
	state->total.store(static_cast<std::int64_t>(item_count), std::memory_order_relaxed);
	state->queued.store(static_cast<std::int64_t>(item_count), std::memory_order_relaxed);
	std::mutex result_mutex;
	std::vector<std::pair<TKey, detail::task_value_t<result_t>>> value_results;
	value_results.reserve(item_count);
	std::vector<std::pair<TKey, null_t>> void_results;
	void_results.reserve(item_count);

	detail::execute_worker_batch(worker_count, [&](std::size_t worker_index) {
			auto worker_context = shared<context>();
			worker_context->state = state;
			worker_context->worker_id = int_t<>(static_cast<std::int64_t>(worker_index));
			bool active_item = false;
			try {
				while (true) {
					if (detail::is_timed_out(timeout)) {
						state->stop_requested.store(true);
						break;
					}
					if (state->stop_requested.load()) {
						break;
					}
					const std::size_t index = next_index.fetch_add(1, std::memory_order_relaxed);
					if (index >= item_count) {
						break;
					}
					state->queued.fetch_sub(1, std::memory_order_relaxed);
					state->active.fetch_add(1, std::memory_order_relaxed);
					active_item = true;

					const auto &entry = input.at(index);
					const auto &key = entry.first;
					const auto &item = entry.second;
					try {
						if constexpr (std::is_void_v<result_t>) {
							detail::invoke_callback(callback, item, worker_context);
							if (detail::is_timed_out(timeout)) {
								state->stop_requested.store(true);
								detail::throw_timeout();
							}
							std::lock_guard<std::mutex> lock(result_mutex);
							void_results.emplace_back(key, null);
						} else {
							auto value = detail::invoke_callback(callback, item, worker_context);
							if (detail::is_timed_out(timeout)) {
								state->stop_requested.store(true);
								detail::throw_timeout();
							}
							std::lock_guard<std::mutex> lock(result_mutex);
							value_results.emplace_back(key, std::move(value));
						}
					} catch (const std::exception &exception) {
						state->errors.fetch_add(1, std::memory_order_relaxed);
						if constexpr (std::is_same_v<std::decay_t<TErrorHandler>, null_t>) {
							state->stop_requested.store(true);
							throw;
						} else {
							auto event = detail::make_error_event(exception, mixed_t(key), worker_index);
							if constexpr (std::is_void_v<result_t>) {
								detail::invoke_error_handler(error_handler, item, event);
							} else if constexpr (std::is_void_v<std::invoke_result_t<TErrorHandler, TItem, shared_p<error>>>) {
								detail::invoke_error_handler(error_handler, item, event);
							} else {
								auto value = detail::invoke_error_handler(error_handler, item, event);
								std::lock_guard<std::mutex> lock(result_mutex);
								value_results.emplace_back(key, std::move(value));
							}
						}
					} catch (...) {
						state->errors.fetch_add(1, std::memory_order_relaxed);
						if constexpr (std::is_same_v<std::decay_t<TErrorHandler>, null_t>) {
							state->stop_requested.store(true);
							throw;
						} else {
							auto event = detail::make_unknown_error_event(mixed_t(key), worker_index);
							if constexpr (std::is_void_v<result_t>) {
								detail::invoke_error_handler(error_handler, item, event);
							} else if constexpr (std::is_void_v<std::invoke_result_t<TErrorHandler, TItem, shared_p<error>>>) {
								detail::invoke_error_handler(error_handler, item, event);
							} else {
								auto value = detail::invoke_error_handler(error_handler, item, event);
								std::lock_guard<std::mutex> lock(result_mutex);
								value_results.emplace_back(key, std::move(value));
							}
						}
					}
					state->active.fetch_sub(1, std::memory_order_relaxed);
					active_item = false;
					state->completed.fetch_add(1, std::memory_order_relaxed);
				}
			} catch (...) {
				if (active_item) {
					state->active.fetch_sub(1, std::memory_order_relaxed);
				}
				errors.at(worker_index) = std::current_exception();
			}
	});

	for (const auto &entry : errors) {
		if (entry) {
			std::rethrow_exception(entry);
		}
	}
	state->done.store(true);

	if constexpr (std::is_void_v<result_t>) {
		for (auto &entry : void_results) {
			results.set(std::move(entry.first), null);
		}
	} else {
		for (auto &entry : value_results) {
			results.set(std::move(entry.first), std::move(entry.second));
		}
	}

	return results;
}

template <typename TItem, typename TKey, typename TCallback, typename TErrorHandler>
[[nodiscard]] auto run(const hash_t<TItem, TKey> &items, const int_t<> &workers, TCallback callback, null_t, null_t, TErrorHandler error_handler, const int_t<> &timeout_ms)
	-> detail::result_hash_t<detail::callback_result_t<TCallback, TItem>, TKey>
{
	return run_hash_with_state(items, workers, callback, error_handler, timeout_ms, shared<detail::batch_state>());
}

template <typename TItem, typename TKey, typename TCallback, typename TResultCollection, typename TErrorHandler>
[[nodiscard]] TResultCollection run(const hash_t<TItem, TKey> &items, const int_t<> &workers, TCallback callback, null_t, TResultCollection result, TErrorHandler error_handler, const int_t<> &timeout_ms)
	requires(!std::is_same_v<std::decay_t<TResultCollection>, null_t>)
{
	auto values = run_hash_with_state(items, workers, callback, error_handler, timeout_ms, shared<detail::batch_state>());
	for (auto it = values.begin_entries(); it != values.end_entries(); ++it) {
		const auto entry = *it;
		detail::set_result(result, entry.key(), entry.value_copy());
	}
	return result;
}

template <typename TItem, typename TKey, typename TCallback>
[[nodiscard]] shared_p<batch> start(const hash_t<TItem, TKey> &items, const int_t<> &workers, TCallback callback)
{
	return start(items, workers, callback, null, null, null, int_t<>(0));
}

template <typename TItem, typename TKey, typename TCallback, typename TErrorHandler>
[[nodiscard]] shared_p<batch> start(const hash_t<TItem, TKey> &items, const int_t<> &workers, TCallback callback, null_t, null_t, TErrorHandler error_handler)
{
	return start(items, workers, callback, null, null, error_handler, int_t<>(0));
}

template <typename TItem, typename TKey, typename TCallback, typename TResultCollection, typename TErrorHandler>
[[nodiscard]] shared_p<batch> start(const hash_t<TItem, TKey> &items, const int_t<> &workers, TCallback callback, null_t, TResultCollection result, TErrorHandler error_handler)
	requires(!std::is_same_v<std::decay_t<TResultCollection>, null_t>)
{
	return start(items, workers, callback, null, std::move(result), error_handler, int_t<>(0));
}

template <typename TItem, typename TKey, typename TCallback, typename TIndexCallback, typename TResultCollection, typename TErrorHandler>
[[nodiscard]] shared_p<batch> start(const hash_t<TItem, TKey> &items, const int_t<> &workers, TCallback callback, TIndexCallback index_callback, TResultCollection result, TErrorHandler error_handler)
	requires(!std::is_same_v<std::decay_t<TResultCollection>, null_t>)
{
	return start(items, workers, callback, index_callback, std::move(result), error_handler, int_t<>(0));
}

template <typename TItem, typename TKey, typename TCallback, typename TErrorHandler>
[[nodiscard]] shared_p<batch> start(const hash_t<TItem, TKey> &items, const int_t<> &workers, TCallback callback, null_t, null_t, TErrorHandler error_handler, const int_t<> &timeout_ms)
{
	auto resource = shared<batch>();
	resource->state->total.store(static_cast<std::int64_t>(items.size()), std::memory_order_relaxed);
	resource->state->queued.store(static_cast<std::int64_t>(items.size()), std::memory_order_relaxed);
	resource->coordinator = unique<detail::worker_thread>([resource, items, workers, callback, error_handler, timeout_ms]() mutable {
		try {
			auto values = run_hash_with_state(items, workers, callback, error_handler, timeout_ms, resource->state);
			std::lock_guard<std::mutex> lock(resource->lifecycle_mutex);
			resource->result = detail::box_hash_result(std::move(values));
			resource->state->done.store(true);
		} catch (...) {
			std::lock_guard<std::mutex> lock(resource->lifecycle_mutex);
			resource->error = std::current_exception();
			resource->state->done.store(true);
			resource->state->stop_requested.store(true);
		}
	});
	return resource;
}

template <typename TItem, typename TKey, typename TCallback, typename TResultCollection, typename TErrorHandler>
[[nodiscard]] shared_p<batch> start(const hash_t<TItem, TKey> &items, const int_t<> &workers, TCallback callback, null_t, TResultCollection result, TErrorHandler error_handler, const int_t<> &timeout_ms)
	requires(!std::is_same_v<std::decay_t<TResultCollection>, null_t>)
{
	auto resource = shared<batch>();
	resource->state->total.store(static_cast<std::int64_t>(items.size()), std::memory_order_relaxed);
	resource->state->queued.store(static_cast<std::int64_t>(items.size()), std::memory_order_relaxed);
	resource->coordinator = unique<detail::worker_thread>([resource, items, workers, callback, result = std::move(result), error_handler, timeout_ms]() mutable {
		try {
			auto values = run_hash_with_state(items, workers, callback, error_handler, timeout_ms, resource->state);
			for (auto it = values.begin_entries(); it != values.end_entries(); ++it) {
				const auto entry = *it;
				detail::set_result(result, entry.key(), entry.value_copy());
			}
			std::lock_guard<std::mutex> lock(resource->lifecycle_mutex);
			resource->result = detail::box_result_target(std::move(result));
			resource->state->done.store(true);
		} catch (...) {
			std::lock_guard<std::mutex> lock(resource->lifecycle_mutex);
			resource->error = std::current_exception();
			resource->state->done.store(true);
			resource->state->stop_requested.store(true);
		}
	});
	return resource;
}

template <typename TItem, typename TKey, typename TCallback, typename TIndexCallback>
[[nodiscard]] auto run(const hash_t<TItem, TKey> &items, const int_t<> &workers, TCallback callback, TIndexCallback index_callback, null_t, null_t, const int_t<> &timeout_ms)
	-> hash_t<detail::task_value_t<detail::callback_result_t<TCallback, TItem>>, detail::task_value_t<std::invoke_result_t<TIndexCallback, TItem>>>
{
	using result_t = detail::callback_result_t<TCallback, TItem>;
	using value_t = detail::task_value_t<result_t>;
	using index_key_t = detail::task_value_t<std::invoke_result_t<TIndexCallback, TItem>>;

	auto values = run(items, workers, callback, null, null, null, timeout_ms);
	hash_t<value_t, index_key_t> keyed;
	for (auto it = items.begin_entries(); it != items.end_entries(); ++it) {
		const auto entry = *it;
		index_key_t key = index_callback(entry.value_copy());
		keyed.set(std::move(key), std::move(values[entry.key()]));
	}
	return keyed;
}

template <typename TItem, typename TKey, typename TCallback, typename TIndexCallback, typename TResultCollection, typename TErrorHandler>
[[nodiscard]] shared_p<batch> start(const hash_t<TItem, TKey> &items, const int_t<> &workers, TCallback callback, TIndexCallback index_callback, TResultCollection result, TErrorHandler error_handler, const int_t<> &timeout_ms)
	requires(!std::is_same_v<std::decay_t<TResultCollection>, null_t>)
{
	auto resource = shared<batch>();
	resource->state->total.store(static_cast<std::int64_t>(items.size()), std::memory_order_relaxed);
	resource->state->queued.store(static_cast<std::int64_t>(items.size()), std::memory_order_relaxed);
	resource->coordinator = unique<detail::worker_thread>([resource, items, workers, callback, index_callback, result = std::move(result), error_handler, timeout_ms]() mutable {
		try {
			auto values = run_hash_with_state(items, workers, callback, error_handler, timeout_ms, resource->state);
			for (auto it = items.begin_entries(); it != items.end_entries(); ++it) {
				const auto entry = *it;
				auto key = index_callback(entry.value_copy());
				detail::set_result(result, std::move(key), std::move(values[entry.key()]));
			}
			std::lock_guard<std::mutex> lock(resource->lifecycle_mutex);
			resource->result = detail::box_result_target(std::move(result));
			resource->state->done.store(true);
		} catch (...) {
			std::lock_guard<std::mutex> lock(resource->lifecycle_mutex);
			resource->error = std::current_exception();
			resource->state->done.store(true);
			resource->state->stop_requested.store(true);
		}
	});
	return resource;
}

template <typename TItem, typename TKey, typename TCallback, typename TIndexCallback, typename TResultCollection, typename TErrorHandler>
[[nodiscard]] TResultCollection run(const hash_t<TItem, TKey> &items, const int_t<> &workers, TCallback callback, TIndexCallback index_callback, TResultCollection result, TErrorHandler error_handler, const int_t<> &timeout_ms)
	requires(!std::is_same_v<std::decay_t<TResultCollection>, null_t>)
{
	auto values = run_hash_with_state(items, workers, callback, error_handler, timeout_ms, shared<detail::batch_state>());
	for (auto it = items.begin_entries(); it != items.end_entries(); ++it) {
		const auto entry = *it;
		auto key = index_callback(entry.value_copy());
		detail::set_result(result, std::move(key), std::move(values[entry.key()]));
	}
	return result;
}

template <typename TCallback, typename TErrorHandler>
[[nodiscard]] mixed_t run(const mixed_t &items, const int_t<> &workers, TCallback callback, null_t, null_t, TErrorHandler error_handler, const int_t<> &timeout_ms)
{
	const auto &table = detail::require_task_input_table(items);
	if (table.is_packed().native_value()) {
		auto values = detail::mixed_table_to_vector_input(table);
		return detail::box_vector_result(detail::run_vector_with_state(values, workers, callback, error_handler, timeout_ms, shared<detail::batch_state>()));
	}
	auto values = detail::mixed_table_to_hash_input(table);
	return detail::box_hash_result(run_hash_with_state(values, workers, callback, error_handler, timeout_ms, shared<detail::batch_state>()));
}

template <typename TCallback>
[[nodiscard]] mixed_t run(const mixed_t &items, const int_t<> &workers, TCallback callback)
{
	return run(items, workers, callback, null, null, null, int_t<>(0));
}

template <typename TCallback, typename TErrorHandler>
[[nodiscard]] mixed_t run(const mixed_t &items, const int_t<> &workers, TCallback callback, null_t, null_t, TErrorHandler error_handler)
{
	return run(items, workers, callback, null, null, error_handler, int_t<>(0));
}

template <typename TCallback, typename TErrorHandler>
[[nodiscard]] shared_p<batch> start(const mixed_t &items, const int_t<> &workers, TCallback callback, null_t, null_t, TErrorHandler error_handler, const int_t<> &timeout_ms)
{
	(void) detail::require_task_input_table(items);
	auto resource = shared<batch>();
	resource->coordinator = unique<detail::worker_thread>([resource, items, workers, callback, error_handler, timeout_ms]() mutable {
		try {
			resource->result = run(items, workers, callback, null, null, error_handler, timeout_ms);
			resource->state->done.store(true);
		} catch (...) {
			std::lock_guard<std::mutex> lock(resource->lifecycle_mutex);
			resource->error = std::current_exception();
			resource->state->done.store(true);
			resource->state->stop_requested.store(true);
		}
	});
	return resource;
}

template <typename TCallback>
[[nodiscard]] shared_p<batch> start(const mixed_t &items, const int_t<> &workers, TCallback callback)
{
	return start(items, workers, callback, null, null, null, int_t<>(0));
}

template <typename TCallback, typename TErrorHandler>
[[nodiscard]] shared_p<batch> start(const mixed_t &items, const int_t<> &workers, TCallback callback, null_t, null_t, TErrorHandler error_handler)
{
	return start(items, workers, callback, null, null, error_handler, int_t<>(0));
}

#else

[[nodiscard]] inline bool_t done(const shared_p<batch> &)
{
	throw runtime_error(
		"task_done(): tasks runtime module is not enabled in this build",
		"tasks_module_disabled",
		"scpp::tasks",
		"task_done"
	);
}

inline void cancel(const shared_p<batch> &)
{
	throw runtime_error(
		"task_cancel(): tasks runtime module is not enabled in this build",
		"tasks_module_disabled",
		"scpp::tasks",
		"task_cancel"
	);
}

[[nodiscard]] inline mixed_t join(const shared_p<batch> &)
{
	throw runtime_error(
		"task_join(): tasks runtime module is not enabled in this build",
		"tasks_module_disabled",
		"scpp::tasks",
		"task_join"
	);
}

[[nodiscard]] inline string_t status(const shared_p<batch> &)
{
	throw runtime_error(
		"task_status(): tasks runtime module is not enabled in this build",
		"tasks_module_disabled",
		"scpp::tasks",
		"task_status"
	);
}

[[nodiscard]] inline shared_p<progress_info> progress(const shared_p<batch> &)
{
	throw runtime_error(
		"task_progress(): tasks runtime module is not enabled in this build",
		"tasks_module_disabled",
		"scpp::tasks",
		"task_progress"
	);
}

inline void set_status(const shared_p<context> &, const string_t &)
{
	throw runtime_error(
		"task_set_status(): tasks runtime module is not enabled in this build",
		"tasks_module_disabled",
		"scpp::tasks",
		"task_set_status"
	);
}

template <typename TItem, typename TCallback, typename TErrorHandler>
[[nodiscard]] auto run(const vector_t<TItem> &, const int_t<> &, TCallback, null_t, null_t, TErrorHandler, const int_t<> &)
	-> detail::result_vector_t<detail::callback_result_t<TCallback, TItem>>;

template <typename TItem, typename TKey, typename TCallback, typename TErrorHandler>
[[nodiscard]] auto run(const hash_t<TItem, TKey> &, const int_t<> &, TCallback, null_t, null_t, TErrorHandler, const int_t<> &)
	-> detail::result_hash_t<detail::callback_result_t<TCallback, TItem>, TKey>;

template <typename TItem, typename TCallback>
[[nodiscard]] shared_p<batch> start(const vector_t<TItem> &, const int_t<> &, TCallback)
{
	throw runtime_error(
		"task_start(): tasks runtime module is not enabled in this build",
		"tasks_module_disabled",
		"scpp::tasks",
		"task_start"
	);
}

template <typename TItem, typename TCallback, typename TErrorHandler>
[[nodiscard]] shared_p<batch> start(const vector_t<TItem> &, const int_t<> &, TCallback, null_t, null_t, TErrorHandler)
{
	throw runtime_error(
		"task_start(): tasks runtime module is not enabled in this build",
		"tasks_module_disabled",
		"scpp::tasks",
		"task_start"
	);
}

template <typename TItem, typename TCallback, typename TErrorHandler>
[[nodiscard]] shared_p<batch> start(const vector_t<TItem> &, const int_t<> &, TCallback, null_t, null_t, TErrorHandler, const int_t<> &)
{
	throw runtime_error(
		"task_start(): tasks runtime module is not enabled in this build",
		"tasks_module_disabled",
		"scpp::tasks",
		"task_start"
	);
}

template <typename TItem, typename TCallback, typename TResultCollection, typename TErrorHandler>
[[nodiscard]] shared_p<batch> start(const vector_t<TItem> &, const int_t<> &, TCallback, null_t, TResultCollection, TErrorHandler)
	requires(!std::is_same_v<std::decay_t<TResultCollection>, null_t>)
{
	throw runtime_error(
		"task_start(): tasks runtime module is not enabled in this build",
		"tasks_module_disabled",
		"scpp::tasks",
		"task_start"
	);
}

template <typename TItem, typename TCallback, typename TResultCollection, typename TErrorHandler>
[[nodiscard]] shared_p<batch> start(const vector_t<TItem> &, const int_t<> &, TCallback, null_t, TResultCollection, TErrorHandler, const int_t<> &)
	requires(!std::is_same_v<std::decay_t<TResultCollection>, null_t>)
{
	throw runtime_error(
		"task_start(): tasks runtime module is not enabled in this build",
		"tasks_module_disabled",
		"scpp::tasks",
		"task_start"
	);
}

template <typename TItem, typename TCallback, typename TIndexCallback, typename TResultCollection, typename TErrorHandler>
[[nodiscard]] shared_p<batch> start(const vector_t<TItem> &, const int_t<> &, TCallback, TIndexCallback, TResultCollection, TErrorHandler)
	requires(!std::is_same_v<std::decay_t<TResultCollection>, null_t>)
{
	throw runtime_error(
		"task_start(): tasks runtime module is not enabled in this build",
		"tasks_module_disabled",
		"scpp::tasks",
		"task_start"
	);
}

template <typename TItem, typename TCallback, typename TIndexCallback, typename TResultCollection, typename TErrorHandler>
[[nodiscard]] shared_p<batch> start(const vector_t<TItem> &, const int_t<> &, TCallback, TIndexCallback, TResultCollection, TErrorHandler, const int_t<> &)
	requires(!std::is_same_v<std::decay_t<TResultCollection>, null_t>)
{
	throw runtime_error(
		"task_start(): tasks runtime module is not enabled in this build",
		"tasks_module_disabled",
		"scpp::tasks",
		"task_start"
	);
}

template <typename TItem, typename TKey, typename TCallback>
[[nodiscard]] shared_p<batch> start(const hash_t<TItem, TKey> &, const int_t<> &, TCallback)
{
	throw runtime_error(
		"task_start(): tasks runtime module is not enabled in this build",
		"tasks_module_disabled",
		"scpp::tasks",
		"task_start"
	);
}

template <typename TItem, typename TKey, typename TCallback, typename TErrorHandler>
[[nodiscard]] shared_p<batch> start(const hash_t<TItem, TKey> &, const int_t<> &, TCallback, null_t, null_t, TErrorHandler)
{
	throw runtime_error(
		"task_start(): tasks runtime module is not enabled in this build",
		"tasks_module_disabled",
		"scpp::tasks",
		"task_start"
	);
}

template <typename TItem, typename TKey, typename TCallback, typename TErrorHandler>
[[nodiscard]] shared_p<batch> start(const hash_t<TItem, TKey> &, const int_t<> &, TCallback, null_t, null_t, TErrorHandler, const int_t<> &)
{
	throw runtime_error(
		"task_start(): tasks runtime module is not enabled in this build",
		"tasks_module_disabled",
		"scpp::tasks",
		"task_start"
	);
}

template <typename TItem, typename TKey, typename TCallback, typename TResultCollection, typename TErrorHandler>
[[nodiscard]] shared_p<batch> start(const hash_t<TItem, TKey> &, const int_t<> &, TCallback, null_t, TResultCollection, TErrorHandler)
	requires(!std::is_same_v<std::decay_t<TResultCollection>, null_t>)
{
	throw runtime_error(
		"task_start(): tasks runtime module is not enabled in this build",
		"tasks_module_disabled",
		"scpp::tasks",
		"task_start"
	);
}

template <typename TItem, typename TKey, typename TCallback, typename TResultCollection, typename TErrorHandler>
[[nodiscard]] shared_p<batch> start(const hash_t<TItem, TKey> &, const int_t<> &, TCallback, null_t, TResultCollection, TErrorHandler, const int_t<> &)
	requires(!std::is_same_v<std::decay_t<TResultCollection>, null_t>)
{
	throw runtime_error(
		"task_start(): tasks runtime module is not enabled in this build",
		"tasks_module_disabled",
		"scpp::tasks",
		"task_start"
	);
}

template <typename TItem, typename TKey, typename TCallback, typename TIndexCallback, typename TResultCollection, typename TErrorHandler>
[[nodiscard]] shared_p<batch> start(const hash_t<TItem, TKey> &, const int_t<> &, TCallback, TIndexCallback, TResultCollection, TErrorHandler)
	requires(!std::is_same_v<std::decay_t<TResultCollection>, null_t>)
{
	throw runtime_error(
		"task_start(): tasks runtime module is not enabled in this build",
		"tasks_module_disabled",
		"scpp::tasks",
		"task_start"
	);
}

template <typename TItem, typename TKey, typename TCallback, typename TIndexCallback, typename TResultCollection, typename TErrorHandler>
[[nodiscard]] shared_p<batch> start(const hash_t<TItem, TKey> &, const int_t<> &, TCallback, TIndexCallback, TResultCollection, TErrorHandler, const int_t<> &)
	requires(!std::is_same_v<std::decay_t<TResultCollection>, null_t>)
{
	throw runtime_error(
		"task_start(): tasks runtime module is not enabled in this build",
		"tasks_module_disabled",
		"scpp::tasks",
		"task_start"
	);
}

template <typename TItem, typename TCallback>
[[nodiscard]] auto run(const vector_t<TItem> &, const int_t<> &, TCallback)
	-> detail::result_vector_t<detail::callback_result_t<TCallback, TItem>>
{
	throw runtime_error(
		"task_run(): tasks runtime module is not enabled in this build",
		"tasks_module_disabled",
		"scpp::tasks",
		"task_run"
	);
}

template <typename TItem, typename TCallback, typename TErrorHandler>
[[nodiscard]] auto run(const vector_t<TItem> &, const int_t<> &, TCallback, null_t, null_t, TErrorHandler)
	-> detail::result_vector_t<detail::callback_result_t<TCallback, TItem>>
{
	throw runtime_error(
		"task_run(): tasks runtime module is not enabled in this build",
		"tasks_module_disabled",
		"scpp::tasks",
		"task_run"
	);
}

template <typename TItem, typename TCallback, typename TErrorHandler>
[[nodiscard]] auto run(const vector_t<TItem> &, const int_t<> &, TCallback, null_t, null_t, TErrorHandler, const int_t<> &)
	-> detail::result_vector_t<detail::callback_result_t<TCallback, TItem>>
{
	throw runtime_error(
		"task_run(): tasks runtime module is not enabled in this build",
		"tasks_module_disabled",
		"scpp::tasks",
		"task_run"
	);
}

template <typename TItem, typename TWorkCallback, typename TPublishCallback, typename TErrorHandler>
[[nodiscard]] int_t<> run_publish(const vector_t<TItem> &, const int_t<> &, TWorkCallback, TPublishCallback, TErrorHandler, const int_t<> &)
{
	throw runtime_error(
		"task_run_publish(): tasks runtime module is not enabled in this build",
		"tasks_module_disabled",
		"scpp::tasks",
		"task_run_publish"
	);
}

template <typename TItem, typename TWorkCallback, typename TPublishCallback>
[[nodiscard]] int_t<> run_publish(const vector_t<TItem> &, const int_t<> &, TWorkCallback, TPublishCallback)
{
	throw runtime_error(
		"task_run_publish(): tasks runtime module is not enabled in this build",
		"tasks_module_disabled",
		"scpp::tasks",
		"task_run_publish"
	);
}

template <typename TItem, typename TCallback, typename TIndexCallback>
[[nodiscard]] auto run(const vector_t<TItem> &, const int_t<> &, TCallback, TIndexCallback, null_t, null_t, const int_t<> &)
	-> hash_t<detail::task_value_t<detail::callback_result_t<TCallback, TItem>>, detail::task_value_t<std::invoke_result_t<TIndexCallback, TItem>>>
{
	throw runtime_error(
		"task_run(): tasks runtime module is not enabled in this build",
		"tasks_module_disabled",
		"scpp::tasks",
		"task_run"
	);
}

template <typename TItem, typename TCallback, typename TIndexCallback>
[[nodiscard]] auto run(const vector_t<TItem> &, const int_t<> &, TCallback, TIndexCallback, null_t, null_t)
	-> hash_t<detail::task_value_t<detail::callback_result_t<TCallback, TItem>>, detail::task_value_t<std::invoke_result_t<TIndexCallback, TItem>>>
{
	throw runtime_error(
		"task_run(): tasks runtime module is not enabled in this build",
		"tasks_module_disabled",
		"scpp::tasks",
		"task_run"
	);
}

template <typename TItem, typename TCallback, typename TResultCollection, typename TErrorHandler>
[[nodiscard]] TResultCollection run(const vector_t<TItem> &, const int_t<> &, TCallback, null_t, TResultCollection, TErrorHandler, const int_t<> &)
	requires(!std::is_same_v<std::decay_t<TResultCollection>, null_t>)
{
	throw runtime_error(
		"task_run(): tasks runtime module is not enabled in this build",
		"tasks_module_disabled",
		"scpp::tasks",
		"task_run"
	);
}

template <typename TItem, typename TCallback, typename TResultCollection, typename TErrorHandler>
[[nodiscard]] TResultCollection run(const vector_t<TItem> &, const int_t<> &, TCallback, null_t, TResultCollection, TErrorHandler)
	requires(!std::is_same_v<std::decay_t<TResultCollection>, null_t>)
{
	throw runtime_error(
		"task_run(): tasks runtime module is not enabled in this build",
		"tasks_module_disabled",
		"scpp::tasks",
		"task_run"
	);
}

template <typename TItem, typename TCallback, typename TIndexCallback, typename TResultCollection, typename TErrorHandler>
[[nodiscard]] TResultCollection run(const vector_t<TItem> &, const int_t<> &, TCallback, TIndexCallback, TResultCollection, TErrorHandler, const int_t<> &)
	requires(!std::is_same_v<std::decay_t<TResultCollection>, null_t>)
{
	throw runtime_error(
		"task_run(): tasks runtime module is not enabled in this build",
		"tasks_module_disabled",
		"scpp::tasks",
		"task_run"
	);
}

template <typename TItem, typename TCallback, typename TIndexCallback, typename TResultCollection, typename TErrorHandler>
[[nodiscard]] TResultCollection run(const vector_t<TItem> &, const int_t<> &, TCallback, TIndexCallback, TResultCollection, TErrorHandler)
	requires(!std::is_same_v<std::decay_t<TResultCollection>, null_t>)
{
	throw runtime_error(
		"task_run(): tasks runtime module is not enabled in this build",
		"tasks_module_disabled",
		"scpp::tasks",
		"task_run"
	);
}

template <typename TItem, typename TKey, typename TCallback>
[[nodiscard]] auto run(const hash_t<TItem, TKey> &, const int_t<> &, TCallback)
	-> detail::result_hash_t<detail::callback_result_t<TCallback, TItem>, TKey>
{
	throw runtime_error(
		"task_run(): tasks runtime module is not enabled in this build",
		"tasks_module_disabled",
		"scpp::tasks",
		"task_run"
	);
}

template <typename TItem, typename TKey, typename TCallback, typename TErrorHandler>
[[nodiscard]] auto run(const hash_t<TItem, TKey> &, const int_t<> &, TCallback, null_t, null_t, TErrorHandler)
	-> detail::result_hash_t<detail::callback_result_t<TCallback, TItem>, TKey>
{
	throw runtime_error(
		"task_run(): tasks runtime module is not enabled in this build",
		"tasks_module_disabled",
		"scpp::tasks",
		"task_run"
	);
}

template <typename TItem, typename TKey, typename TCallback, typename TErrorHandler>
[[nodiscard]] auto run(const hash_t<TItem, TKey> &, const int_t<> &, TCallback, null_t, null_t, TErrorHandler, const int_t<> &)
	-> detail::result_hash_t<detail::callback_result_t<TCallback, TItem>, TKey>
{
	throw runtime_error(
		"task_run(): tasks runtime module is not enabled in this build",
		"tasks_module_disabled",
		"scpp::tasks",
		"task_run"
	);
}

template <typename TItem, typename TKey, typename TCallback, typename TIndexCallback>
[[nodiscard]] auto run(const hash_t<TItem, TKey> &, const int_t<> &, TCallback, TIndexCallback, null_t, null_t)
	-> hash_t<detail::task_value_t<detail::callback_result_t<TCallback, TItem>>, detail::task_value_t<std::invoke_result_t<TIndexCallback, TItem>>>
{
	throw runtime_error(
		"task_run(): tasks runtime module is not enabled in this build",
		"tasks_module_disabled",
		"scpp::tasks",
		"task_run"
	);
}

template <typename TItem, typename TKey, typename TCallback, typename TIndexCallback>
[[nodiscard]] auto run(const hash_t<TItem, TKey> &, const int_t<> &, TCallback, TIndexCallback, null_t, null_t, const int_t<> &)
	-> hash_t<detail::task_value_t<detail::callback_result_t<TCallback, TItem>>, detail::task_value_t<std::invoke_result_t<TIndexCallback, TItem>>>
{
	throw runtime_error(
		"task_run(): tasks runtime module is not enabled in this build",
		"tasks_module_disabled",
		"scpp::tasks",
		"task_run"
	);
}

template <typename TItem, typename TKey, typename TCallback, typename TResultCollection, typename TErrorHandler>
[[nodiscard]] TResultCollection run(const hash_t<TItem, TKey> &, const int_t<> &, TCallback, null_t, TResultCollection, TErrorHandler, const int_t<> &)
	requires(!std::is_same_v<std::decay_t<TResultCollection>, null_t>)
{
	throw runtime_error(
		"task_run(): tasks runtime module is not enabled in this build",
		"tasks_module_disabled",
		"scpp::tasks",
		"task_run"
	);
}

template <typename TItem, typename TKey, typename TCallback, typename TResultCollection, typename TErrorHandler>
[[nodiscard]] TResultCollection run(const hash_t<TItem, TKey> &, const int_t<> &, TCallback, null_t, TResultCollection, TErrorHandler)
	requires(!std::is_same_v<std::decay_t<TResultCollection>, null_t>)
{
	throw runtime_error(
		"task_run(): tasks runtime module is not enabled in this build",
		"tasks_module_disabled",
		"scpp::tasks",
		"task_run"
	);
}

template <typename TItem, typename TKey, typename TCallback, typename TIndexCallback, typename TResultCollection, typename TErrorHandler>
[[nodiscard]] TResultCollection run(const hash_t<TItem, TKey> &, const int_t<> &, TCallback, TIndexCallback, TResultCollection, TErrorHandler, const int_t<> &)
	requires(!std::is_same_v<std::decay_t<TResultCollection>, null_t>)
{
	throw runtime_error(
		"task_run(): tasks runtime module is not enabled in this build",
		"tasks_module_disabled",
		"scpp::tasks",
		"task_run"
	);
}

template <typename TItem, typename TKey, typename TCallback, typename TIndexCallback, typename TResultCollection, typename TErrorHandler>
[[nodiscard]] TResultCollection run(const hash_t<TItem, TKey> &, const int_t<> &, TCallback, TIndexCallback, TResultCollection, TErrorHandler)
	requires(!std::is_same_v<std::decay_t<TResultCollection>, null_t>)
{
	throw runtime_error(
		"task_run(): tasks runtime module is not enabled in this build",
		"tasks_module_disabled",
		"scpp::tasks",
		"task_run"
	);
}

inline void configure_default_worker_pool(const int_t<> &)
{
	throw runtime_error(
		"task_set_worker_pool_size(): tasks runtime module is not enabled in this build",
		"tasks_module_disabled",
		"scpp::tasks",
		"task_set_worker_pool_size"
	);
}

inline void configure_publish_try_lock(const bool_t &)
{
	throw runtime_error(
		"task_set_publish_try_lock(): tasks runtime module is not enabled in this build",
		"tasks_module_disabled",
		"scpp::tasks",
		"task_set_publish_try_lock"
	);
}

[[nodiscard]] inline int_t<> publish_lock_wait_us()
{
	throw runtime_error(
		"task_publish_lock_wait_us(): tasks runtime module is not enabled in this build",
		"tasks_module_disabled",
		"scpp::tasks",
		"task_publish_lock_wait_us"
	);
}

[[nodiscard]] inline int_t<> publish_lock_hold_us()
{
	throw runtime_error(
		"task_publish_lock_hold_us(): tasks runtime module is not enabled in this build",
		"tasks_module_disabled",
		"scpp::tasks",
		"task_publish_lock_hold_us"
	);
}

[[nodiscard]] inline int_t<> publish_callback_us()
{
	throw runtime_error(
		"task_publish_callback_us(): tasks runtime module is not enabled in this build",
		"tasks_module_disabled",
		"scpp::tasks",
		"task_publish_callback_us"
	);
}

[[nodiscard]] inline int_t<> publish_batch_count()
{
	throw runtime_error(
		"task_publish_batch_count(): tasks runtime module is not enabled in this build",
		"tasks_module_disabled",
		"scpp::tasks",
		"task_publish_batch_count"
	);
}

[[nodiscard]] inline int_t<> publish_published_count()
{
	throw runtime_error(
		"task_publish_published_count(): tasks runtime module is not enabled in this build",
		"tasks_module_disabled",
		"scpp::tasks",
		"task_publish_published_count"
	);
}

[[nodiscard]] inline int_t<> publish_max_batch_size()
{
	throw runtime_error(
		"task_publish_max_batch_size(): tasks runtime module is not enabled in this build",
		"tasks_module_disabled",
		"scpp::tasks",
		"task_publish_max_batch_size"
	);
}

[[nodiscard]] inline int_t<> publish_failed_try_lock_count()
{
	throw runtime_error(
		"task_publish_failed_try_lock_count(): tasks runtime module is not enabled in this build",
		"tasks_module_disabled",
		"scpp::tasks",
		"task_publish_failed_try_lock_count"
	);
}

[[nodiscard]] inline int_t<> publish_deferred_flush_count()
{
	throw runtime_error(
		"task_publish_deferred_flush_count(): tasks runtime module is not enabled in this build",
		"tasks_module_disabled",
		"scpp::tasks",
		"task_publish_deferred_flush_count"
	);
}

#endif

} // namespace scpp::tasks

namespace scpp {

using task_batch = tasks::batch;
using task_context = tasks::context;
using task_progress_info = tasks::progress_info;
using task_error = tasks::error;

} // namespace scpp

#include "worker_pool.hpp"
#include "tasks.hpp"

#include <deque>
#include <memory>
#include <time.h>

#ifndef SCPP_TASKS_DEFAULT_WORKER_POOL_SIZE
#define SCPP_TASKS_DEFAULT_WORKER_POOL_SIZE 0
#endif

namespace scpp::tasks {

namespace {

thread_local bool default_pool_worker_active = false;
thread_local bool coordinator_callback_active = false;

class reusable_worker_pool final {
public:
	~reusable_worker_pool()
	{
		shutdown_and_join();
	}

	void configure(std::size_t workers)
	{
		std::lock_guard<std::mutex> lock(mutex_);
		desired_workers_ = workers;
		if (!stopping_) {
			ensure_workers_locked();
		}
		cv_.notify_all();
	}

	[[nodiscard]] std::size_t desired_workers() const
	{
		std::lock_guard<std::mutex> lock(mutex_);
		return desired_workers_;
	}

	[[nodiscard]] std::size_t live_workers() const
	{
		return live_workers_.load(std::memory_order_relaxed);
	}

	[[nodiscard]] std::size_t created_workers() const
	{
		return created_workers_.load(std::memory_order_relaxed);
	}

	[[nodiscard]] bool run_batch(std::size_t worker_count, const detail::worker_batch_body &body, detail::coordinator_dispatch &dispatch)
	{
		if (worker_count == 0) {
			return true;
		}

		struct wait_state final {
			std::mutex mutex;
			std::exception_ptr error = nullptr;
		};

		auto state = std::make_shared<wait_state>();
		auto shared_body = std::make_shared<detail::worker_batch_body>(body);

		{
			std::lock_guard<std::mutex> lock(mutex_);
			if (stopping_ || desired_workers_ == 0) {
				return false;
			}

			ensure_workers_locked();
			const auto queued_before = jobs_.size();
			try {
				for (std::size_t worker_index = 0; worker_index < worker_count; ++worker_index) {
					jobs_.push_back([state, shared_body, worker_index, &dispatch]() {
						try {
							(*shared_body)(worker_index);
						} catch (...) {
							std::lock_guard<std::mutex> lock(state->mutex);
							if (!state->error) {
								state->error = std::current_exception();
							}
						}

						dispatch.finish_workers();
					});
				}

			} catch (...) {
				while (jobs_.size() > queued_before) { jobs_.pop_back(); }
				throw;
			}
		}
		cv_.notify_all();

		dispatch.drain();
		std::unique_lock<std::mutex> lock(state->mutex);
		if (state->error) {
			std::rethrow_exception(state->error);
		}
		return true;
	}

	void shutdown_and_join()
	{
		{
			std::lock_guard<std::mutex> lock(mutex_);
			desired_workers_ = 0;
			stopping_ = true;
		}
		cv_.notify_all();

		for (auto &worker : workers_) {
			if (worker.joinable() && worker.get_id() != std::this_thread::get_id()) {
				worker.join();
			}
		}
	}

private:
	[[nodiscard]] bool should_retire_locked() const
	{
		return jobs_.empty()
			&& !stopping_
			&& live_workers_.load(std::memory_order_relaxed) > desired_workers_ + retiring_workers_;
	}

	void ensure_workers_locked()
	{
		while (!stopping_ && live_workers_.load(std::memory_order_relaxed) < desired_workers_) {
			live_workers_.fetch_add(1, std::memory_order_relaxed);
			created_workers_.fetch_add(1, std::memory_order_relaxed);
			try {
				workers_.emplace_back([this]() {
					worker_loop();
				});
			} catch (...) {
				live_workers_.fetch_sub(1, std::memory_order_relaxed);
				created_workers_.fetch_sub(1, std::memory_order_relaxed);
				throw;
			}
		}
	}

	void worker_loop() noexcept
	{
		default_pool_worker_active = true;
		bool retiring = false;
		try {
			while (true) {
				std::function<void()> job;
				{
					std::unique_lock<std::mutex> lock(mutex_);
					cv_.wait(lock, [this]() {
						return stopping_ || !jobs_.empty() || should_retire_locked();
					});

					if (!jobs_.empty()) {
						job = std::move(jobs_.front());
						jobs_.pop_front();
					} else if (stopping_ || should_retire_locked()) {
						if (!stopping_) {
							++retiring_workers_;
							retiring = true;
						}
						break;
					}
				}

				if (job) {
					job();
				}
			}
		} catch (...) {
		}

		{
			std::lock_guard<std::mutex> lock(mutex_);
			if (retiring && retiring_workers_ > 0) {
				--retiring_workers_;
			}
			live_workers_.fetch_sub(1, std::memory_order_relaxed);
		}
		default_pool_worker_active = false;
		cv_.notify_all();
	}

	mutable std::mutex mutex_;
	std::condition_variable cv_;
	std::deque<std::function<void()>> jobs_;
	std::vector<detail::worker_thread> workers_;
	std::atomic<std::size_t> live_workers_{0};
	std::atomic<std::size_t> created_workers_{0};
	std::size_t desired_workers_ = 0;
	std::size_t retiring_workers_ = 0;
	bool stopping_ = false;
};

std::mutex background_mutex;
std::vector<shared_p<batch_lifecycle>> background_batches;

void release_background(const shared_p<batch_lifecycle>& resource)
{
	std::lock_guard<std::mutex> guard(background_mutex);
	std::erase_if(background_batches, [&](const auto& saved) { return saved.get() == resource.get(); });
}

std::mutex default_pool_mutex;
std::shared_ptr<reusable_worker_pool> default_pool;
std::atomic<bool> publish_try_lock_mode{false};
std::atomic<std::int64_t> publish_metric_lock_wait_us{0};
std::atomic<std::int64_t> publish_metric_lock_hold_us{0};
std::atomic<std::int64_t> publish_metric_callback_us{0};
std::atomic<std::int64_t> publish_metric_batch_count{0};
std::atomic<std::int64_t> publish_metric_published_count{0};
std::atomic<std::int64_t> publish_metric_max_batch_size{0};
std::atomic<std::int64_t> publish_metric_failed_try_lock_count{0};
std::atomic<std::int64_t> publish_metric_deferred_flush_count{0};

void record_max(std::atomic<std::int64_t> &target, std::int64_t value)
{
	auto current = target.load(std::memory_order_relaxed);
	while (value > current && !target.compare_exchange_weak(current, value, std::memory_order_relaxed)) {
	}
}

#if SCPP_TASKS_DEFAULT_WORKER_POOL_SIZE > 0
struct configured_default_worker_pool final {
	configured_default_worker_pool()
	{
		configure_default_worker_pool(int_t<>(SCPP_TASKS_DEFAULT_WORKER_POOL_SIZE));
	}

	~configured_default_worker_pool()
	{
		shutdown_default_worker_pool();
	}
};

configured_default_worker_pool configured_default_worker_pool_instance;
#endif

[[nodiscard]] std::shared_ptr<reusable_worker_pool> current_default_pool()
{
	std::lock_guard<std::mutex> lock(default_pool_mutex);
	if (!default_pool || default_pool->desired_workers() == 0) {
		return nullptr;
	}
	return default_pool;
}

[[nodiscard]] shared_p<detail::batch_state> state_or_throw(const shared_p<batch_lifecycle> &resource, const char *name)
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

void reset_publish_metrics()
{
	publish_metric_lock_wait_us.store(0, std::memory_order_relaxed);
	publish_metric_lock_hold_us.store(0, std::memory_order_relaxed);
	publish_metric_callback_us.store(0, std::memory_order_relaxed);
	publish_metric_batch_count.store(0, std::memory_order_relaxed);
	publish_metric_published_count.store(0, std::memory_order_relaxed);
	publish_metric_max_batch_size.store(0, std::memory_order_relaxed);
	publish_metric_failed_try_lock_count.store(0, std::memory_order_relaxed);
	publish_metric_deferred_flush_count.store(0, std::memory_order_relaxed);
}

void record_publish_lock_wait(std::int64_t elapsed_us)
{
	publish_metric_lock_wait_us.fetch_add(elapsed_us, std::memory_order_relaxed);
}

void record_publish_lock_hold(std::int64_t elapsed_us)
{
	publish_metric_lock_hold_us.fetch_add(elapsed_us, std::memory_order_relaxed);
}

void record_publish_callback(std::int64_t elapsed_us, std::int64_t batch_size)
{
	publish_metric_callback_us.fetch_add(elapsed_us, std::memory_order_relaxed);
	publish_metric_batch_count.fetch_add(1, std::memory_order_relaxed);
	publish_metric_published_count.fetch_add(batch_size, std::memory_order_relaxed);
	record_max(publish_metric_max_batch_size, batch_size);
}

void record_publish_failed_try_lock()
{
	publish_metric_failed_try_lock_count.fetch_add(1, std::memory_order_relaxed);
}

void record_publish_deferred_flush()
{
	publish_metric_deferred_flush_count.fetch_add(1, std::memory_order_relaxed);
}

[[nodiscard]] publish_metrics_snapshot publish_metrics()
{
	publish_metrics_snapshot snapshot;
	snapshot.lock_wait_us = publish_metric_lock_wait_us.load(std::memory_order_relaxed);
	snapshot.lock_hold_us = publish_metric_lock_hold_us.load(std::memory_order_relaxed);
	snapshot.callback_us = publish_metric_callback_us.load(std::memory_order_relaxed);
	snapshot.batch_count = publish_metric_batch_count.load(std::memory_order_relaxed);
	snapshot.published_count = publish_metric_published_count.load(std::memory_order_relaxed);
	snapshot.max_batch_size = publish_metric_max_batch_size.load(std::memory_order_relaxed);
	snapshot.failed_try_lock_count = publish_metric_failed_try_lock_count.load(std::memory_order_relaxed);
	snapshot.deferred_flush_count = publish_metric_deferred_flush_count.load(std::memory_order_relaxed);
	return snapshot;
}

void configure_publish_try_lock(const bool_t &enabled)
{
	publish_try_lock_mode.store(enabled.native_value(), std::memory_order_relaxed);
}

[[nodiscard]] bool publish_try_lock_enabled()
{
	return publish_try_lock_mode.load(std::memory_order_relaxed);
}

[[nodiscard]] int_t<> publish_lock_wait_us()
{
	return int_t<>(publish_metrics().lock_wait_us);
}

[[nodiscard]] int_t<> publish_lock_hold_us()
{
	return int_t<>(publish_metrics().lock_hold_us);
}

[[nodiscard]] int_t<> publish_callback_us()
{
	return int_t<>(publish_metrics().callback_us);
}

[[nodiscard]] int_t<> publish_batch_count()
{
	return int_t<>(publish_metrics().batch_count);
}

[[nodiscard]] int_t<> publish_published_count()
{
	return int_t<>(publish_metrics().published_count);
}

[[nodiscard]] int_t<> publish_max_batch_size()
{
	return int_t<>(publish_metrics().max_batch_size);
}

[[nodiscard]] int_t<> publish_failed_try_lock_count()
{
	return int_t<>(publish_metrics().failed_try_lock_count);
}

[[nodiscard]] int_t<> publish_deferred_flush_count()
{
	return int_t<>(publish_metrics().deferred_flush_count);
}

void configure_default_worker_pool(const int_t<> &workers)
{
	const auto requested = workers.native_value();
	const std::size_t keepalive_workers = requested <= 0
		? std::size_t{0}
		: static_cast<std::size_t>(requested);

	std::shared_ptr<reusable_worker_pool> pool;
	{
		std::lock_guard<std::mutex> lock(default_pool_mutex);
		if (!default_pool) {
			default_pool = std::make_shared<reusable_worker_pool>();
		}
		pool = default_pool;
	}
	pool->configure(keepalive_workers);
}

void shutdown_default_worker_pool()
{
	std::shared_ptr<reusable_worker_pool> pool;
	{
		std::lock_guard<std::mutex> lock(default_pool_mutex);
		pool = default_pool;
		default_pool.reset();
	}
	if (pool) {
		pool->shutdown_and_join();
	}
}

[[nodiscard]] int_t<> default_worker_pool_size()
{
	std::lock_guard<std::mutex> lock(default_pool_mutex);
	if (!default_pool) {
		return int_t<>(0);
	}
	return int_t<>(static_cast<std::int64_t>(default_pool->desired_workers()));
}

[[nodiscard]] int_t<> default_worker_pool_live_workers()
{
	std::lock_guard<std::mutex> lock(default_pool_mutex);
	if (!default_pool) {
		return int_t<>(0);
	}
	return int_t<>(static_cast<std::int64_t>(default_pool->live_workers()));
}

[[nodiscard]] int_t<> default_worker_pool_created_workers()
{
	std::lock_guard<std::mutex> lock(default_pool_mutex);
	if (!default_pool) {
		return int_t<>(0);
	}
	return int_t<>(static_cast<std::int64_t>(default_pool->created_workers()));
}

namespace detail {

void coordinator_dispatch::invoke(std::function<void()> body)
{
	request entry{std::move(body), false, nullptr};
	std::unique_lock<std::mutex> lock(mutex_);
	requests_.push_back(&entry);
	changed_.notify_all();
	changed_.wait(lock, [&] { return entry.complete; });
	if (entry.failure) { std::rethrow_exception(entry.failure); }
}

void coordinator_dispatch::finish_workers(std::size_t count)
{
	const auto waiting = active_worker_profile ? profile_now_ns() : 0;
	std::unique_lock<std::mutex> lock(mutex_);
	const auto acquired = active_worker_profile ? profile_now_ns() : 0;
	remaining_ -= count;
	changed_.notify_all();
	lock.unlock();
	if (active_worker_profile) {
		active_worker_profile->lock_wait_ns += acquired - waiting;
		active_worker_profile->lock_hold_ns += profile_now_ns() - acquired;
	}
}

void coordinator_dispatch::drain()
{
	std::unique_lock<std::mutex> lock(mutex_);
	while (remaining_ != 0 || !requests_.empty()) {
		changed_.wait(lock, [&] { return remaining_ == 0 || !requests_.empty(); });
		if (requests_.empty()) { continue; }
		auto* entry = requests_.front();
		requests_.erase(requests_.begin());
		lock.unlock();
		const bool previous = coordinator_callback_active;
		coordinator_callback_active = true;
		try { entry->body(); }
		catch (...) { entry->failure = std::current_exception(); }
		coordinator_callback_active = previous;
		lock.lock();
		entry->complete = true;
		changed_.notify_all();
	}
}

// Opt-in gated launch keeps production scheduling unchanged when profiling is disabled.
std::uint64_t profile_thread_cpu_ns()
{
#if defined(CLOCK_THREAD_CPUTIME_ID)
	timespec value{};
	if (clock_gettime(CLOCK_THREAD_CPUTIME_ID, &value) != 0) {
		throw std::runtime_error("Thread CPU clock unavailable");
	}
	return std::uint64_t(value.tv_sec) * 1000000000ULL + value.tv_nsec;
#else
	throw std::runtime_error("Thread CPU profiling unsupported on this platform");
#endif
}

static void execute_profiled_worker_batch(std::size_t worker_count,
	const worker_batch_body &body, coordinator_dispatch &dispatch, batch_profile &profile)
{
	const auto start = profile_now_ns();
	std::vector<worker_thread> threads;
	threads.reserve(worker_count);
	std::vector<worker_profile> metrics(worker_count);
	std::vector<std::exception_ptr> errors(worker_count);
	std::mutex gate;
	std::condition_variable changed;
	std::size_t ready = 0;
	bool released = false;
	std::exception_ptr launch_error;
	try {
		for (std::size_t index = 0; index < worker_count; ++index) {
			threads.emplace_back([&, index] {
				{
					std::unique_lock lock(gate);
					++ready;
					changed.notify_all();
					changed.wait(lock, [&] { return released; });
				}
				auto &metric = metrics[index];
				active_worker_profile = &metric;
				try {
					const auto cpu = profile_thread_cpu_ns();
					try { body(index); }
					catch (...) { errors[index] = std::current_exception(); }
					metric.cpu_ns = profile_thread_cpu_ns() - cpu;
				} catch (...) { errors[index] = std::current_exception(); }
				metric.work_end = profile_now_ns();
				dispatch.finish_workers();
				metric.completion_end = profile_now_ns();
				active_worker_profile = nullptr;
			});
		}
	} catch (...) { launch_error = std::current_exception(); }
	std::uint64_t release;
	{
		std::unique_lock lock(gate);
		changed.wait(lock, [&] { return ready == threads.size(); });
		release = profile_now_ns();
		released = true;
	}
	changed.notify_all();
	if (threads.size() != worker_count) { dispatch.finish_workers(worker_count - threads.size()); }
	dispatch.drain();
	for (auto &thread : threads) { thread.join(); }
	const auto joined = profile_now_ns();
	auto work_end = release;
	auto completion_end = release;
	for (const auto &metric : metrics) {
		work_end = std::max(work_end, metric.work_end);
		completion_end = std::max(completion_end, metric.completion_end);
		profile.worker_cpu_ns += metric.cpu_ns;
		profile.queue_claim_ns += metric.queue_claim_ns;
		profile.completion_lock_wait_ns += metric.lock_wait_ns;
		profile.completion_lock_hold_ns += metric.lock_hold_ns;
	}
	profile.workers = threads.size();
	profile.startup_ns = release - start;
	profile.work_ns = work_end - release;
	profile.completion_tail_ns = completion_end - work_end;
	profile.join_tail_ns = joined - completion_end;
	if (launch_error) { std::rethrow_exception(launch_error); }
	for (const auto &error : errors) { if (error) { std::rethrow_exception(error); } }
}

void execute_worker_batch(std::size_t worker_count, const worker_batch_body &body, coordinator_dispatch &dispatch)
{
	if (worker_count == 0) { return; }
	if (active_batch_profile) {
		execute_profiled_worker_batch(worker_count, body, dispatch, *active_batch_profile);
		return;
	}
	if (!default_pool_worker_active && !coordinator_callback_active) {
		if (auto pool = current_default_pool(); pool && pool->run_batch(worker_count, body, dispatch)) { return; }
	}
	std::vector<worker_thread> threads;
	threads.reserve(worker_count);
	std::vector<std::exception_ptr> errors(worker_count);
	std::exception_ptr launch_error;
	try {
		for (std::size_t worker_index = 0; worker_index < worker_count; ++worker_index) {
			threads.emplace_back([&, worker_index] {
				try { body(worker_index); }
				catch (...) { errors.at(worker_index) = std::current_exception(); }
				dispatch.finish_workers();
			});
		}
	} catch (...) {
		launch_error = std::current_exception();
		dispatch.finish_workers(worker_count - threads.size());
	}
	dispatch.drain();
	for (auto &thread : threads) { if (thread.joinable()) { thread.join(); } }
	if (launch_error) { std::rethrow_exception(launch_error); }
	for (const auto& error : errors) { if (error) { std::rethrow_exception(error); } }
}

} // namespace detail

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

[[nodiscard]] bool_t done(const shared_p<batch_lifecycle> &resource)
{
	return bool_t(state_or_throw(resource, "task_done")->done.load());
}

void cancel(const shared_p<batch_lifecycle> &resource)
{
	const auto state = state_or_throw(resource, "task_cancel");
	if (!state->done.load()) {
		state->stop_requested.store(true);
	}
}

void detail::wait_batch(const shared_p<batch_lifecycle> &resource)
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

	release_background(resource);
	std::lock_guard<std::mutex> lock(resource->lifecycle_mutex);
	if (resource->error) {
		std::rethrow_exception(resource->error);
	}
}

[[nodiscard]] mixed_t join(const shared_p<batch> &resource)
{
	detail::wait_batch(resource);
	std::lock_guard<std::mutex> lock(resource->lifecycle_mutex);
	return resource->result;
}

[[nodiscard]] dynamic_t<> join(const shared_p<dynamic_batch> &resource)
{
	detail::wait_batch(resource);
	std::lock_guard<std::mutex> lock(resource->lifecycle_mutex);
	return resource->result;
}

namespace detail {
void retain_background(const shared_p<batch_lifecycle>& resource)
{
	try {
		std::lock_guard<std::mutex> guard(background_mutex);
		background_batches.push_back(resource);
	} catch (...) {
		// Never leave a running thread to destroy its own last handle on failure.
		try { detail::wait_batch(resource); } catch (...) {}
		throw;
	}
}
}

void shutdown()
{
	std::exception_ptr first_failure;
	for (;;) {
		std::vector<shared_p<batch_lifecycle>> pending;
		{
			std::lock_guard<std::mutex> guard(background_mutex);
			pending.swap(background_batches);
		}
		if (pending.empty()) { break; }
		for (const auto& resource : pending) {
			try { detail::wait_batch(resource); }
			catch (...) { if (!first_failure) { first_failure = std::current_exception(); } }
		}
	}
	shutdown_default_worker_pool();
	if (first_failure) { std::rethrow_exception(first_failure); }
}

[[nodiscard]] string_t status(const shared_p<batch_lifecycle> &resource)
{
	const auto state = state_or_throw(resource, "task_status");
	std::lock_guard<std::mutex> lock(state->status_mutex);
	return state->status;
}

[[nodiscard]] shared_p<progress_info> progress(const shared_p<batch_lifecycle> &resource)
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

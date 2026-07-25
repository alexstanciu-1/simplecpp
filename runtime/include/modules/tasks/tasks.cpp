#include "tasks.hpp"

#include <deque>
#include <memory>

namespace scpp::tasks {

namespace {

thread_local bool default_pool_worker_active = false;

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

	[[nodiscard]] bool run_batch(std::size_t worker_count, const detail::worker_batch_body &body)
	{
		if (worker_count == 0) {
			return true;
		}

		struct wait_state final {
			std::mutex mutex;
			std::condition_variable cv;
			std::size_t remaining = 0;
			std::exception_ptr error = nullptr;
		};

		auto state = std::make_shared<wait_state>();
		state->remaining = worker_count;

		{
			std::lock_guard<std::mutex> lock(mutex_);
			if (stopping_ || desired_workers_ == 0) {
				return false;
			}

			for (std::size_t worker_index = 0; worker_index < worker_count; ++worker_index) {
				jobs_.push_back([state, body, worker_index]() {
					try {
						body(worker_index);
					} catch (...) {
						std::lock_guard<std::mutex> lock(state->mutex);
						if (!state->error) {
							state->error = std::current_exception();
						}
					}

					{
						std::lock_guard<std::mutex> lock(state->mutex);
						if (state->remaining > 0) {
							--state->remaining;
						}
					}
					state->cv.notify_one();
				});
			}

			ensure_workers_locked();
		}
		cv_.notify_all();

		std::unique_lock<std::mutex> lock(state->mutex);
		state->cv.wait(lock, [&state]() {
			return state->remaining == 0;
		});
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

std::mutex default_pool_mutex;
std::shared_ptr<reusable_worker_pool> default_pool;

[[nodiscard]] std::shared_ptr<reusable_worker_pool> current_default_pool()
{
	std::lock_guard<std::mutex> lock(default_pool_mutex);
	if (!default_pool || default_pool->desired_workers() == 0) {
		return nullptr;
	}
	return default_pool;
}

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

void execute_worker_batch(std::size_t worker_count, const worker_batch_body &body)
{
	if (worker_count == 0) {
		return;
	}

	if (!default_pool_worker_active) {
		if (auto pool = current_default_pool(); pool && pool->run_batch(worker_count, body)) {
			return;
		}
	}

	std::vector<worker_thread> threads;
	threads.reserve(worker_count);
	for (std::size_t worker_index = 0; worker_index < worker_count; ++worker_index) {
		threads.emplace_back([&, worker_index]() {
			body(worker_index);
		});
	}

	for (auto &thread : threads) {
		if (thread.joinable()) {
			thread.join();
		}
	}
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

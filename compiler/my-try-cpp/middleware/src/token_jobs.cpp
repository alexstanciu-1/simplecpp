#include "scpp_compiler/token_jobs.hpp"
#include "scalar_access.hpp"
#include "string_state.hpp"
#include "modules/tasks/tasks.hpp"
#include "modules/datetime/datetime.hpp"
#include <atomic>

namespace scpp::compiler_support
{
	struct token_work
	{
		int64 id;
		string path;
		token_buffer source;
		int64 hint;
		uint64 revision;
		token_sequence output;
		std::uint64_t read_ns = 0;
		std::uint64_t read_cpu_ns = 0;
	};

	struct token_jobs::state {
		vector_t<shared_p<token_work>> work;
	};

	token_jobs::token_jobs() : state_(new state)
	{
	}

	token_jobs::~token_jobs() noexcept
	{
		delete this->state_;
	}

	void token_jobs::add(int64 id, const string &path, const token_buffer &source, int64 hint, uint64 revision)
	{
		this->state_->work.append(shared<token_work>(token_work{id, path, source, hint, revision, {}}));
	}

	/**
	 * Run lexical scans in private work records and join all workers before returning.
	 */
	token_measurements token_jobs::execute(uint32 jobs, token_scan_function scanner, boolean profile)
	{
		if (scanner == nullptr) {
			throw std::invalid_argument("Tokenization scanner must not be null");
		}
		if (jobs == 0) {
			throw std::invalid_argument("Tokenization jobs must be positive");
		}
		token_measurements result;
		tasks::detail::batch_profile batch_metrics;
		tasks::detail::batch_profile_scope scope(profile ? &batch_metrics : nullptr);
		const auto begin = dt::monotonic_micros().native_value();
		std::atomic<std::uint64_t> active{0};
		std::atomic<std::uint64_t> peak{0};
		if (!this->state_->work.empty().native_value())
		{
			(void)tasks::run(this->state_->work, int_t<>(scalar_access::get(jobs).native_value()),
				[&](const shared_p<token_work> &work)
				{
					const auto count = active.fetch_add(1) + 1;
					auto previous = peak.load();
					while (previous < count && !peak.compare_exchange_weak(previous, count)) {
					}
					try
					{
						const auto cpu = profile ? tasks::detail::profile_thread_cpu_ns() : 0;
						const auto start = profile ? tasks::detail::profile_now_ns() : 0;
						work->output = scanner(work->source, work->hint);
						if (profile) {
							work->read_ns = tasks::detail::profile_now_ns() - start;
							work->read_cpu_ns = tasks::detail::profile_thread_cpu_ns() - cpu;
						}
						active.fetch_sub(1);
						return bool_t(true);
					}
					catch (const std::exception &error) {
						active.fetch_sub(1);
						throw std::runtime_error(runtime_access::get(work->path).native_value() + ": " + error.what());
					}
					catch (...) {
						active.fetch_sub(1);
						throw;
					}
				});
		}
		result.files_tokenized = this->state_->work.size();
		for (const auto &work : this->state_->work.native_value()) {
			result.bytes_scanned += work->source.byte_size();
			result.token_count += uint64(work->output.size());
			result.capacity_bytes += work->output.capacity_bytes();
			result.scan_callback_ns += work->read_ns;
			result.scan_callback_cpu_ns += work->read_cpu_ns;
		}
		result.startup_ns = batch_metrics.startup_ns;
		result.work_ns = batch_metrics.work_ns;
		result.worker_cpu_ns = batch_metrics.worker_cpu_ns;
		result.completion_tail_ns = batch_metrics.completion_tail_ns;
		result.join_tail_ns = batch_metrics.join_tail_ns;
		result.queue_claim_ns = batch_metrics.queue_claim_ns;
		result.completion_lock_wait_ns = batch_metrics.completion_lock_wait_ns;
		result.completion_lock_hold_ns = batch_metrics.completion_lock_hold_ns;
		result.gather_ns = batch_metrics.gather_ns;
		result.workers = batch_metrics.workers;
		result.peak_workers = peak.load();
		result.elapsed_us = dt::monotonic_micros().native_value() - begin;
		return result;
	}

	int64 token_jobs::size() const
	{
		return static_cast<std::int64_t>(this->state_->work.size());
	}

	int64 token_jobs::record_id(int64 index) const
	{
		return this->state_->work.at(scalar_access::get(index))->id;
	}

	uint64 token_jobs::revision(int64 index) const
	{
		return this->state_->work.at(scalar_access::get(index))->revision;
	}

	token_sequence &token_jobs::tokens(int64 index)
	{
		return this->state_->work.at(scalar_access::get(index))->output;
	}
}

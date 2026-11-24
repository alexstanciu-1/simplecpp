#include "scpp/mt_segments.hpp"
#include <algorithm>
#include <atomic>
#include <cstdlib>
#include <exception>
#include <memory>
#include <mutex>
#include <string_view>
#include <stdexcept>
#include <thread>
#include <vector>

namespace scpp::mt {
void* segment::at(int64_t index) const {
    if (index < 0 || index >= length) throw std::out_of_range("Exclusive segment index");
    return data + static_cast<size_t>(offset + index) * stride;
}

namespace {
struct batch_slice {
    job* jobs;
    std::exception_ptr* errors;
    std::size_t begin, end;

    void run() const noexcept {
        for (auto i = begin; i < end; ++i) {
            auto& item = jobs[i];
            try { item.execute(); }
            catch (...) { errors[i] = std::current_exception(); }
        }
    }
};

bool active_wait() {
    const char* value = std::getenv("SCPP_MT_WAIT");
    if (!value || std::string_view(value) == "sleep") return false;
    if (std::string_view(value) == "active") return true;
    throw std::invalid_argument("SCPP_MT_WAIT must be sleep or active");
}

enum class command { idle, run, stop };

struct worker {
    const bool active;
    bool leased = false; // Protected by the pool mutex.
    batch_slice slice{};
    std::atomic<command> state{command::idle};
    std::thread thread;

    explicit worker(bool active) : active(active), thread([this] { loop(); }) {}
    ~worker() {
        publish(command::stop);
        thread.join();
    }

    void publish(command next) noexcept {
        state.store(next, std::memory_order_release);
        if (!active) state.notify_all();
    }

    void wait_while(command previous) noexcept {
        while (state.load(std::memory_order_acquire) == previous) {
            if (!active) state.wait(previous, std::memory_order_acquire);
#if defined(__i386__) || defined(__x86_64__)
            else __builtin_ia32_pause();
#else
            else std::this_thread::yield();
#endif
        }
    }

    void loop() noexcept {
        for (;;) {
            wait_while(command::idle);
            if (state.load(std::memory_order_acquire) == command::stop) return;
            slice.run();
            publish(command::idle);
        }
    }
};

class worker_pool {
    const bool active = active_wait();
    const std::size_t limit = std::max(2u, std::thread::hardware_concurrency()) - 1;
    std::mutex mutex;
    std::vector<std::unique_ptr<worker>> workers;
public:
    // Independent callers can share capacity. Each caller always makes progress.
    std::vector<worker*> acquire(std::size_t requested) {
        std::lock_guard lock(mutex);
        requested = std::min(requested, limit);
        std::vector<worker*> selected;
        selected.reserve(requested);
        for (auto& w : workers) {
            if (!w->leased) selected.push_back(w.get());
            if (selected.size() == requested) break;
        }
        while (selected.size() < requested && workers.size() < limit) {
            workers.push_back(std::make_unique<worker>(active));
            selected.push_back(workers.back().get());
        }
        // Publish leases only after every allocation and thread launch succeeded.
        for (auto* w : selected) w->leased = true;
        return selected;
    }

    void release(const std::vector<worker*>& selected) {
        std::lock_guard lock(mutex);
        for (auto* w : selected) w->leased = false;
    }
};
}

void job::admit(const job* jobs, std::size_t count) {
    struct interval { const void* backing; std::int64_t first, end; };
    std::vector<interval> admitted;
    for (std::size_t i = 0; i < count; ++i) {
        for (std::size_t slot = 0; slot < jobs[i].count; ++slot) {
            const auto& r = jobs[i].inputs[slot];
            const auto size = r.size;
            if (r.offset < 0 || r.offset > size || r.length < 0 || r.length > size - r.offset)
                throw std::out_of_range("Exclusive segment bounds");
            if (r.length == 0) continue;
            const interval next{r.data, r.offset, r.offset + r.length};
            for (const auto& previous : admitted)
                if (previous.backing == next.backing && previous.first < next.end && next.first < previous.end)
                    throw std::invalid_argument("Overlapping exclusive segments");
            admitted.push_back(next);
        }
    }
}

void join(job* jobs, std::size_t count) {
    job::admit(jobs, count);
    std::vector<std::exception_ptr> errors(count);
    if (count < 2) {
        batch_slice{jobs, errors.data(), 0, count}.run();
    } else {
        static worker_pool pool;
        auto selected = pool.acquire(count - 1);
        const auto participants = selected.size() + 1;
        const auto length = count / participants;
        const auto extra = count % participants;
        auto slice = [&](std::size_t id) {
            const auto begin = id * length + std::min(id, extra);
            return batch_slice{jobs, errors.data(), begin, begin + length + (id < extra)};
        };
        for (std::size_t i = 0; i < selected.size(); ++i) {
            selected[i]->slice = slice(i + 1);
            selected[i]->publish(command::run);
        }
        slice(0).run();
        for (auto* w : selected) w->wait_while(command::run);
        pool.release(selected);
    }
    for (const auto& error : errors) if (error) std::rethrow_exception(error);
}
}

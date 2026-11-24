#include "scpp/mt_segments.hpp"
#include <algorithm>
#include <atomic>
#include <cstdlib>
#include <exception>
#include <functional>
#include <memory>
#include <mutex>
#include <string_view>
#include <stdexcept>
#include <thread>
#include <vector>

namespace scpp::mt {
segment_range balanced_range(int64_t length, int64_t parts, int64_t part) {
    if (length < 0 || parts <= 0 || part < 0 || part >= parts)
        throw std::invalid_argument("balanced_range requires length >= 0, parts > 0 and 0 <= part < parts");
    const auto width = length / parts;
    const auto extra = length % parts;
    return {part * width + std::min(part, extra), width + (part < extra)};
}

const void* segment::read_at(int64_t index) const {
    if (field || index < 0 || index >= length) throw std::out_of_range("Segment index");
    return data + static_cast<size_t>(offset + index) * stride;
}

void* segment::at(int64_t index) const {
    if (read_only) throw std::logic_error("Cannot write through a read-only segment");
    return const_cast<void*>(read_at(index));
}

// A cursor is a one-based physical position relative to the admitted start.
bool segment::next(int64_t& cursor) const noexcept {
    if (!field || cursor < 0) return false;
    while (cursor < physical_end - physical_begin) {
        if (data[physical_begin + cursor++]) return true;
    }
    return false;
}

const void* segment::selected(int64_t cursor, bool key) const {
    if (!field || cursor <= 0 || cursor > physical_end - physical_begin || !data[physical_begin + cursor - 1])
        throw std::out_of_range("Hash segment cursor");
    return field(hash_owner, physical_begin + cursor - 1, key);
}

void* segment::writable_value(int64_t cursor) const {
    if (read_only) throw std::logic_error("Cannot write through a read-only segment");
    return const_cast<void*>(selected(cursor, false));
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

// Grant descriptors may be shared by callers. Only batch-local copies receive physical bounds.
struct batch_admission {
    std::vector<job> resolved;
    std::vector<segment> inputs;

    static bool validate(const job* jobs, std::size_t count);
    void resolve(const job* jobs, std::size_t count);
};

bool batch_admission::validate(const job* jobs, std::size_t count) {
    bool hashes = false;
    struct interval { const void* backing; std::int64_t first, end; bool read_only; };
    std::vector<interval> admitted;
    for (std::size_t i = 0; i < count; ++i) {
        for (std::size_t slot = 0; slot < jobs[i].count; ++slot) {
            const auto& r = jobs[i].inputs[slot];
            hashes |= r.field != nullptr;
            const auto size = r.size;
            if (r.offset < 0 || r.offset > size || r.length < 0 || r.length > size - r.offset)
                throw std::out_of_range("Segment bounds");
            if (r.length == 0) continue;
            const interval next{r.data, r.offset, r.offset + r.length, r.read_only};
            for (const auto& previous : admitted)
                if ((!previous.read_only || !next.read_only) && previous.backing == next.backing && previous.first < next.end && next.first < previous.end)
                    throw std::invalid_argument(previous.read_only || next.read_only
                        ? "Overlapping read and write segments" : "Overlapping exclusive segments");
            admitted.push_back(next);
        }
    }
    return hashes;
}

void batch_admission::resolve(const job* jobs, std::size_t count) {
    if (!validate(jobs, count)) return;
    resolved.assign(jobs, jobs + count);
    struct boundary { const unsigned char* live; int64_t ordinal; int64_t* physical; };
    std::vector<boundary> boundaries;
    for (const auto& j : resolved) inputs.insert(inputs.end(), j.inputs, j.inputs + j.count);
    size_t slot = 0;
    for (auto& j : resolved) {
        j.inputs = inputs.data() + slot;
        slot += j.count;
    }
    for (auto& r : inputs) {
        if (!r.field || !r.length) continue;
        boundaries.push_back({r.data, r.offset, &r.physical_begin});
        boundaries.push_back({r.data, r.offset + r.length, &r.physical_end});
    }
    std::sort(boundaries.begin(), boundaries.end(), [](const boundary& a, const boundary& b) {
        return a.live == b.live ? a.ordinal < b.ordinal : std::less<>{}(a.live, b.live);
    });
    const unsigned char* backing = nullptr;
    int64_t ordinal = 0, physical = 0;
    for (const auto& b : boundaries) {
        if (backing != b.live) { backing = b.live; ordinal = physical = 0; }
        while (ordinal < b.ordinal) ordinal += backing[physical++] != 0;
        *b.physical = physical;
    }
}

void join(job* jobs, std::size_t count) {
    join(jobs, count, INT64_MAX);
}

void join(job* jobs, std::size_t count, int64_t workers) {
    join(jobs, count, workers, nullptr, 0);
}

void join(job* jobs, std::size_t count, int64_t workers, const job_entry* entries, size_t entry_count) {
    if (workers <= 0) throw std::invalid_argument("Group workers must be positive");
    batch_admission admission;
    admission.resolve(jobs, count);
    auto* work = admission.resolved.empty() ? jobs : admission.resolved.data();
    // Chunk boundaries map scheduler units back to contiguous original result and error slots.
    std::vector<std::size_t> boundaries;
    auto units = count;
    if (entries) {
        const auto limit = std::min<uint64_t>(workers, std::max(2u, std::thread::hardware_concurrency()));
        boundaries.push_back(0);
        for (std::size_t i = 0; i < entry_count; ++i) {
            const auto [length, cap] = entries[i];
            if (cap <= 0 || length > count - boundaries.back()) throw std::invalid_argument("Invalid job entry");
            if (!length) continue;
            const auto chunks = std::min<uint64_t>({length, static_cast<uint64_t>(cap), limit});
            const auto width = length / chunks, extra = length % chunks;
            for (uint64_t part = 0; part < chunks; ++part)
                boundaries.push_back(boundaries.back() + width + (part < extra));
        }
        if (boundaries.back() != count) throw std::invalid_argument("Job entries must cover the batch");
        units = boundaries.size() - 1;
    }
    std::vector<std::exception_ptr> errors(count);
    if (units < 2 || workers == 1) {
        batch_slice{work, errors.data(), 0, count}.run();
    } else {
        static worker_pool pool;
        auto selected = pool.acquire(static_cast<std::size_t>(std::min<uint64_t>(units - 1, workers - 1)));
        const auto participants = selected.size() + 1;
        const auto length = units / participants;
        const auto extra = units % participants;
        auto slice = [&](std::size_t id) {
            const auto begin = id * length + std::min(id, extra);
            const auto end = begin + length + (id < extra);
            return batch_slice{work, errors.data(), entries ? boundaries[begin] : begin, entries ? boundaries[end] : end};
        };
        for (std::size_t i = 0; i < selected.size(); ++i) {
            selected[i]->slice = slice(i + 1);
            selected[i]->publish(command::run);
        }
        slice(0).run();
        for (auto* w : selected) w->wait_while(command::run);
        pool.release(selected);
    }
    if (work != jobs) for (size_t i = 0; i < count; ++i) jobs[i].result = work[i].result;
    for (const auto& error : errors) if (error) std::rethrow_exception(error);
}
}

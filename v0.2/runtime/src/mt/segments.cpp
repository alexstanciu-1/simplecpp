#include "scpp/mt_segments.hpp"
#include <exception>
#include <stdexcept>
#include <thread>
#include <vector>

namespace scpp::mt {
void* segment::at(int64_t index) const {
    if (index < 0 || index >= length) throw std::out_of_range("Exclusive segment index");
    return data + static_cast<size_t>(offset + index) * stride;
}

void join(job* jobs, std::size_t count) {
    struct interval { const void* backing; std::int64_t first, end; };
    std::vector<interval> admitted;
    for (std::size_t i = 0; i < count; ++i) {
        for (const auto& r : jobs[i].inputs) {
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
    std::vector<std::exception_ptr> errors(count);
    {
        std::vector<std::thread> threads;
        struct join_on_exit {
            std::vector<std::thread>& workers;
            ~join_on_exit() { for (auto& worker : workers) worker.join(); }
        } guard{threads};
        threads.reserve(count);
        for (std::size_t i = 0; i < count; ++i) {
            threads.emplace_back([&, i] {
                try { jobs[i].result = jobs[i].run(jobs[i].inputs[0], jobs[i].inputs[1]); }
                catch (...) { errors[i] = std::current_exception(); }
            });
        }
    }
    for (const auto& error : errors) if (error) std::rethrow_exception(error);
}
}

#include "../../runtime/include/modules/tasks/publication_context.hpp"
#include <cassert>
#include <thread>
#include <vector>

// Isolated proof of the exact lock/context primitive used by unordered task workers.
int main() {
    using namespace scpp::tasks::detail;
    std::mutex publication;
    int total = 0;
    std::vector<std::thread> workers;
    for (int i = 0; i < 8; ++i) {
        workers.emplace_back([&] {
            publication_context context(&publication);
            for (int n = 0; n < 2000; ++n) {
                synchronized_publication([&] {
                    auto prior = total;
                    std::this_thread::yield();
                    total = prior + 1;
                });
            }
            try {
                synchronized_publication([] { throw std::runtime_error("callback"); });
                assert(false);
            } catch (const std::runtime_error &) {}
            synchronized_publication([&] {
                ++total;
                try {
                    synchronized_publication([] {});
                    assert(false);
                } catch (const std::logic_error &) {}
            });
            assert(active_publication == &publication);
        });
    }
    for (auto &worker : workers) { worker.join(); }
    assert(total == 16008);
    assert(active_publication == nullptr);
    try {
        synchronized_publication([] {});
        assert(false);
    } catch (const std::logic_error &) {}
    // Nested batch context restores the caller's batch without sharing its mutex.
    std::mutex other;
    {
        publication_context outer(&publication);
        { publication_context inner(&other); assert(active_publication == &other); }
        assert(active_publication == &publication);
    }
    assert(active_publication == nullptr);
}

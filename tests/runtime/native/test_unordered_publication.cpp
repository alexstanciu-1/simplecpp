#include "scpp/tasks.hpp"
#include <cassert>
#include <chrono>
#include <condition_variable>
#include <iostream>
#include <mutex>
#include <stdexcept>

int main() {
    using namespace scpp;
    using namespace std::chrono_literals;
    vector_t<int_t<>> pair;
    pair.push_back(int_t<>(0));
    pair.push_back(int_t<>(1));
    std::mutex gate;
    std::condition_variable ready;
    bool fast_published = false;
    std::vector<int> publication_order;
    auto count = tasks::run_publish_unordered(pair, int_t<>(2), [&](int_t<> item) {
        if (item.native_value() == 0) {
            std::unique_lock lock(gate);
            if (!ready.wait_for(lock, 5s, [&] { return fast_published; })) {
                throw std::runtime_error("Publication waited for input order");
            }
        }
        return item;
    }, [&](int_t<> item) {
        publication_order.push_back(static_cast<int>(item.native_value()));
        if (item.native_value() == 1) {
            std::lock_guard lock(gate);
            fast_published = true;
            ready.notify_all();
        }
    });
    assert(count.native_value() == 2);
    assert((publication_order == std::vector<int>{1, 0}));

    vector_t<int_t<>> many;
    for (int i = 0; i != 32; ++i) many.push_back(int_t<>(i));
    std::atomic<int> active{0}, peak{0}, publishing{0};
    auto work = [&](int_t<> item) {
        int current = ++active;
        int old = peak.load();
        while (current > old && !peak.compare_exchange_weak(old, current)) {}
        std::this_thread::sleep_for(1ms);
        --active;
        return item;
    };
    count = tasks::run_publish_unordered(many, int_t<>(3), work, [&](int_t<>) {
        assert(publishing.fetch_add(1) == 0);
        std::this_thread::sleep_for(1ms);
        assert(publishing.fetch_sub(1) == 1);
    });
    assert(count.native_value() == 32 && peak.load() <= 3 && active.load() == 0);
    for (bool fail_publish : {false, true}) {
        bool caught = false;
        try {
            (void) tasks::run_publish_unordered(many, int_t<>(3), [&](int_t<> item) {
                struct exit_guard { std::atomic<int>& active; ~exit_guard() { --active; } } guard{active};
                ++active;
                std::this_thread::sleep_for(1ms);
                if (!fail_publish && item.native_value() == 0) throw std::runtime_error("work failure");
                return item;
            }, [&](int_t<> item) {
                if (fail_publish && item.native_value() == 0) throw std::runtime_error("publish failure");
            });
        } catch (const std::exception&) { caught = true; }
        assert(caught && active.load() == 0);
    }
    vector_t<int_t<>> empty;
    assert(tasks::run_publish_unordered(empty, int_t<>(1), work, [](int_t<>) {}).native_value() == 0);
    bool rejected = false;
    try { (void) tasks::run_publish_unordered(empty, int_t<>(0), work, [](int_t<>) {}); }
    catch (const std::exception&) { rejected = true; }
    assert(rejected);
    // The pre-existing ordered operation retains its contract.
    publication_order.clear();
    (void) tasks::run_publish(pair, int_t<>(2), work, [&](vector_t<int_t<>> batch) {
        for (std::size_t i = 0; i < batch.size(); ++i) publication_order.push_back(static_cast<int>(batch.at(i).native_value()));
    });
    assert((publication_order == std::vector<int>{0, 1}));
    std::cout << "Unordered publication: completion order, exclusion, bounds, errors/join and ordered regression passed\n";
}

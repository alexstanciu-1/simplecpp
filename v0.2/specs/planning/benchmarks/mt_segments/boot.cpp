// Time from beginning creation to entry of the later worker; keep both live until release.
#include <algorithm>
#include <chrono>
#include <condition_variable>
#include <iostream>
#include <mutex>
#include <thread>

int main() {
    using clock = std::chrono::steady_clock;
    std::cout << "trial,both_entered_us,joined_us\n";
    for (int i = -12; i < 240; ++i) {
        std::mutex mutex;
        std::condition_variable changed;
        int ready = 0;
        bool release = false;
        clock::time_point entered[2];
        auto worker = [&](int id) {
            entered[id] = clock::now();
            std::unique_lock lock(mutex);
            ++ready;
            changed.notify_all();
            changed.wait(lock, [&] { return release; });
        };
        const auto start = clock::now();
        std::thread a(worker, 0);
        std::thread b(worker, 1);
        {
            std::unique_lock lock(mutex);
            changed.wait(lock, [&] { return ready == 2; });
            release = true;
        }
        changed.notify_all();
        a.join();
        b.join();
        const auto end = clock::now();
        if (i >= 0) {
            const auto us = [](auto d) { return std::chrono::duration<double, std::micro>(d).count(); };
            std::cout << i << ',' << us(std::max(entered[0], entered[1]) - start) << ',' << us(end - start) << '\n';
        }
    }
}

#include <chrono>
#include <iostream>
#include <string>
#include <vector>

static unsigned long allocations, requested;
#ifdef COUNTERS
extern "C" void* __real__Znwm(std::size_t);
extern "C" void* __wrap__Znwm(std::size_t bytes) {
    ++allocations;
    requested += bytes;
    return __real__Znwm(bytes);
}
#endif

int main(int argc, char** argv) {
    for (std::string line; std::getline(std::cin, line);) {
        const scpp::string input(line.data(), static_cast<scpp::int64>(line.size()));
        if (argc == 1) {
            auto result = App::record_result_Packet::from_text(input);
            if (result.has_error()) {
                auto error = result.error().get_message();
                std::cout.write(error.data(), error.size());
            } else {
                const auto& rows = result.value().rows;
                std::cout << "ok";
                for (scpp::int64 i = 0; i < rows.size(); ++i) {
                    auto r = rows.at(i);
                    std::cout << ' ' << r.id << ',' << r.x << ',' << r.ok << ',' << r.tag;
                }
            }
            std::cout << '\n';
            continue;
        }
        const int loops = std::stoi(argv[1]);
        unsigned long checksum = 0;
        allocations = requested = 0;
        const auto start = std::chrono::steady_clock::now();
        for (int i = 0; i < loops; ++i) {
            auto result = App::record_result_Packet::from_text(input);
            const auto& rows = result.value().rows;
            checksum += rows.size();
            if (rows.size()) {
                const auto r = rows.at(rows.size() - 1);
                checksum += r.id + r.x + r.ok + r.tag;
            }
        }
        const auto elapsed = std::chrono::steady_clock::now() - start;
        std::cout << std::chrono::duration<double, std::nano>(elapsed).count() / loops << ' '
                  << allocations / loops << ' ' << requested / loops << ' ' << checksum << '\n';
    }
}

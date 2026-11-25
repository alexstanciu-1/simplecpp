// dev002 topology: CPU 19 shares core 3; spare pairs are (c,c+16), c=8..15.
#include <fstream>
#include <sstream>
struct PlacementDetector {
    struct Ticks { long long busy{}, total{}; };
    using Snapshot = std::array<Ticks, 32>;
    std::array<Snapshot, 2> history{};
    unsigned polls{};
    int moved_pass = -1, target = -1;
    double polling_us{}, observed_busy{};
    Clock::time_point decision{};
    void poll(unsigned pass) {
        auto begin = Clock::now();
        Snapshot now{};
        std::ifstream input("/proc/stat");
        std::string line;
        unsigned count = 0;
        while (std::getline(input, line)) {
            std::istringstream row(line);
            std::string name;
            row >> name;
            if (!name.starts_with("cpu")) break;
            if (name == "cpu") continue;
            unsigned id = std::stoul(name.substr(3));
            if (id >= now.size()) continue;
            for (int j = 0; j < 8; ++j) {
                long long v;
                if (!(row >> v)) std::abort();
                now[id].total += v;
                if (j != 3 && j != 4) now[id].busy += v;
            }
            ++count;
        }
        if (count != 32) std::abort();
        auto& old = history[polls % 2];
        auto busy = [&](int cpu) { return now[cpu].busy - old[cpu].busy; };
        auto total = [&](int cpu) { return now[cpu].total - old[cpu].total; };
        if (polls >= 2 && busy(19) >= 2 && busy(19) * 2 >= total(19)) {
            for (int c = 8; c < 16; ++c) {
                if (total(c)>0 && total(c+16)>0 && busy(c)==0 && busy(c+16)==0) {
                    target = c;
                    moved_pass = pass;
                    observed_busy = double(busy(19))/total(19);
                    decision = Clock::now();
                    break;
                }
            }
        }
        old = now;
        ++polls;
        polling_us += micros(Clock::now() - begin);
    }
};

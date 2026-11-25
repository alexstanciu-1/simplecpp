// Read-only Linux/CPPC counters, sampled outside the measured rotation loop.
#include <fstream>
#include <sstream>
#include <sys/resource.h>

struct TaskCounters {
    long long cpu_ns;
    long involuntary, voluntary;
};
TaskCounters task_counters() {
    rusage ru{};
    timespec t{};
    if (getrusage(RUSAGE_THREAD, &ru) || clock_gettime(CLOCK_THREAD_CPUTIME_ID, &t))
        std::abort();
    return {t.tv_sec * 1'000'000'000LL + t.tv_nsec, ru.ru_nivcsw, ru.ru_nvcsw};
}
struct alignas(64) TaskSpan {
    TaskCounters begin{}, end{};
    Clock::time_point start, finish;
};
struct HostCounters {
    struct Core {
        long long ref, delivered, sibling_busy, sibling_total;
    };
    std::array<Core, 8> cores{};
    HostCounters() {
        std::ifstream stat("/proc/stat");
        std::string line;
        int seen = 0;
        while (std::getline(stat, line)) {
            std::istringstream fields(line);
            std::string name;
            fields >> name;
            if (name.size() < 4 || name.substr(0,3) != "cpu") continue;
            int id = std::stoi(name.substr(3));
            if (id < 16 || id >= 24) continue;
            auto& c = cores[id-16];
            for (int j=0; j<8; ++j) {
                long long ticks;
                if (!(fields >> ticks)) std::abort();
                c.sibling_total += ticks;
                if (j != 3 && j != 4) c.sibling_busy += ticks;
            }
            ++seen;
        }
        if (seen != 8) std::abort();
        for (int id=0; id<8; ++id) {
            std::ifstream file("/sys/devices/system/cpu/cpu"+std::to_string(id)+"/acpi_cppc/feedback_ctrs");
            std::string ref, del;
            if (!(file >> ref >> del) || !ref.starts_with("ref:") || !del.starts_with("del:")) std::abort();
            cores[id].ref = std::stoll(ref.substr(4));
            cores[id].delivered = std::stoll(del.substr(4));
        }
    }
};

// Sample one pass in 32. Published pass_start is immutable until all workers finish.
using Clock = std::chrono::steady_clock;
#ifdef PROFILE_DISPATCH
constexpr bool profile_dispatch = true;
#else
constexpr bool profile_dispatch = false;
#endif

double micros(Clock::duration d) { return std::chrono::duration<double, std::micro>(d).count(); }
double thread_cpu_us() {
    timespec t{};
    if (clock_gettime(CLOCK_THREAD_CPUTIME_ID, &t)) std::abort();
    return t.tv_sec * 1e6 + t.tv_nsec * 0.001;
}

struct alignas(64) WorkerTiming {
    double start_delay{}, kernel_cpu{};
};

struct DispatchTiming {
    Clock::time_point pass_start, caller_start, wait_start;
    double wait_cpu_start{}, launch{}, caller{}, wait{}, wait_cpu{};
    std::array<WorkerTiming, 8> workers{};
    unsigned samples{};

    void begin() { pass_start = Clock::now(); ++samples; }
    void before_caller() {
        caller_start = Clock::now();
        launch += micros(caller_start - pass_start);
    }
    void before_wait() {
        wait_start = Clock::now();
        caller += micros(wait_start - caller_start);
        wait_cpu_start = thread_cpu_us();
    }
    void end() {
        wait += micros(Clock::now() - wait_start);
        wait_cpu += thread_cpu_us() - wait_cpu_start;
    }
    void print(unsigned count) const {
        double delay = 0, cpu = 0;
        for (unsigned w = 1; w < count; ++w) {
            delay += workers[w].start_delay;
            cpu += workers[w].kernel_cpu;
        }
        double divisor = samples * std::max(1u, count - 1);
        std::printf(",%.3f,%.3f,%.3f,%.3f,%.3f,%.3f", launch / samples, caller / samples,
                    wait / samples, wait_cpu / samples, delay / divisor, cpu / divisor);
    }
};

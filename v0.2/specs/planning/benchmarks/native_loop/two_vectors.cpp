// Standalone Linux/x86 benchmark. Compile as C++20 with -O3 -pthread.
#include <algorithm>
#include <array>
#include <atomic>
#include <bit>
#include <chrono>
#include <cstdio>
#include <cstdlib>
#include <cstdint>
#include <immintrin.h>
#include <latch>
#include <sched.h>
#include <string>
#include <thread>
#include <vector>

using Word = std::uint64_t;
using Clock = std::chrono::steady_clock;
constexpr unsigned records = 65536, passes = 8, stride = 32;
struct Record { Word value; std::array<Word, 15> padding; };
static_assert(sizeof(Record)==128);

Word transform(Word x, unsigned steps) {
    for (unsigned i=0; i<steps; ++i)
        x = std::rotl((x ^ (x >> 27)) * 0x2545f4914f6cdd1dULL, 17);
    return x;
}

[[gnu::noinline]] void process(const Record* input, Word* output, unsigned first,
                               unsigned end, unsigned pass, unsigned steps) {
    for (unsigned i=first; i<end; ++i)
        output[i] = transform(input[i].value + pass, steps);
}

void pin(unsigned cpu) {
    cpu_set_t mask;
    CPU_ZERO(&mask);
    CPU_SET(cpu,&mask);
    if (sched_setaffinity(0,sizeof(mask),&mask)) { std::perror("pin"); std::abort(); }
}

struct alignas(64) Worker {
    std::atomic<unsigned> finished{0};
    std::vector<Clock::time_point> times;
};

int main(int argc,char** argv) {
    if (argc!=4) return 1;
    const unsigned count=std::stoul(argv[1]), steps=std::stoul(argv[2]);
    const bool trace=std::stoi(argv[3])!=0;
    if (!count || count>8 || records%(count*stride) || !steps) return 1;
    const unsigned length=records/count, points=length/stride+1;
    std::vector<Record> input(records);
    std::vector<Word> output(records,0);
    for (unsigned i=0;i<records;++i) input[i].value=1'000'000+i;
    std::array<Worker,8> workers;
    if (trace) for (unsigned w=0;w<count;++w) workers[w].times.resize(points*passes);
    auto run=[&](unsigned w,unsigned pass) {
        const unsigned first=w*length, end=first+length;
        if (!trace) { process(input.data(),output.data(),first,end,pass,steps); return; }
        auto* t=workers[w].times.data()+pass*points;
        *t++=Clock::now();
        for (unsigned i=first;i<end;i+=stride) {
            process(input.data(),output.data(),i,i+stride,pass,steps);
            *t++=Clock::now();
        }
    };
    std::atomic<unsigned> generation{0};
    std::latch ready(count-1);
    std::vector<std::thread> team;
    for (unsigned w=1;w<count;++w) team.emplace_back([&,w] {
        pin(w);
        ready.count_down();
        for (unsigned p=0;p<passes;++p) {
            while (generation.load(std::memory_order_acquire)<=p) _mm_pause();
            run(w,p);
            workers[w].finished.store(p+1,std::memory_order_release);
        }
    });
    pin(0);
    ready.wait();
    const auto begin=Clock::now();
    for (unsigned p=0;p<passes;++p) {
        generation.store(p+1,std::memory_order_release);
        run(0,p);
        for (unsigned w=1;w<count;++w)
            while (workers[w].finished.load(std::memory_order_acquire)<=p) _mm_pause();
    }
    const double wall=std::chrono::duration<double>(Clock::now()-begin).count();
    for (auto& t:team) t.join();
    Word checksum=0;
    for (unsigned i=0;i<records;++i) {
        if (output[i]!=transform(1'000'000ULL+i+passes-1,steps)) return 2;
        checksum+=output[i];
    }
    Word low=0, high=0;
    double overlap=0, envelope=0;
    if (trace) for (unsigned p=0;p<passes;++p) {
        auto latest=Clock::time_point::min(), earliest=Clock::time_point::max();
        auto first=earliest, last=latest;
        for (unsigned w=0;w<count;++w) {
            const auto* t=workers[w].times.data()+p*points;
            latest=std::max(latest,t[0]); earliest=std::min(earliest,t[points-1]);
            first=std::min(first,t[0]); last=std::max(last,t[points-1]);
        }
        envelope+=std::chrono::duration<double>(last-first).count();
        if (latest>=earliest) continue;
        overlap+=std::chrono::duration<double>(earliest-latest).count();
        for (unsigned w=0;w<count;++w) {
            const auto* t=workers[w].times.data()+p*points;
            for (unsigned j=0;j+1<points;++j) {
                if (t[j]>=latest && t[j+1]<=earliest) low+=stride;
                if (t[j]<earliest && t[j+1]>latest) high+=stride;
            }
        }
    }
    std::printf("%.9f,%.9f,%.9f,%llu,%llu,%llu\n",wall,overlap,envelope,
        (unsigned long long)low,(unsigned long long)high,(unsigned long long)checksum);
}

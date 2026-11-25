#include <scpp/mt_segments.hpp>
#include <scpp/vectors.hpp>
#include "reuse.hpp"
#include <algorithm>
#include <array>
#include <chrono>
#include <cstdio>
#include <cstdlib>
#include <ctime>
#include <sched.h>

using Int = scpp::int64;
using Stock = App::scpp_records::Stock;
using Seg = scpp::mt::segment;
using Clock = std::chrono::steady_clock;
static_assert(sizeof(Stock)==128);
constexpr Int records = 65536;
struct alignas(64) Sample {
    Clock::time_point begin, end;
    double cpu;
    int first_cpu, last_cpu;
};
Sample samples[8];
bool profile;
Int chunk;
double thread_us() {
    timespec t{};
    clock_gettime(CLOCK_THREAD_CPUTIME_ID,&t);
    return t.tv_sec*1e6 + t.tv_nsec*1e-3;
}
double us(Clock::duration d) { return std::chrono::duration<double,std::micro>(d).count(); }
namespace App {
Int measured_kernel(Seg,Seg);
Int reconcile_(Seg rows, Seg adj) {
    if (!profile) return measured_kernel(rows,adj);
    auto& s=samples[(static_cast<Stock*>(rows.at(0))->sku - 1000000)/chunk];
    s.first_cpu=sched_getcpu();
    const auto cpu=thread_us();
    s.begin=Clock::now();
    auto result=measured_kernel(rows,adj);
    s.end=Clock::now();
    s.cpu=thread_us()-cpu;
    s.last_cpu=sched_getcpu();
    return result;
}
scpp::vector_int64 batch1_(Rows&,scpp::vector_int64&);
scpp::vector_int64 batch2_(Rows&,scpp::vector_int64&);
scpp::vector_int64 batch4_(Rows&,scpp::vector_int64&);
scpp::vector_int64 batch8_(Rows&,scpp::vector_int64&);
}

int main(int argc, char** argv) {
    if(argc!=2) return 1;
    const int workers=std::atoi(argv[1]);
    if(workers!=1 && workers!=2 && workers!=4 && workers!=8) return 1;
    chunk=records/workers;
    auto launch=workers==1 ? App::batch1_ : workers==2 ? App::batch2_ : workers==4 ? App::batch4_ : App::batch8_;
    Rows rows;
    scpp::vector_int64 changes;
    Int expected[8]{};
    for(Int i=0;i<records;++i) {
        Stock row{};
        row.sku=1000000+i; row.warehouse=i%8; row.reserved=i%21; row.unit_cost_cents=50+i%10000;
        rows.append(row); changes.append(0);
        expected[i/chunk]+=(500+i%300+i%11-5-row.reserved)*row.unit_cost_cents;
    }
    auto stock=[&](Int i)->Stock& {return *static_cast<Stock*>(scpp::mt::job::borrow(rows,i,1).at(0));};
    bench::pair_executor pool(workers);
    int order[]{0,1,2};
    for(int round=-6;round<36;++round) {
        profile=round>=24;
        for(int mode:order) {
            bench::selected=mode==2 ? &pool : nullptr;
            for(Int i=0;i<records;++i) {
                auto& s=stock(i);
                s.on_hand=500+i%300; s.available=s.value_cents=s.revision=0;
                changes.set(i,i%11-5);
            }
            Int result[8]{};
            auto start=Clock::now();
            if(mode==0) {
                for(int k=0;k<workers;++k)
                    result[k]=App::reconcile_(scpp::mt::job::borrow(rows,k*chunk,chunk),
                                              scpp::mt::job::borrow(changes,k*chunk,chunk));
            } else {
                auto v=launch(rows,changes);
                for(int k=0;k<workers;++k) result[k]=v.at(k);
            }
            auto end=Clock::now();
            for(int k=0;k<workers;++k) if(result[k]!=expected[k]) return 2;
            for(Int i=0;i<records;++i) {
                auto& s=stock(i); Int qty=500+i%300+i%11-5;
                if(s.on_hand!=qty || s.available!=qty-i%21 || s.revision!=1 ||
                   s.value_cents!=s.available*s.unit_cost_cents || changes.at(i)!=0) return 3;
            }
            if(round<0) continue;
            double pre=0,span=0,tail=0,cpu=0,spread=0;
            if(profile) {
                auto first=samples[0].begin,last_start=first,last=samples[0].end;
                for(int k=0;k<workers;++k) {
                    first=std::min(first,samples[k].begin); last=std::max(last,samples[k].end);
                    last_start=std::max(last_start,samples[k].begin); cpu+=samples[k].cpu;
                }
                pre=us(first-start); span=us(last-first); tail=us(end-last); spread=us(last_start-first);
            }
            std::printf("%d,%d,%s,%d,%.3f,%.3f,%.3f,%.3f,%.3f,%.3f\n",workers,round,
                mode==0?"seq":mode==1?"fresh":"reuse",profile,us(end-start),pre,span,tail,cpu,spread);
        }
        std::next_permutation(order,order+3);
    }
}

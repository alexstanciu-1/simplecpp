#include "model.hpp"
#include <new>
#include <cstdlib>
#include <utility>
#include <stdexcept>
#include <limits>
#include <cstddef>
#ifdef BENCH_INSTRUMENT
#define BENCH_COUNT(x) do { if(counting) { x; } } while(false)
namespace bench { long allocation_budget=-1; Counts counts; bool counting=false; void reset_counts(){counts={};} }
// Header-based allocation accounting is enabled only for the untimed proof.
struct alignas(std::max_align_t) Allocation { std::size_t size; bool tracked; };
void* operator new(std::size_t n) {
    if(bench::counting && bench::allocation_budget>=0 && bench::allocation_budget--==0)throw std::bad_alloc();
    auto* a=static_cast<Allocation*>(std::malloc(sizeof(Allocation)+(n?n:1)));
    if(!a)throw std::bad_alloc();
    a->size=n;a->tracked=bench::counting;
    if(a->tracked){auto& c=bench::counts;++c.allocations;c.bytes+=n;c.live+=n;if(c.live>c.peak)c.peak=c.live;}
    return a+1;
}
void operator delete(void* p) noexcept {if(!p)return;auto* a=static_cast<Allocation*>(p)-1;if(a->tracked)bench::counts.live-=a->size;std::free(a);}
void* operator new[](std::size_t n){return ::operator new(n);}
void operator delete[](void* p) noexcept{::operator delete(p);}
void operator delete(void* p,std::size_t) noexcept{::operator delete(p);}
void operator delete[](void* p,std::size_t) noexcept{::operator delete(p);}
#else
#define BENCH_COUNT(x) do {} while(false)
#endif
namespace bench {
// Template implementation is private to this one runtime TU; emitted consumers see concrete types.
template<class T> struct Buffer {
    T* data=nullptr;std::size_t size=0,capacity=0;
    ~Buffer(){while(size)data[--size].~T();::operator delete(data);}
    void reserve(std::size_t n){
        if(n<=capacity)return;
        if(n>std::numeric_limits<std::size_t>::max()/sizeof(T))throw std::bad_array_new_length();
        auto* next=static_cast<T*>(::operator new(n*sizeof(T)));
        BENCH_COUNT(++counts.buffers;if(capacity)++counts.growth);
        for(std::size_t i=0;i<size;++i){new(next+i)T(std::move(data[i]));BENCH_COUNT(++counts.moves);data[i].~T();}
        ::operator delete(data);data=next;capacity=n;
    }
    void append(T&& x){if(size==capacity)reserve(capacity?capacity*2:4);new(data+size)T(std::move(x));++size;BENCH_COUNT(++counts.moves);}
};
template<class T> Buffer<T>* copy_input(const T* p,std::size_t n){
    if(!n)return nullptr;
    auto* b=new Buffer<T>;
    try{b->reserve(n);while(b->size<n){new(b->data+b->size)T(p[b->size]);++b->size;BENCH_COUNT(++counts.copies);}}
    catch(...){delete b;throw;}return b;
}
template<class T> Buffer<T>* move_input(T* p,std::size_t n){
    if(!n)return nullptr;
    auto* b=new Buffer<T>;
    try{b->reserve(n);while(b->size<n){new(b->data+b->size)T(std::move(p[b->size]));++b->size;BENCH_COUNT(++counts.moves);}}
    catch(...){delete b;throw;}return b;
}
#define IMPL(V,T) \
V::V() noexcept=default; V::~V(){delete static_cast<Buffer<T>*>(p);} \
V::V(const V& x):p(x.p?copy_input(static_cast<Buffer<T>*>(x.p)->data,x.size()):nullptr){} \
V::V(V&& x) noexcept:p(std::exchange(x.p,nullptr)){} \
V& V::operator=(const V& x){if(this!=&x){V tmp(x);std::swap(p,tmp.p);}return *this;} \
V& V::operator=(V&& x) noexcept{if(this!=&x){delete static_cast<Buffer<T>*>(p);p=std::exchange(x.p,nullptr);}return *this;} \
V::V(std::initializer_list<T> x):V(x.begin(),x.size()){} \
V::V(const T* x,std::size_t n):p(copy_input(x,n)){} \
V::V(owned_input,T* x,std::size_t n):p(move_input(x,n)){} \
V V::from(std::initializer_list<T> x){return V(x.begin(),x.size());} \
V V::from(const T* x,std::size_t n){return V(x,n);} \
V V::from_owned(T* x,std::size_t n){return V(owned_input{},x,n);} \
void V::reserve(std::size_t n){if(!n)return;if(!p)p=new Buffer<T>;static_cast<Buffer<T>*>(p)->reserve(n);} \
void V::append(T x){if(!p)p=new Buffer<T>;static_cast<Buffer<T>*>(p)->append(std::move(x));} \
std::size_t V::capacity()const{return p?static_cast<Buffer<T>*>(p)->capacity:0;} \
T& V::at(std::size_t i){return const_cast<T&>(static_cast<const V&>(*this).at(i));} \
std::size_t V::size()const{return p?static_cast<Buffer<T>*>(p)->size:0;} \
const T& V::at(std::size_t i)const{if(i>=size())throw std::out_of_range("index");return static_cast<Buffer<T>*>(p)->data[i];}
IMPL(IVec,I)
IMPL(SVec,scpp::string)
IMPL(Matrix,IVec)
#undef IMPL
bool presized(const IVec& v){return v.size()==v.capacity();}
bool presized(const SVec& v){return v.size()==v.capacity();}
bool presized(const Matrix& v){if(v.size()!=v.capacity())return false;for(std::size_t i=0;i<v.size();++i)if(!presized(v.at(i)))return false;return true;}
static void mix(std::uint64_t& h,std::uint64_t x){h=(h^x)*1099511628211ULL;}
std::uint64_t digest(const IVec& v){std::uint64_t h=1469598103934665603ULL;mix(h,v.size());for(std::size_t i=0;i<v.size();++i)mix(h,v.at(i));return h;}
std::uint64_t digest(const SVec& v){std::uint64_t h=1469598103934665603ULL;mix(h,v.size());for(std::size_t i=0;i<v.size();++i){const auto& s=v.at(i);mix(h,s.size());for(I j=0;j<s.size();++j)mix(h,static_cast<unsigned char>(s.data()[j]));}return h;}
std::uint64_t digest(const Matrix& v){std::uint64_t h=1469598103934665603ULL;mix(h,v.size());for(std::size_t i=0;i<v.size();++i)mix(h,digest(v.at(i)));return h;}
std::uint64_t observe(const IVec& v){return v.size()+static_cast<std::uint64_t>(v.at(0))+static_cast<std::uint64_t>(v.at(v.size()-1));}
std::uint64_t observe(const SVec& v){return v.size()+v.at(0).size()+v.at(v.size()-1).size();}
std::uint64_t observe(const Matrix& v){return v.size()+observe(v.at(0))+observe(v.at(v.size()-1));}
}

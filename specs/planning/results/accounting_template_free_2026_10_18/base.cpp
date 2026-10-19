#include "base.hpp"
#include <stdlib.h>
#include <string.h>
#include <stdio.h>
#include <inttypes.h>
namespace scpp {
[[noreturn]] void fail(const char *message) { throw runtime_failure{message}; }
void *allocate_bytes(size_t bytes) { auto *p=malloc(bytes); if(!p) fail("allocation_failure"); return p; }
string_t::string_t(const char *v) : string_t(v,strlen(v)) {}
string_t::string_t(const char *v,size_t n) : data_(static_cast<char*>(allocate_bytes(n+1))),length_(n) { memcpy(data_,v,n);data_[n]=0; }
string_t::string_t(const string_t& x) : string_t(x.data(),x.size()) {}
string_t::string_t(string_t&& x) noexcept : data_(x.data_),length_(x.length_) { x.data_=nullptr;x.length_=0; }
string_t& string_t::operator=(const string_t& x) { if(this!=&x) { string_t next(x); *this=static_cast<string_t&&>(next); } return *this; }
string_t& string_t::operator=(string_t&& x) noexcept { if(this!=&x) { free(data_);data_=x.data_;length_=x.length_;x.data_=nullptr;x.length_=0; } return *this; }
string_t::~string_t() { free(data_); }
string_t operator+(const string_t& a,const string_t& b) {
    if(a.size()>SIZE_MAX-b.size()-1) fail("string_size_overflow");
    auto *buffer=static_cast<char*>(allocate_bytes(a.size()+b.size()+1));
    memcpy(buffer,a.data(),a.size());memcpy(buffer+a.size(),b.data(),b.size());
    try { string_t result(buffer,a.size()+b.size());free(buffer);return result; } catch(...) {free(buffer);throw;}
}
bool operator==(const string_t& a,const string_t& b) { return a.size()==b.size() && memcmp(a.data(),b.data(),a.size())==0; }
uint64_t key_hash(const string_t& key) { uint64_t h=14695981039346656037ull;for(size_t i=0;i<key.size();++i) { h^=static_cast<unsigned char>(key.data()[i]);h*=1099511628211ull; }return h; }
string_t convert_string_t(I v) { char buffer[32];int n=snprintf(buffer,sizeof(buffer),"%" PRId64,v.value);return string_t(buffer,static_cast<size_t>(n)); }
namespace str {
I length(const string_t& text) {
    size_t count=0;
    for(size_t i=0;i<text.size();) {
        unsigned char lead=static_cast<unsigned char>(text.data()[i]);
        size_t width=0; uint32_t cp=0;
        if(lead<0x80) {width=1;cp=lead;}
        else if(lead>=0xc2 && lead<=0xdf) {width=2;cp=lead&0x1f;}
        else if(lead>=0xe0 && lead<=0xef) {width=3;cp=lead&0x0f;}
        else if(lead>=0xf0 && lead<=0xf4) {width=4;cp=lead&7;}
        else return I(static_cast<int64_t>(text.size()));
        if(width>text.size()-i) return I(static_cast<int64_t>(text.size()));
        for(size_t j=1;j<width;++j) {
            unsigned char part=static_cast<unsigned char>(text.data()[i+j]);
            if((part&0xc0)!=0x80) return I(static_cast<int64_t>(text.size()));
            cp=(cp<<6)|(part&0x3f);
        }
        if((width==3 && cp<0x800) || (width==4 && cp<0x10000) ||
           (cp>=0xd800 && cp<=0xdfff) || cp>0x10ffff) return I(static_cast<int64_t>(text.size()));
        i+=width;++count;
    }
    return I(static_cast<int64_t>(count));
}
}
#ifdef SCPP_OWNER_AUDIT
static size_t owner_count=0;
size_t outstanding_owners() {return owner_count;}
#endif
struct owner_state { size_t refs;void *value;void (*drop)(void*); };
owner_state *make_owner(void *value,void (*drop)(void*)) {
    auto *p=static_cast<owner_state*>(malloc(sizeof(owner_state)));
    if(!p) {drop(value);fail("allocation_failure");}
    p->refs=1;p->value=value;p->drop=drop;
#ifdef SCPP_OWNER_AUDIT
    ++owner_count;
#endif
    return p;
}
void retain_owner(owner_state *p) noexcept {if(p) __atomic_fetch_add(&p->refs,1,__ATOMIC_RELAXED);}
void release_owner(owner_state *p) noexcept {if(p && __atomic_fetch_sub(&p->refs,1,__ATOMIC_ACQ_REL)==1) {p->drop(p->value);free(p);
#ifdef SCPP_OWNER_AUDIT
--owner_count;
#endif
}}
void print_runtime_exception(const runtime_failure& e) {fprintf(stderr,"%s\n",e.message);}
namespace php {
static cli_arguments args{0,nullptr};
void echo_one(const string_t& s) {fwrite(s.data(),1,s.size(),stdout);}
void echo_one(I v) {printf("%" PRId64,v.value);}
void set_cli_args(int count,char **values) {args={count,values};}
I cli_argc() {return I(args.count);}
cli_arguments cli_argv() {return args;}
}
}

#pragma once
#include <stdint.h>
#include <stddef.h>
namespace scpp {
struct runtime_failure { const char *message; };
[[noreturn]] void fail(const char *);
void *allocate_bytes(size_t);
struct bool_t {
    bool value = false;
    bool_t() = default;
    explicit bool_t(bool v) : value(v) {}
    explicit operator bool() const { return value; }
};
inline bool_t operator!(bool_t a) { return bool_t(!a.value); }
inline bool_t operator&&(bool_t a,bool_t b) { return bool_t(a.value && b.value); }
inline bool_t operator||(bool_t a,bool_t b) { return bool_t(a.value || b.value); }
inline bool operator==(bool_t a,bool_t b) { return a.value==b.value; }
struct I {
    int64_t value = 0;
    I() = default;
    explicit I(int64_t v) : value(v) {}
};
inline I operator+(I a,I b) { return I(a.value+b.value); }
inline I operator-(I a,I b) { return I(a.value-b.value); }
inline I operator*(I a,I b) { return I(a.value*b.value); }
inline I operator/(I a,I b) { if(!b.value) fail("division_by_zero"); return I(a.value/b.value); }
inline I operator%(I a,I b) { if(!b.value) fail("modulo_by_zero"); return I(a.value%b.value); }
inline I operator-(I a) { return I(-a.value); }
inline I& operator+=(I& a,I b) { a=a+b; return a; }
inline I& operator-=(I& a,I b) { a=a-b; return a; }
inline I& operator*=(I& a,I b) { a=a*b; return a; }
inline I operator++(I& a,int) { I old=a; a+=I(1); return old; }
inline I operator--(I& a,int) { I old=a; a-=I(1); return old; }
inline bool_t operator==(I a,I b) { return bool_t(a.value==b.value); }
inline bool_t operator!=(I a,I b) { return bool_t(a.value!=b.value); }
inline bool_t operator<(I a,I b) { return bool_t(a.value<b.value); }
inline bool_t operator>(I a,I b) { return bool_t(a.value>b.value); }
inline bool_t operator<=(I a,I b) { return bool_t(a.value<=b.value); }
inline bool_t operator>=(I a,I b) { return bool_t(a.value>=b.value); }
class string_t {
    char *data_ = nullptr;
    size_t length_ = 0;
public:
    string_t() = default;
    explicit string_t(const char *);
    string_t(const char *, size_t);
    string_t(const string_t&);
    string_t(string_t&&) noexcept;
    string_t& operator=(const string_t&);
    string_t& operator=(string_t&&) noexcept;
    ~string_t();
    const char *data() const { return data_ ? data_ : ""; }
    size_t size() const { return length_; }
};
string_t operator+(const string_t&,const string_t&);
bool operator==(const string_t&,const string_t&);
uint64_t key_hash(const string_t&);
inline uint64_t key_hash(I key) { return static_cast<uint64_t>(key.value)*11400714819323198485ull; }
inline I convert_I(I value) { return value; }
inline bool_t convert_bool_t(bool_t value) { return value; }
inline string_t convert_string_t(const string_t& value) { return value; }
string_t convert_string_t(I);
namespace str { I length(const string_t&); }
struct shared_self { virtual ~shared_self() = default; };
struct owner_state;
owner_state *make_owner(void *, void (*)(void*));
void retain_owner(owner_state *) noexcept;
void release_owner(owner_state *) noexcept;
#ifdef SCPP_OWNER_AUDIT
size_t outstanding_owners();
#endif
struct owner_ref {
    owner_state *state=nullptr;
    owner_ref()=default;
    explicit owner_ref(owner_state *s) : state(s) {}
    owner_ref(const owner_ref& x) : state(x.state) { retain_owner(state); }
    owner_ref(owner_ref&& x) noexcept : state(x.state) { x.state=nullptr; }
    owner_ref& operator=(const owner_ref& x) { retain_owner(x.state); release_owner(state); state=x.state; return *this; }
    owner_ref& operator=(owner_ref&& x) noexcept { if(this!=&x) { release_owner(state); state=x.state; x.state=nullptr; } return *this; }
    ~owner_ref() { release_owner(state); }
};
struct cli_arguments { int count; char **values; };
void print_runtime_exception(const runtime_failure&);
namespace php {
inline bool_t condition_truthy(bool_t v) { return v; }
inline bool_t condition_truthy(I v) { return bool_t(v.value!=0); }
inline bool_t identical(I a,I b) { return a==b; }
inline bool_t not_identical(I a,I b) { return !(a==b); }
inline bool_t identical(bool_t a,bool_t b) { return bool_t(a==b); }
inline bool_t not_identical(bool_t a,bool_t b) { return bool_t(!(a==b)); }
inline bool_t identical(const string_t& a,const string_t& b) { return bool_t(a==b); }
inline bool_t not_identical(const string_t& a,const string_t& b) { return bool_t(!(a==b)); }
void echo_one(const string_t&);
void echo_one(I);
void set_cli_args(int,char**);
I cli_argc();
cli_arguments cli_argv();
}
}
#define SCPP_CALL_DEPTH_GUARD(a,b,c) ((void)0)

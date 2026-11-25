"""Experimental native fixtures. This is not the production compiler lowerer."""
from pathlib import Path

def write(path, text):
    if not path.exists() or path.read_text() != text:
        path.write_text(text)

def emit(root, variant, stage):
    root.mkdir(parents=True, exist_ok=True)
    names = ['Row', 'Other'] if stage == 'specialization' else ['Row']
    layout = stage in ('layout', 'specialization')
    for name in names:
        write(root/f'{name}.hpp', f'''#pragma once
#include <cstddef>
#include <cstdint>
struct {name}State;
class {name} {{
    {name}State *state_ = nullptr;
public:
    {name}() = default;
    explicit {name}(int64_t value);
    {name}(const {name}&);
    {name}({name}&&) noexcept;
    {name}& operator=(const {name}&);
    {name}& operator=({name}&&) noexcept;
    ~{name}();
    int64_t value() const;
    void value(int64_t);
    bool same(const {name}&) const;
    static int live();
    static int made();
    static int dropped();
}};
''')
        extra = 'int64_t extra = 73;' if layout and name == 'Row' else ''
        read = 'value_ + extra - 73' if extra else 'value_'
        write(root/f'{name}_payload.hpp', f'''#pragma once
#include <scpp/lang/php.hpp>
struct {name}Payload {{
    inline static int live = 0, made = 0, dropped = 0;
    int64_t value_;
    {extra}
    explicit {name}Payload(int64_t v) : value_(v) {{ ++live; ++made; }}
    ~{name}Payload() {{ --live; ++dropped; }}
    int64_t value() const {{ return {read}; }}
}};
''')
        write(root/f'{name}.cpp', f'''#include "{name}.hpp"
#include "{name}_payload.hpp"
struct {name}State {{ scpp::shared_p<{name}Payload> ptr; }};
{name}::{name}(int64_t v) : state_(new {name}State{{scpp::shared_p<{name}Payload>(std::make_shared<{name}Payload>(v))}}) {{}}
{name}::{name}(const {name}& x) : state_(x.state_ ? new {name}State{{x.state_->ptr}} : nullptr) {{}}
{name}::{name}({name}&& x) noexcept : state_(x.state_) {{ x.state_ = nullptr; }}
{name}& {name}::operator=(const {name}& x) {{
    if (this != &x) {{ auto *next = x.state_ ? new {name}State{{x.state_->ptr}} : nullptr; delete state_; state_ = next; }}
    return *this;
}}
{name}& {name}::operator=({name}&& x) noexcept {{
    if (this != &x) {{ delete state_; state_ = x.state_; x.state_ = nullptr; }} return *this;
}}
{name}::~{name}() {{ delete state_; }}
int64_t {name}::value() const {{ return state_->ptr->value(); }}
void {name}::value(int64_t v) {{ state_->ptr->value_ = v; }}
bool {name}::same(const {name}& x) const {{ return state_ && x.state_ && state_->ptr.get() == x.state_->ptr.get(); }}
int {name}::live() {{ return {name}Payload::live; }}
int {name}::made() {{ return {name}Payload::made; }}
int {name}::dropped() {{ return {name}Payload::dropped; }}
''')
        write(root/'bounds.hpp', '#pragma once\nstruct BoundsError {};\n')
        if variant == 'exposed':
            write(root/f'{name}Vector.hpp', f'''#pragma once
#include "{name}.hpp"
#include "{name}_payload.hpp"
#include "bounds.hpp"
class {name}Vector {{
    scpp::vector_t<{name}> values_;
public:
    size_t size() const {{ return values_.size(); }}
    void append({name} value) {{ values_.append(static_cast<{name}&&>(value)); }}
    {name} get(size_t i) const {{ if(i >= size()) throw BoundsError{{}}; return values_.at(i); }}
    void set(size_t i, {name} value) {{ if(i >= size()) throw BoundsError{{}}; values_.at(i) = static_cast<{name}&&>(value); }}
}};
''')
        else:
            storage = f'{name} *data_ = nullptr; size_t size_ = 0, capacity_ = 0;' if variant == 'lowered' else f'{name}VectorState *state_;'
            write(root/f'{name}Vector.hpp', f'''#pragma once
#include "{name}.hpp"
#include "bounds.hpp"
struct {name}VectorState;
class {name}Vector {{
    {storage}
public:
    {name}Vector();
    {name}Vector(const {name}Vector&);
    {name}Vector({name}Vector&&) noexcept;
    {name}Vector& operator=(const {name}Vector&);
    {name}Vector& operator=({name}Vector&&) noexcept;
    ~{name}Vector();
    size_t size() const;
    void append({name});
    {name} get(size_t) const;
    void set(size_t, {name});
}};
''')
            if variant == 'adapter':
                impl = f'''#include <scpp/lang/php.hpp>
struct {name}VectorState {{ scpp::vector_t<{name}> values; }};
{name}Vector::{name}Vector() : state_(new {name}VectorState) {{}}
{name}Vector::{name}Vector(const {name}Vector& x) : state_(x.state_ ? new {name}VectorState(*x.state_) : new {name}VectorState) {{}}
{name}Vector::{name}Vector({name}Vector&& x) noexcept : state_(x.state_) {{ x.state_=nullptr; }}
{name}Vector& {name}Vector::operator=(const {name}Vector& x) {{
    if(this != &x) {{ {name}Vector next(x); auto *old=state_; state_=next.state_; next.state_=old; }} return *this;
}}
{name}Vector& {name}Vector::operator=({name}Vector&& x) noexcept {{
    if(this != &x) {{ delete state_; state_=x.state_; x.state_=nullptr; }} return *this;
}}
{name}Vector::~{name}Vector() {{ delete state_; }}
size_t {name}Vector::size() const {{ return state_ ? state_->values.size() : 0; }}
void {name}Vector::append({name} v) {{ if(!state_) state_=new {name}VectorState; state_->values.append(static_cast<{name}&&>(v)); }}
{name} {name}Vector::get(size_t i) const {{ if(i>=size()) throw BoundsError{{}}; return state_->values.at(i); }}
void {name}Vector::set(size_t i, {name} v) {{ if(i>=size()) throw BoundsError{{}}; state_->values.at(i)=static_cast<{name}&&>(v); }}
'''
            else:
                impl = f'''#include <new>
{name}Vector::{name}Vector() = default;
{name}Vector::{name}Vector(const {name}Vector& x) {{
    auto *next = new {name}[x.size_];
    try {{ for(size_t i=0;i<x.size_;++i) next[i]=x.data_[i]; }} catch(...) {{ delete[] next; throw; }}
    data_=next; size_=capacity_=x.size_;
}}
{name}Vector::{name}Vector({name}Vector&& x) noexcept : data_(x.data_),size_(x.size_),capacity_(x.capacity_) {{
    x.data_=nullptr; x.size_=x.capacity_=0;
}}
{name}Vector& {name}Vector::operator=(const {name}Vector& x) {{
    if(this!=&x) {{ {name}Vector next(x); *this=static_cast<{name}Vector&&>(next); }} return *this;
}}
{name}Vector& {name}Vector::operator=({name}Vector&& x) noexcept {{
    if(this!=&x) {{ delete[] data_; data_=x.data_; size_=x.size_; capacity_=x.capacity_; x.data_=nullptr; x.size_=x.capacity_=0; }} return *this;
}}
{name}Vector::~{name}Vector() {{ delete[] data_; }}
size_t {name}Vector::size() const {{ return size_; }}
void {name}Vector::append({name} v) {{
    if(size_==capacity_) {{
        if(capacity_ > SIZE_MAX / 2 / sizeof({name})) throw std::bad_array_new_length();
        size_t capacity=capacity_ ? capacity_*2 : 4;
        auto *next=new {name}[capacity];
        // Concrete handles have noexcept moves; allocation completes before mutation.
        for(size_t i=0;i<size_;++i) next[i]=static_cast<{name}&&>(data_[i]);
        delete[] data_; data_=next; capacity_=capacity;
    }}
    data_[size_++]=static_cast<{name}&&>(v);
}}
{name} {name}Vector::get(size_t i) const {{ if(i>=size_) throw BoundsError{{}}; return data_[i]; }}
void {name}Vector::set(size_t i, {name} v) {{ if(i>=size_) throw BoundsError{{}}; data_[i]=static_cast<{name}&&>(v); }}
'''
            write(root/f'{name}Vector.cpp',f'#include "{name}Vector.hpp"\n'+impl)
    body = 10 if stage == 'base' else 11
    app = '#include <cassert>\n#include <cstdio>\n'
    for name in names:
        app += f'#include "{name}Vector.hpp"\n'
        app += f'''static int test_{name}() {{
    assert({name}::live()==0);
    int made={name}::made(), dropped={name}::dropped();
    {{
        {name} first({body});
        {name}Vector a; a.append(first);
        for(int i=0;i<32;++i) a.append({name}(i));
        a.append(a.get(0)); // aliased append across possible reallocation
        {name}Vector b(a);
        assert(a.size()==34 && b.size()==34);
        b.get(0).value(99);
        assert(first.value()==99 && a.get(0).value()==99);
        b.set(0,{name}(77));
        assert(a.get(0).same(first) && !b.get(0).same(first));
        b.append({name}(55));
        assert(a.size()==34 && b.size()==35);
        {name}Vector assigned; assigned=b;
        {name}Vector moved(static_cast<{name}Vector&&>(assigned));
        assert(moved.size()==35 && moved.get(0).value()==77);
        moved=moved; // self-assignment retains contents
        assert(moved.size()==35);
        bool failed=false;
        try {{ a.get(1000); }} catch(const BoundsError&) {{ failed=true; }}
        assert(failed);
        auto early=[&]() {{ {name}Vector temporary(a); temporary.append({name}(8)); return temporary.get(0); }};
        assert(early().same(first));
        int live={name}::live();
        try {{ {name}Vector temporary(a); temporary.append({name}(8)); throw 7; }} catch(int) {{}}
        assert({name}::live()==live);
        assert(first.value()==99);
    }}
    assert({name}::live()==0 && {name}::made()-made=={name}::dropped()-dropped);
    return {body};
}}
'''
    app += 'int main() { int result=0;\n' + ''.join(f'result+=test_{n}();\n' for n in names) + 'std::printf("%d\\n",result); }\n'
    write(root/'app.cpp',app)
    return {'sources':[f'{n}.cpp' for n in names]+([] if variant=='exposed' else [f'{n}Vector.cpp' for n in names])+['app.cpp'],
            'expected':str(body*len(names))+'\n'}

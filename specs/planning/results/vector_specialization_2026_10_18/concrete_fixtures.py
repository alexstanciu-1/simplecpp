"""Concrete record/vector specializations over one non-generic shared-owner API."""
from fixtures import emit as emit_vector, write

def emit(root, variant, stage):
    spec=emit_vector(root,'lowered',stage)
    write(root/'owner.hpp','''#pragma once
struct SharedOwner;
SharedOwner *owner_create(void *, void (*)(void *));
SharedOwner *owner_copy(const SharedOwner *);
void owner_drop(SharedOwner *);
void *owner_get(const SharedOwner *);
''')
    write(root/'owner.cpp','''#include "owner.hpp"
#include <memory>
struct SharedOwner { std::shared_ptr<void> ptr; };
SharedOwner *owner_create(void *value, void (*drop)(void *)) {
    // Establish cleanup before allocating the externally owned handle.
    std::shared_ptr<void> retained(value, drop);
    return new SharedOwner{retained};
}
SharedOwner *owner_copy(const SharedOwner *value) { return value ? new SharedOwner{value->ptr} : nullptr; }
void owner_drop(SharedOwner *value) { delete value; }
void *owner_get(const SharedOwner *value) { return value ? value->ptr.get() : nullptr; }
''')
    names=['Row','Other'] if stage=='specialization' else ['Row']
    for name in names:
        payload=(root/f'{name}_payload.hpp').read_text().replace('#include <scpp/lang/php.hpp>','#include <cstdint>')
        write(root/f'{name}_payload.hpp',payload)
        # The public handle remains pointer-sized and independent of payload layout.
        api=(root/f'{name}.hpp').read_text().replace(f'struct {name}State;', '#include "owner.hpp"').replace(f'{name}State *state_', 'SharedOwner *state_')
        write(root/f'{name}.hpp',api)
        write(root/f'{name}.cpp',f'''#include "{name}.hpp"
#include "{name}_payload.hpp"
static void destroy_{name}(void *value) {{ delete static_cast<{name}Payload*>(value); }}
{name}::{name}(int64_t v) : state_(owner_create(new {name}Payload(v), destroy_{name})) {{}}
{name}::{name}(const {name}& x) : state_(owner_copy(x.state_)) {{}}
{name}::{name}({name}&& x) noexcept : state_(x.state_) {{ x.state_=nullptr; }}
{name}& {name}::operator=(const {name}& x) {{
    if(this!=&x) {{ auto *next=owner_copy(x.state_); owner_drop(state_); state_=next; }} return *this;
}}
{name}& {name}::operator=({name}&& x) noexcept {{
    if(this!=&x) {{ owner_drop(state_); state_=x.state_; x.state_=nullptr; }} return *this;
}}
{name}::~{name}() {{ owner_drop(state_); }}
int64_t {name}::value() const {{ return static_cast<{name}Payload*>(owner_get(state_))->value(); }}
void {name}::value(int64_t v) {{ static_cast<{name}Payload*>(owner_get(state_))->value_=v; }}
bool {name}::same(const {name}& x) const {{ return state_ && x.state_ && owner_get(state_)==owner_get(x.state_); }}
int {name}::live() {{ return {name}Payload::live; }}
int {name}::made() {{ return {name}Payload::made; }}
int {name}::dropped() {{ return {name}Payload::dropped; }}
''')
    spec['sources']=['owner.cpp']+spec['sources']
    return spec

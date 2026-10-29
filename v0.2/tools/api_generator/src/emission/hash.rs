//! Hash declarations and wiring from resolved concrete plans.
use super::{BANNER, sequences::lifecycle};
use crate::{hash::KeyKind, runtime::RuntimePlan};
use std::collections::BTreeMap;
pub(super) fn emit(plan: &RuntimePlan, files: &mut BTreeMap<String, String>) {
    let Some(hashes) = &plan.hashes else {
        return;
    };
    let mut h = format!(
        "{BANNER}#pragma once\n#include \"{}\"\n#include \"{}\"\n",
        plan.scalars.headers["operators"],
        plan.carriers_header
            .as_ref()
            .expect("prepared nullable dependency")
    );
    if let Some(s) = &plan.string {
        add!(h, "#include \"{}\"\n", s.header);
    }
    h.push_str("namespace scpp {\n");
    let mut d = format!(
        "{BANNER}#include \"{}\"\n#include \"typed_hash.hpp\"\n#include \"scalar_semantics.hpp\"\nnamespace scpp {{\n",
        hashes.header
    );
    for hash in &hashes.specializations {
        let n = &hash.name;
        let k = &hash.key;
        let v = &hash.value;
        let nullable = &hash.nullable;
        let count = &hashes.count_type;
        let (lh, ld) = lifecycle(n);
        add!(
            h,
            "class {n} final {{\npublic:\n{lh}    {count} size() const;\n    bool_t empty() const noexcept;\n    bool_t has(const {k}& key) const;\n    {v} at(const {k}& key) const;\n    {nullable} find(const {k}& key) const;\n    void set({k} key, {v} value);\n    bool_t remove(const {k}& key);\n    void clear() noexcept;\n"
        );
        let policy = match hash.key_kind {
            KeyKind::Integer => "integer_hash_key",
            KeyKind::String => "string_hash_key",
        };
        add!(
            d,
            "struct {n}::storage {{ detail::typed_hash<{v}, {k}, detail::{policy}<{k}>> table; }};\n{ld}"
        );
        add!(
            d,
            "{count} {n}::size() const {{ return {count}{{detail::checked_integer_cast<long long>(storage_ ? storage_->table.size() : 0)}}; }}\nbool_t {n}::empty() const noexcept {{ return bool_t{{!storage_ || storage_->table.size() == 0}}; }}\nbool_t {n}::has(const {k}& key) const {{ return bool_t{{storage_ && storage_->table.find(key)}}; }}\n{v} {n}::at(const {k}& key) const {{ const auto* value = storage_ ? storage_->table.find(key) : nullptr; if (!value) throw std::out_of_range(\"hash_missing_key\"); return *value; }}\n{nullable} {n}::find(const {k}& key) const {{ const auto* value = storage_ ? storage_->table.find(key) : nullptr; return value ? {nullable}{{*value}} : {nullable}{{}}; }}\nvoid {n}::set({k} key, {v} value) {{ if (!storage_) storage_ = new storage; storage_->table.set(std::move(key), std::move(value)); }}\nbool_t {n}::remove(const {k}& key) {{ return bool_t{{storage_ && storage_->table.remove(key)}}; }}\nvoid {n}::clear() noexcept {{ delete storage_; storage_ = nullptr; }}\n"
        );
        if hash.key_kind == KeyKind::Integer {
            add!(h, "    {k} append({v} value);\n");
            add!(
                d,
                "{k} {n}::append({v} value) {{ if (!storage_) storage_ = new storage; return storage_->table.append(std::move(value)); }}\n"
            );
        }
        h.push_str("private:\n    struct storage;\n    storage* storage_ = nullptr;\n};\n");
    }
    h.push_str("}\n");
    d.push_str("}\n");
    files.insert(format!("public/{}", hashes.header), h);
    files.insert("src/hash.cpp".into(), d);
}

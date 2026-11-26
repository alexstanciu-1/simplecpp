#include "mysqli_provider.hpp"
#include <mysql.h>
#include "scpp/mysqli/bind_param.hpp"
#include "scpp/mixed.hpp"
#include <atomic>
#include <cerrno>
#include <cstdlib>
#include <charconv>
#include <memory>
#include <set>
#include <string>
#include <string_view>
#include <utility>
#include <vector>

namespace scpp::detail {
bool db_slot::accepts(char c) const {
    return (c == 'i' && (type == kind::integer || type == kind::boxed)) ||
           (c == 'd' && (type == kind::real || type == kind::boxed)) ||
           ((c == 's' || c == 'b') && (type == kind::bytes || type == kind::boxed));
}
dynamic db_slot::read() const {
    switch (type) {
    case kind::integer:
        return dynamic::from_integer(*static_cast<const int64 *>(address));
    case kind::real:
        return dynamic::from_floating(*static_cast<const float64 *>(address));
    case kind::bytes:
        return dynamic::from_string(*static_cast<const string *>(address));
    case kind::boxed: {
        const auto &v = *static_cast<const mixed *>(address);
        switch (v.kind()) {
        case boxed_kind::integer:
            return dynamic::from_integer(v.get_integer());
        case boxed_kind::floating:
            return dynamic::from_floating(v.get_floating());
        case boxed_kind::string:
            return dynamic::from_string(v.get_string());
        case boxed_kind::null:
            return {};
        default:
            return dynamic::from_boolean(false);
        }
    }
    }
    return {};
}
struct db_status {
    int64 number{};
    string message;
    int64 affected{}, inserted{};
    void fail(int64 n, const char *m) {
        number = n;
        message = string{m};
        affected = 0;
        inserted = 0;
    }
};
struct mysqli_stmt_state;
struct mysqli_state {
    std::atomic<size_t> references{1};
    MYSQL *native{};
    db_status status, connection;
    std::set<mysqli_stmt_state *> statements;
    ~mysqli_state() {
        if (native)
            mysql_close(native);
    }
};
struct mysqli_result_state {
    std::atomic<size_t> references{1};
    std::vector<string> names;
    std::vector<std::vector<dynamic>> rows;
    size_t cursor{};
};
struct mysqli_stmt_state {
    std::atomic<size_t> references{1};
    mysqli owner;
    MYSQL_STMT *native{};
    db_status status;
    string binding_types;
    std::vector<struct db_slot> slots;
    bool result_available{};
    ~mysqli_stmt_state();
};

mysqli_stmt_state::~mysqli_stmt_state() {
    if (native)
        mysql_stmt_close(native);
    if (auto *s = mysqli_access::get(owner))
        s->statements.erase(this);
}
} // namespace scpp::detail
namespace scpp::mysqli_api {
using namespace detail;
static MYSQL *connection(const mysqli &h) {
    auto *s = mysqli_access::get(h);
    if (s && !s->native)
        s->status.fail(1, "closed connection");
    return s ? s->native : nullptr;
}
static MYSQL_STMT *statement(const mysqli_stmt &h) {
    auto *s = mysqli_stmt_access::get(h);
    if (s && !s->native)
        s->status.fail(1, "closed statement");
    return s ? s->native : nullptr;
}
static std::string bytes(const string &s) {
    return {s.data(), static_cast<size_t>(s.size())};
}
static dynamic cell(enum_field_types type, const char *data, unsigned long len) {
    if (!data)
        return {};
    if (type == MYSQL_TYPE_BIT && len == 1)
        return dynamic::from_integer(static_cast<int64>(static_cast<unsigned char>(*data)));
    switch (type) {
    case MYSQL_TYPE_TINY:
    case MYSQL_TYPE_SHORT:
    case MYSQL_TYPE_LONG:
    case MYSQL_TYPE_INT24:
    case MYSQL_TYPE_YEAR:
    case MYSQL_TYPE_LONGLONG: {
        int64 i{};
        auto [end, ec] = std::from_chars(data, data + len, i);
        if (ec == std::errc{} && end == data + len)
            return dynamic::from_integer(i);
        break;
    }
    case MYSQL_TYPE_FLOAT:
    case MYSQL_TYPE_DOUBLE:
    case MYSQL_TYPE_DECIMAL:
    case MYSQL_TYPE_NEWDECIMAL: {
        std::string text(data, len);
        char *end{};
        errno = 0;
        double v = std::strtod(text.c_str(), &end);
        if (!errno && end == text.data() + len)
            return dynamic::from_floating(v);
        break;
    }
    default:
        break;
    }
    return dynamic::from_string(string{data, static_cast<int64>(len)});
}
mysqli php_connect(const string &host, const string &user, const string &password, const string &db,
                   int64 port, const string &socket) {
    auto *s = new mysqli_state;
    auto handle = mysqli_access::adopt(s);
    auto h = bytes(host), u = bytes(user), p = bytes(password), d = bytes(db), sock = bytes(socket);
    bool valid = port >= 0 && port <= 65535;
    for (auto *a : {&h, &u, &p, &d, &sock})
        if (a->find('\0') != a->npos)
            valid = false;
    if (!valid)
        s->connection.fail(1, "invalid connection arguments");
    else if (!(s->native = mysql_init(nullptr)))
        s->connection.fail(1, "connector initialization failed");
    else if (!mysql_real_connect(s->native, h.c_str(), u.c_str(), p.c_str(), d.empty() ? nullptr : d.c_str(),
                                 static_cast<unsigned>(port), sock.empty() ? nullptr : sock.c_str(), 0)) {
        s->connection.fail(mysql_errno(s->native), mysql_error(s->native));
        mysql_close(std::exchange(s->native, nullptr));
    }
    s->status = s->connection;
    return handle;
}
result_mysqli connect(const string &h, const string &u, const string &p, const string &d, int64 port,
                      const string &sock) {
    auto v = php_connect(h, u, p, d, port, sock);
    auto *s = mysqli_access::get(v);
    if (!s->native)
        return result_mysqli{error_t{s->connection.message}};
    return result_mysqli{std::move(v)};
}
static bool update(mysqli_state &s, int rc) {
    if (rc) {
        s.status.fail(mysql_errno(s.native), mysql_error(s.native));
        return false;
    }
    s.status = {};
    s.status.inserted = static_cast<int64>(mysql_insert_id(s.native));
    s.status.affected = static_cast<int64>(mysql_affected_rows(s.native));
    return true;
}
result_or_bool_mysqli_result query(const mysqli &h, const string &sql) {
    auto *n = connection(h);
    if (!n)
        return {};
    auto &s = *mysqli_access::get(h);
    if (!update(s, mysql_real_query(n, sql.data(), sql.size())))
        return {};
    if (!mysql_field_count(n))
        return result_or_bool_mysqli_result::from_true();
    std::unique_ptr<MYSQL_RES, decltype(&mysql_free_result)> res(mysql_store_result(n), mysql_free_result);
    if (!res) {
        update(s, 1);
        return {};
    }
    auto *out = new mysqli_result_state;
    auto handle = mysqli_result_access::adopt(out);
    auto count = mysql_num_fields(res.get());
    auto *fields = mysql_fetch_fields(res.get());
    for (unsigned i = 0; i < count; ++i)
        out->names.emplace_back(fields[i].name, fields[i].name_length);
    while (auto row = mysql_fetch_row(res.get())) {
        auto *lengths = mysql_fetch_lengths(res.get());
        auto &values = out->rows.emplace_back();
        for (unsigned i = 0; i < count; ++i)
            values.push_back(cell(fields[i].type, row[i], lengths[i]));
    }
    if (mysql_errno(n)) {
        update(s, 1);
        return {};
    }
    s.status.affected = out->rows.size();
    return result_or_bool_mysqli_result{std::move(handle)};
}
result_or_false_mysqli_stmt prepare(const mysqli &h, const string &sql) {
    auto *n = connection(h);
    if (!n)
        return {};
    auto &c = *mysqli_access::get(h);
    auto *s = new mysqli_stmt_state;
    auto handle = mysqli_stmt_access::adopt(s);
    s->owner = h;
    s->native = mysql_stmt_init(n);
    if (!s->native) {
        update(c, 1);
        return {};
    }
    if (mysql_stmt_prepare(s->native, sql.data(), sql.size())) {
        c.status.fail(mysql_stmt_errno(s->native), mysql_stmt_error(s->native));
        return {};
    }
    c.statements.insert(s);
    c.status = {};
    return result_or_false_mysqli_stmt{std::move(handle)};
}
void stmt_close(const mysqli_stmt &h) {
    auto *s = mysqli_stmt_access::get(h);
    if (!s)
        return;
    if (s->native)
        mysql_stmt_close(std::exchange(s->native, nullptr));
    s->slots.clear();
    if (auto *c = mysqli_access::get(s->owner))
        c->statements.erase(s);
}
void close(const mysqli &h) {
    auto *c = mysqli_access::get(h);
    if (!c || !c->native)
        return;
    for (auto *s : c->statements) {
        mysql_stmt_close(std::exchange(s->native, nullptr));
        s->slots.clear();
    }
    c->statements.clear();
    mysql_close(std::exchange(c->native, nullptr));
}
bool_t set_charset(const mysqli &h, const string &charset) {
    auto *n = connection(h);
    if (!n)
        return false;
    auto &s = *mysqli_access::get(h);
    auto name = bytes(charset);
    if (name.find('\0') != name.npos) {
        s.status.fail(1, "invalid charset");
        return false;
    }
    return update(s, mysql_set_character_set(n, name.c_str()));
}
bool_t begin_transaction(const mysqli &h) {
    auto *n = connection(h);
    return n && update(*mysqli_access::get(h), mysql_query(n, "START TRANSACTION"));
}
bool_t commit(const mysqli &h) {
    auto *n = connection(h);
    return n && update(*mysqli_access::get(h), mysql_commit(n));
}
bool_t rollback(const mysqli &h) {
    auto *n = connection(h);
    return n && update(*mysqli_access::get(h), mysql_rollback(n));
}
static dynamic fetch(const mysqli_result &h, bool assoc) {
    auto *s = mysqli_result_access::get(h);
    if (!s || s->cursor >= s->rows.size())
        return {};
    const auto &cells = s->rows[s->cursor++];
    auto row = dynamic::container();
    for (size_t i = 0; i < cells.size(); ++i) {
        if (assoc)
            row.set(s->names[i], cells[i]);
        else
            row.append(cells[i]);
    }
    return row;
}
dynamic fetch_row(const mysqli_result &h) {
    return fetch(h, false);
}
dynamic fetch_assoc(const mysqli_result &h) {
    return fetch(h, true);
}
static bool stmt_fail(mysqli_stmt_state &s) {
    s.status.fail(mysql_stmt_errno(s.native), mysql_stmt_error(s.native));
    return false;
}
struct param {
    long long integer{};
    double real{};
    string data;
};
static bool execute(mysqli_stmt_state &s, const string &types, const std::vector<dynamic> &values) {
    s.result_available = false;
    mysql_stmt_free_result(s.native);
    if (values.size() != mysql_stmt_param_count(s.native) ||
        static_cast<size_t>(types.size()) != values.size()) {
        s.status.fail(1, "bound argument count mismatch");
        return false;
    }
    std::vector<MYSQL_BIND> bindings(values.size());
    std::vector<param> storage(values.size());
    for (size_t i = 0; i < values.size(); ++i) {
        auto &b = bindings[i];
        auto &v = values[i];
        auto &p = storage[i];
        char type = types.data()[i];
        if (std::string_view("idsb").find(type) == std::string_view::npos) {
            s.status.fail(1, "invalid binding type");
            return false;
        }
        if (v.kind() == boxed_kind::null)
            b.buffer_type = MYSQL_TYPE_NULL;
        else if (type == 'i' && v.kind() == boxed_kind::integer) {
            p.integer = v.get_integer();
            b.buffer_type = MYSQL_TYPE_LONGLONG;
            b.buffer = &p.integer;
        } else if (type == 'd' && v.kind() == boxed_kind::floating) {
            p.real = v.get_floating();
            b.buffer_type = MYSQL_TYPE_DOUBLE;
            b.buffer = &p.real;
        } else if ((type == 's' || type == 'b') && v.kind() == boxed_kind::string) {
            p.data = v.get_string();
            b.buffer_type = type == 'b' ? MYSQL_TYPE_BLOB : MYSQL_TYPE_STRING;
            b.buffer = const_cast<char *>(p.data.data());
            b.buffer_length = p.data.size();
        } else {
            s.status.fail(1, "bound argument type mismatch");
            return false;
        }
    }
    if ((!bindings.empty() && mysql_stmt_bind_param(s.native, bindings.data())) ||
        mysql_stmt_execute(s.native))
        return stmt_fail(s);
    s.status = {};
    s.status.inserted = static_cast<int64>(mysql_stmt_insert_id(s.native));
    s.status.affected = static_cast<int64>(mysql_stmt_affected_rows(s.native));
    s.result_available = mysql_stmt_field_count(s.native) != 0;
    auto *c = mysqli_access::get(s.owner);
    if (c)
        c->status = s.status;
    return true;
}
bool_t execute_values(const mysqli_stmt &h, const string &types, const dynamic &input) {
    if (!statement(h))
        return false;
    auto &s = *mysqli_stmt_access::get(h);
    if (input.kind() != boxed_kind::container || !input.is_packed_container()) {
        s.status.fail(1, "expected packed argument list");
        return false;
    }
    std::vector<dynamic> values;
    for (int64 i = 0; i < input.container_size(); ++i)
        values.push_back(input.read(i));
    return execute(s, types, values);
}
bool_t bind_slots(const mysqli_stmt &h, const string &types, const db_slot *slots, size_t count) {
    if (!statement(h))
        return false;
    auto &s = *mysqli_stmt_access::get(h);
    if (static_cast<size_t>(types.size()) != count || mysql_stmt_param_count(s.native) != count) {
        s.status.fail(1, "invalid binding count");
        return false;
    }
    for (size_t i = 0; i < count; ++i)
        if (!slots[i].accepts(types.data()[i])) {
            s.status.fail(1, "invalid binding type");
            return false;
        }
    s.slots.assign(slots, slots + count);
    s.binding_types = types;
    s.status = {};
    return true;
}
bool_t stmt_execute(const mysqli_stmt &h) {
    if (!statement(h))
        return false;
    auto &s = *mysqli_stmt_access::get(h);
    std::vector<dynamic> values;
    for (const auto &slot : s.slots)
        values.push_back(slot.read());
    return execute(s, s.binding_types, values);
}
result_or_false_mysqli_result stmt_get_result(const mysqli_stmt &h) {
    auto *n = statement(h);
    if (!n)
        return {};
    auto &s = *mysqli_stmt_access::get(h);
    if (!std::exchange(s.result_available, false)) {
        s.status.fail(1, "statement has no pending result");
        return {};
    }
    std::unique_ptr<MYSQL_RES, decltype(&mysql_free_result)> meta(mysql_stmt_result_metadata(n),
                                                                  mysql_free_result);
    if (!meta || mysql_stmt_store_result(n)) {
        stmt_fail(s);
        return {};
    }
    auto *out = new mysqli_result_state;
    auto handle = mysqli_result_access::adopt(out);
    unsigned count = mysql_num_fields(meta.get());
    auto *fields = mysql_fetch_fields(meta.get());
    struct field {
        char inline_data[128]{};
        double real{};
        unsigned long length{};
        my_bool null{};
    };
    std::vector<field> storage(count);
    std::vector<MYSQL_BIND> binds(count);
    for (unsigned i = 0; i < count; ++i) {
        out->names.emplace_back(fields[i].name, fields[i].name_length);
        auto &b = binds[i];
        auto &f = storage[i];
        b.buffer_type = MYSQL_TYPE_STRING;
        b.buffer = f.inline_data;
        b.buffer_length = sizeof(f.inline_data);
        if (fields[i].type == MYSQL_TYPE_FLOAT || fields[i].type == MYSQL_TYPE_DOUBLE) {
            b.buffer_type = MYSQL_TYPE_DOUBLE;
            b.buffer = &f.real;
            b.buffer_length = sizeof(f.real);
        }
        b.length = &f.length;
        b.is_null = &f.null;
    }
    if (mysql_stmt_bind_result(n, binds.data())) {
        stmt_fail(s);
        return {};
    }
    int rc;
    while ((rc = mysql_stmt_fetch(n)) == 0 || rc == MYSQL_DATA_TRUNCATED) {
        auto &row = out->rows.emplace_back();
        for (unsigned i = 0; i < count; ++i) {
            auto &f = storage[i];
            if (f.null) {
                row.emplace_back();
                continue;
            }
            if (binds[i].buffer_type == MYSQL_TYPE_DOUBLE) {
                row.push_back(dynamic::from_floating(f.real));
                continue;
            }
            if (f.length < sizeof(f.inline_data)) {
                row.push_back(cell(fields[i].type, f.inline_data, f.length));
                continue;
            }
            std::string full(static_cast<size_t>(f.length) + 1, '\0');
            auto b = binds[i];
            b.buffer = full.data();
            b.buffer_length = full.size();
            if (mysql_stmt_fetch_column(n, &b, i, 0)) {
                stmt_fail(s);
                return {};
            }
            row.push_back(cell(fields[i].type, full.data(), f.length));
        }
    }
    if (rc != MYSQL_NO_DATA) {
        stmt_fail(s);
        return {};
    }
    mysql_stmt_free_result(n);
    s.status = {};
    return result_or_false_mysqli_result{std::move(handle)};
}
result_mysqli connect() {
    return connect(string{"127.0.0.1"}, string{}, string{}, string{}, 3306, string{});
}
result_mysqli connect(const string &host) {
    return connect(host, string{}, string{}, string{}, 3306, string{});
}
result_mysqli connect(const string &host, const string &user) {
    return connect(host, user, string{}, string{}, 3306, string{});
}
result_mysqli connect(const string &host, const string &user, const string &password) {
    return connect(host, user, password, string{}, 3306, string{});
}
result_mysqli connect(const string &host, const string &user, const string &password, const string &db) {
    return connect(host, user, password, db, 3306, string{});
}
result_mysqli connect(const string &host, const string &user, const string &password, const string &db,
                      int64 port) {
    return connect(host, user, password, db, port, string{});
}
mysqli php_connect() {
    return php_connect(string{"127.0.0.1"}, string{}, string{}, string{}, 3306, string{});
}
mysqli php_connect(const string &host) {
    return php_connect(host, string{}, string{}, string{}, 3306, string{});
}
mysqli php_connect(const string &host, const string &user) {
    return php_connect(host, user, string{}, string{}, 3306, string{});
}
mysqli php_connect(const string &host, const string &user, const string &password) {
    return php_connect(host, user, password, string{}, 3306, string{});
}
mysqli php_connect(const string &host, const string &user, const string &password, const string &db) {
    return php_connect(host, user, password, db, 3306, string{});
}
mysqli php_connect(const string &host, const string &user, const string &password, const string &db,
                   int64 port) {
    return php_connect(host, user, password, db, port, string{});
}
int64 connect_errno(const mysqli &h) {
    auto *s = mysqli_access::get(h);
    return s ? s->connection.number : int64{};
}
string connect_error(const mysqli &h) {
    auto *s = mysqli_access::get(h);
    return s ? s->connection.message : string{};
}
string error(const mysqli &h) {
    auto *s = mysqli_access::get(h);
    return s ? s->status.message : string{};
}
int64 errno_code(const mysqli &h) {
    auto *s = mysqli_access::get(h);
    return s ? s->status.number : int64{};
}
int64 insert_id(const mysqli &h) {
    auto *s = mysqli_access::get(h);
    return s ? s->status.inserted : int64{};
}
int64 affected_rows(const mysqli &h) {
    auto *s = mysqli_access::get(h);
    return s ? s->status.affected : int64{};
}
string stmt_error(const mysqli_stmt &h) {
    auto *s = mysqli_stmt_access::get(h);
    return s ? s->status.message : string{};
}
int64 stmt_errno_code(const mysqli_stmt &h) {
    auto *s = mysqli_stmt_access::get(h);
    return s ? s->status.number : int64{};
}
int64 stmt_insert_id(const mysqli_stmt &h) {
    auto *s = mysqli_stmt_access::get(h);
    return s ? s->status.inserted : int64{};
}
int64 stmt_affected_rows(const mysqli_stmt &h) {
    auto *s = mysqli_stmt_access::get(h);
    return s ? s->status.affected : int64{};
}
int64 result_num_rows(const mysqli_result &h) {
    auto *s = mysqli_result_access::get(h);
    return s ? static_cast<int64>(s->rows.size()) : 0;
}
} // namespace scpp::mysqli_api
#include "mysqli_values.hpp"

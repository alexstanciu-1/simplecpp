"""Report dimensions, units and measures for the three operational dashboards."""
from itertools import combinations

# Integer dimensions use upper-inclusive boundaries; the last band is open.
def bands(field, *limits):
    return field, list(limits)


ACCESS = {
    "status": bands("status", ("success", 299), ("redirect", 399), ("client_error", 499), ("server_error", None)),
    "method": ("method", ["GET", "HEAD", "POST", "PUT", "PATCH", "DELETE"]),
    "cache": ("cache", ["HIT", "MISS", "BYPASS"]),
    "route": ("category", ["health", "assets", "api", "other"]),
    "latency": bands("elapsed", ("fast", 50), ("interactive", 250), ("delayed", 1000), ("slow", None)),
    "response": bands("bytes", ("empty", 0), ("small", 255), ("medium", 4095), ("large", None)),
    "depth": bands("depth", ("root", 1), ("resource", 2), ("nested", None)),
}
STOCK = {
    "movement": ("kind", ["RECEIVE", "HOLD", "RELEASE", "SHIP", "COUNT"]),
    "decision": ("decision", ["posted", "refused"]),
    "order_size": bands("quantity", ("zero", 0), ("single", 1), ("case", 10), ("bulk", None)),
    "availability": bands("available", ("none", 0), ("low", 5), ("standard", 50), ("surplus", None)),
    "balance": bands("on_hand", ("empty", 0), ("low", 10), ("normal", 100), ("high", None)),
    "reservation": bands("reserved", ("none", 0), ("small", 5), ("case", 20), ("bulk", None)),
    "unit_price": bands("price", ("unpriced", 0), ("minor", 100), ("standard", 1000), ("high", None)),
}
ROUTES = {
    "method": ("method", ["GET", "HEAD", "POST", "PUT", "PATCH", "DELETE"]),
    "resolution": ("resolution", ["unmatched", "exact", "prefix"]),
    "scope": ("scope", ["missing", "method", "any"]),
    "depth": bands("depth", ("root", 1), ("resource", 2), ("nested", None)),
    "path_size": bands("path_bytes", ("short", 8), ("medium", 24), ("long", 64), ("extended", None)),
    "coverage": bands("covered", ("none", 0), ("partial", 50), ("most", 99), ("complete", None)),
    "table": bands("candidates", ("empty", 0), ("small", 8), ("medium", 64), ("large", None)),
}
DOMAINS = {
    "access-log": (ACCESS, ["elapsed", "bytes", "depth"]),
    "stock-ledger": (STOCK, ["quantity", "available", "reserved", "on_hand"]),
    "route-table": (ROUTES, ["path_bytes", "matched_bytes", "depth", "candidates"]),
}


def reports(name):
    axes, measures = DOMAINS[name]
    # First publish single dimension summaries, then cross-tabs, then drilldowns.
    for arity in (1, 2, 3):
        for i, keys in enumerate(combinations(axes, arity)):
            measure = measures[i % len(measures)]
            yield keys, measure

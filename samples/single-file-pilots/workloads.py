"""Larger deterministic input files and independently calculated reports for the pilot."""
from pathlib import Path


def access(groups):
    rows = [
        "web GET /assets/app.js?v=7 200 1200 10 HIT",
        "api POST /api/orders?token=test 201 350 240 MISS",
        "api GET /api/orders 503 64 1200 BYPASS",
        "web HEAD /ready 200 0 2 BYPASS",
        "web GET /old 301 30 50 HIT",
        "api DELETE /api/orders/4 404 80 100 MISS",
        "api GET /invalid#fragment 200 0 1 HIT",
        "bad row",
    ]
    report = f"""requests {6*groups} rejected {2*groups}
responses {3*groups} {groups} {groups} {groups}
bytes {1724*groups} writes {2*groups}
routes health {groups} static {groups} api {3*groups}
cache-percent 50
latency-ms mean 267 p50 50 p95 1200
slowest /api/orders 1200
largest /assets/app.js 1200
error-budget {5*groups//100-groups}
grade unhealthy
service web {3*groups}
service api {3*groups}
"""
    return rows * groups, report


def stock(groups):
    rows = []
    # Each batch liquidates its receipts, so the next batch starts at zero.
    for i in range(groups):
        rows.extend([f"{i}-a|pen|receive|10|100", f"{i}-b|pen|hold|8|0",
                     f"{i}-c|pen|release|2|0", f"{i}-d|pen|ship|4|200",
                     f"{i}-e|pen|count|5|0", f"{i}-f|pen|ship|5|200",
                     f"{i}-d|pen|ship|4|200", f"{i}-g|pen|ship|2|200"])
    return rows, f"""posted {6*groups} invalid 0 duplicates {groups} refused {groups}
receipts {groups} holds {groups} releases {groups} shipments {2*groups} counts {groups}
item pen empty
  stock 0 held 0 available 0 cents 0
  received {10*groups} shipped {9*groups} variance {-groups} shortfalls {groups}
  sales {1800*groups} cost {900*groups} margin {900*groups}
warehouse cents 0 available 0
"""


def routes(groups):
    rows = ["backend edge core.example 80 on", "backend ping health.example 443 on"]
    for i in range(32):
        rows.extend([f"route ANY prefix /v{i} edge", f"route GET exact /v{i}/ping ping"])
    replies = []
    for i in range(groups):
        path = f"/v{i%32}"
        rows.extend([f"test GET {path}/ping?probe=1", f"test POST {path}/orders", f"test GET {path}x"])
        replies.extend([f"match GET {path}/ping ping", f"match POST {path}/orders edge",
                        f"unmatched GET {path}x"])
    replies.extend(["backend edge core.example:80 routes 32", "backend ping health.example:443 routes 32",
                    f"matched {2*groups} unmatched {groups} errors 0 warnings 0"])
    return rows, "\n".join(replies) + "\n"


def write(name, directory, groups=128):
    rows, report = {"access-log": access, "stock-ledger": stock, "route-table": routes}[name](groups)
    directory = Path(directory)
    directory.mkdir(parents=True, exist_ok=True)
    (directory / "input.txt").write_text("\n".join(rows) + "\n")
    (directory / "expected.txt").write_text(report)
    return len(rows)

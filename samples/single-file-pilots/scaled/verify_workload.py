"""Independent row-oriented oracles for the deterministic operational pilot corpora."""
from collections import Counter
import sys

from catalog import DOMAINS
from generate import BASE, materialize
sys.path.insert(0, str(BASE))
from workloads import access, stock, routes


def events(name, rows):
    if name == 'access-log':
        for row in rows:
            fields = row.split()
            if len(fields) != 7 or '#' in fields[2]:
                continue
            service, method, uri, code, size, latency, cache = fields
            path = uri.partition('?')[0]
            category = ('health' if path in ('/ready','/health') else 'api' if path.startswith('/api/')
                        else 'assets' if path.startswith('/assets/') else 'other')
            yield dict(status=int(code), bytes=int(size), elapsed=int(latency), depth=path.count('/'),
                       category=category, method=method, cache=cache)
    elif name == 'stock-ledger':
        balances, seen = {}, set()
        for row in rows:
            doc, sku, kind, quantity, price = row.split('|')
            if doc in seen:
                continue
            seen.add(doc)
            quantity, price = int(quantity), int(price)
            on_hand, reserved = balances.get(sku, (0,0))
            decision = 'posted'
            if kind == 'receive':
                on_hand += quantity
            elif kind == 'ship' and on_hand >= quantity:
                on_hand -= quantity
                reserved = max(0,reserved-quantity)
            elif kind == 'hold' and on_hand-reserved >= quantity:
                reserved += quantity
            elif kind == 'release' and reserved >= quantity:
                reserved -= quantity
            elif kind == 'count' and quantity >= reserved:
                on_hand = quantity
            else:
                decision = 'refused'
            balances[sku] = on_hand, reserved
            yield dict(kind=kind.upper(), quantity=quantity, price=price, on_hand=on_hand,
                       reserved=reserved, available=on_hand-reserved, decision=decision, sku=sku)
    else:
        rules = [row.split()[1:] for row in rows if row.startswith('route ')]
        for row in rows:
            if not row.startswith('test '):
                continue
            _, method, path = row.split()
            path = path.partition('?')[0]
            matches = [(len(p), kind=='exact', verb==method, p, target, verb, kind)
                       for verb,kind,p,target in rules if verb in (method,'ANY') and
                       (path == p or (kind=='prefix' and path.startswith(p.rstrip('/')+'/')))]
            winner = max(matches) if matches else None
            length = winner[0] if winner else 0
            yield dict(method=method, depth=path.count('/'), path_bytes=len(path), candidates=len(rules),
                       covered=100*length//len(path), matched_bytes=length,
                       resolution=winner[-1] if winner else 'unmatched', target=winner[4] if winner else '',
                       scope=('method' if winner[2] else 'any') if winner else 'missing')


def sequence_report(name, stream):
    if name == 'access-log':
        streaks, peak, affected, run = [], 0, 0, 0
        for e in stream:
            if e['status'] >= 500 or e['elapsed'] > 1000:
                run += 1
                peak = max(peak, e['elapsed'])
                affected += e['elapsed']
            elif run:
                streaks.append(run)
                run = 0
        if run:
            streaks.append(run)
        return f'incidents {len(streaks)} longest {max(streaks,default=0)} affected-ms {affected} peak-ms {peak}\n'
    if name == 'stock-ledger':
        previous, counts = {}, Counter()
        depletions = restocks = refused = 0
        for e in stream:
            sku, free = e['sku'], e['available']
            if sku in previous:
                depletions += previous[sku] > 0 and free == 0
                restocks += previous[sku] == 0 and free > 0
            counts[sku] += 1
            previous[sku] = free
            refused += free == 0 and e['decision'] == 'refused'
        busiest, entries = counts.most_common(1)[0] if counts else ('',0)
        return (f'depletions {depletions} restocks {restocks} refused-at-zero {refused} '
                f'busiest {busiest} entries {entries}\n')
    previous, counts = '', Counter()
    switches = run = longest = 0
    for e in stream:
        target = e['target']
        if target:
            switches += bool(previous) and previous != target
            counts[target] += 1
            run = 0
        else:
            run += 1
            longest = max(longest,run)
        previous = target
    return f'target-switches {switches} longest-gap {longest}\n' + ''.join(
        f'target-load {k} {v}\n' for k,v in counts.items())


def dashboard(name, size, rows):
    axes, _ = DOMAINS[name]
    _, plans = materialize(name,size*1000)
    stream = list(events(name,rows))
    cells = {}
    for e in stream:
        labels = {}
        for key,(field,options) in axes.items():
            v = e[field]
            label = v.lower() if isinstance(v,str) else next(
                label for label,limit in options if limit is None or v <= limit)
            labels[key] = f'{key}={label}'
        for keys,measure in plans:
            key = '/'.join([measure, *(labels[k] for k in keys)])
            v = e[measure]
            count,total,low,high = cells.get(key,(0,0,v,v))
            cells[key] = count+1,total+v,min(low,v),max(high,v)
    report = f'dashboard {len(cells)} occupied cells\n'
    report += ''.join(f'{k} {n} {s} {low} {high}\n' for k,(n,s,low,high) in cells.items())
    if size == 100:
        report += sequence_report(name,stream)
    return report


def fixture(name, size, directory, details, groups=128):
    rows, report = {'access-log':access,'stock-ledger':stock,'route-table':routes}[name](groups)
    if details:
        report += dashboard(name, size, rows)
    directory.mkdir(parents=True,exist_ok=True)
    (directory/'input.txt').write_text(('# dashboard\n' if details else '') + '\n'.join(rows)+'\n')
    (directory/'expected.txt').write_text(report)
    return len(rows)

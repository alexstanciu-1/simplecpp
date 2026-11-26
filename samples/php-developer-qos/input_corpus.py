"""Run larger independent input controls against three of the small applications."""
import itertools
import json
from pathlib import Path
import shutil
import subprocess
import tempfile
from urllib.parse import urlencode

from check_apps import build, check_cli, suite


def invoices(exe, work):
    # The writer knows the amounts before serializing them. Preserve account
    # insertion order, including strings that resemble the same integer.
    totals = {}
    accounts = ['01', '1', '0', '000', 'mail', 'münchen', 'accounts payable']
    rows = []
    invalid = 0
    for index in range(10_000):
        key = accounts[index % len(accounts)]
        amount = (index * 31_141) % 100_000_000
        if index % 11 == 0:
            rows.append(f'{key}|{amount}x')
            invalid += 1
        elif index % 17 == 0:
            rows.append('')
        else:
            rows.append(f'{key}| {amount:08d} ')
            totals[key] = totals.get(key, 0) + amount
    ledger = work / 'invoices'
    ledger.write_bytes('\r\n'.join(rows).encode())
    expected = ''.join(f'{key}={value}\n' for key, value in totals.items())
    check_cli(exe, [ledger], 0, expected + f'invalid={invalid}\n')
    print(f'invoices: {len(rows)} rows, {len(totals)} distinct accounts, {invalid} rejected')


def form_components(exe, work):
    # An independent URL encoder produces the body. Check decoded JSON so
    # equivalent JSON escapes do not turn into false differences.
    labels = ['0', '01', 'a b', 'a+b', 'a&b=c', '%26', '"quoted"', 'café', 'cafe\u0301',
              '🍎', '\x00', 'a\nb', '漢字', 'a'*80, '😀'*80, 'e\u0301'*40]
    cases = 0
    for label, notify, reverse in itertools.product(labels, [None, 'on', 'off'], [False, True]):
        fields = [('label', label)]
        if notify is not None:
            fields.append(('notify', notify))
        if reverse:
            fields.reverse()
        if reverse and notify is None:
            continue  # Identical serialization for a single field.
        body = urlencode(fields)
        p = subprocess.run([exe, body], capture_output=True, timeout=5)
        if p.returncode or p.stderr or json.loads(p.stdout) != {'label': label, 'notify': notify == 'on'}:
            raise AssertionError((body, p.returncode, p.stdout, p.stderr))
        cases += 1
    print(f'forms: {cases} URL-encoded bodies round-tripped')


def patches(exe, work):
    omitted = object()
    enabled_values = [omitted, False, True, None, 0, 1, '', '0', [], {}]
    limit_values = [omitted, None, -1, 0, 1, 99, 100, 101, 1.0, False, True, '0', '', [], {}]
    cases = 0
    for enabled, limit in itertools.product(enabled_values, limit_values):
        fields = [(key, value) for key, value in [('enabled', enabled), ('limit', limit)] if value is not omitted]
        orders = [fields, fields[::-1]] if len(fields) == 2 else [fields]
        for ordered in orders:
            body = json.dumps(dict(ordered), separators=(',', ':'))
            control = subprocess.run(['php', '-n', suite/'stock-php/settings-patch.php', body],
                                     capture_output=True, timeout=5)
            if control.stderr or control.returncode not in (0, 1):
                raise AssertionError((body, control))
            check_cli(exe, [body], control.returncode, control.stdout.decode())
            cases += 1
    print(f'settings: {cases} distinct requests matched stock PHP output and status')


if __name__ == '__main__':
    for name, run in [('invoice-summary', invoices), ('form-preview', form_components), ('settings-patch', patches)]:
        with tempfile.TemporaryDirectory(prefix='qos-corpus-') as directory:
            work = Path(directory)
            app = work/'app'
            shutil.copytree(suite/name, app, ignore=shutil.ignore_patterns('module.sources.json'))
            run(build(app, work), work)

"""Preserved exact CLI expectations from the small accounting fixture."""
USAGE = ('accounting self-test\n'
         'accounting sale NET TAX PAYMENT [REFERENCE]\n'
         'accounting stock RECEIVED UNIT_COST ISSUED\n'
         'accounting allocate AMOUNT PARTS\n'
         'Amounts are integer minor units; each command starts a fresh company.\n')
BASELINE = ('accounting workload: invoice/payment/trial-balance checks passed\n'
            'CHECKS passed=998 failed=0\nACCOUNTING_WORKLOAD_PASS\n')


def sale_output(net, tax, paid, payment='ok', reference='CLI-PAYMENT'):
    return (f'invoice=CLI-001 net={net} tax={tax} gross={net + tax}\n'
            f'payment={payment}\nreference={reference}\n'
            f'paid={paid} outstanding={net + tax - paid}\n'
            f'bank={paid} receivables={net + tax - paid} entries={2 if paid else 1}\n'
            'balanced=1 reconciled=1\n')


def cases():
    rows = [
        ('default-self-test', [], BASELINE, 0),
        ('explicit-self-test', ['self-test'], BASELINE, 0),
        ('help', ['--help'], USAGE, 0),
        ('unknown-command', ['no-such-command'], 'error=argument.usage\n' + USAGE, 64),
        ('missing-sale-arguments', ['sale', '100'], 'error=argument.usage\n' + USAGE, 64),
        ('extra-self-test-argument', ['self-test', 'extra'], 'error=argument.usage\n' + USAGE, 64),
        ('sale-partial', ['sale', '10000', '2000', '7000'], sale_output(10000, 2000, 7000), 0),
        ('sale-full', ['sale', '10000', '2000', '12000'], sale_output(10000, 2000, 12000), 0),
        ('sale-unpaid', ['sale', '10000', '2000', '0'], sale_output(10000, 2000, 0, 'skipped'), 0),
        ('sale-zero-tax', ['sale', '100', '0', '100'], sale_output(100, 0, 100), 0),
        ('sale-leading-zeroes', ['sale', '00100', '00', '001'], sale_output(100, 0, 1), 0),
        ('sale-spaced-reference', ['sale', '100', '20', '50', 'receipt two words'], sale_output(100, 20, 50, reference='receipt two words'), 0),
        ('sale-utf8-reference', ['sale', '100', '20', '50', 'plată-中😀'], sale_output(100, 20, 50, reference='plată-中😀'), 0),
        ('sale-flaglike-reference', ['sale', '100', '20', '50', '--reference=x'], sale_output(100, 20, 50, reference='--reference=x'), 0),
        ('sale-empty-reference', ['sale', '100', '20', '50', ''], sale_output(100, 20, 0, 'receipt.invalid', ''), 2),
        ('sale-overpayment-atomic', ['sale', '10000', '2000', '12001'], sale_output(10000, 2000, 0, 'receipt.invalid'), 2),
        ('sale-negative-payment-atomic', ['sale', '100', '20', '-1'], sale_output(100, 20, 0, 'receipt.invalid'), 2),
        ('sale-zero-net', ['sale', '0', '20', '0'], 'error=invoice.invalid\n', 2),
        ('sale-negative-net', ['sale', '-1', '20', '0'], 'error=invoice.invalid\n', 2),
        ('sale-negative-tax', ['sale', '100', '-1', '0'], 'error=invoice.invalid\n', 2),
        ('sale-limit', ['sale', '1000000000', '0', '1000000000'], sale_output(1000000000, 0, 1000000000), 0),
        ('sale-gross-overflow', ['sale', '1000000000', '1', '0'], 'error=amount.overflow\n', 2),
        ('stock-partial', ['stock', '10', '125', '4'], 'shipment=ok cost=500\nquantity=6 value=750 cost_of_sales=500 entries=2\nbalanced=1 reconciled=1\n', 0),
        ('stock-full', ['stock', '10', '125', '10'], 'shipment=ok cost=1250\nquantity=0 value=0 cost_of_sales=1250 entries=2\nbalanced=1 reconciled=1\n', 0),
        ('stock-overship-atomic', ['stock', '10', '125', '11'], 'shipment=stock.unavailable\nquantity=10 value=1250 cost_of_sales=0 entries=1\nbalanced=1 reconciled=1\n', 2),
        ('stock-zero-receipt', ['stock', '0', '125', '1'], 'error=stock.receipt\n', 2),
        ('stock-negative-cost', ['stock', '10', '-1', '1'], 'error=stock.receipt\n', 2),
        ('stock-cost-overflow', ['stock', '2', '1000000000', '1'], 'error=amount.overflow\n', 2),
        ('allocation-remainder', ['allocate', '100', '3'], 'allocation total=100 parts=3\nshare[0]=34\nshare[1]=33\nshare[2]=33\nsum=100\n', 0),
        ('allocation-negative', ['allocate', '-100', '3'], 'allocation total=-100 parts=3\nshare[0]=-34\nshare[1]=-33\nshare[2]=-33\nsum=-100\n', 0),
        ('allocation-zero', ['allocate', '0', '2'], 'allocation total=0 parts=2\nshare[0]=0\nshare[1]=0\nsum=0\n', 0),
        ('allocation-small', ['allocate', '1', '3'], 'allocation total=1 parts=3\nshare[0]=1\nshare[1]=0\nshare[2]=0\nsum=1\n', 0),
        ('allocation-zero-parts', ['allocate', '100', '0'], 'error=allocation.range\n', 2),
        ('allocation-too-many-parts', ['allocate', '100', '10001'], 'error=allocation.range\n', 2),
        ('allocation-negative-limit', ['allocate', '-1000000000', '1'], 'allocation total=-1000000000 parts=1\nshare[0]=-1000000000\nsum=-1000000000\n', 0),
    ]
    for label, value in [('empty', ''), ('spaces', ' 100'), ('trailing-space', '100 '),
                         ('decimal', '1.25'), ('exponent', '1e3'), ('junk', '100x'),
                         ('plus', '+1'), ('bare-minus', '-'), ('unicode-digit', '١'),
                         ('overflow', '1000000001'), ('long', '9999999999999999999999')]:
        rows.append(('integer-' + label, ['sale', value, '0', '0'], 'error=argument.integer\n', 64))
    # Check every numeric input position, not only the first argument.
    rows.extend([
        ('bad-tax', ['sale', '100', 'bad', '0'], 'error=argument.integer\n', 64),
        ('bad-payment', ['sale', '100', '0', 'bad'], 'error=argument.integer\n', 64),
        ('bad-stock-cost', ['stock', '10', 'bad', '1'], 'error=argument.integer\n', 64),
        ('bad-stock-issue', ['stock', '10', '100', 'bad'], 'error=argument.integer\n', 64),
        ('bad-allocation-parts', ['allocate', '100', 'bad'], 'error=argument.integer\n', 64),
    ])
    return rows


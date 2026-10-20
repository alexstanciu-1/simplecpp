"""Rebuild a planning inventory from contracts and dated proofs; executes no compiler tests."""
import csv
import hashlib
import itertools
import json
from collections import Counter
from pathlib import Path

OUT = Path(__file__).resolve().parent
ROOT = OUT.parents[4]
PAYLOADS = ('int', 'float', 'bool', 'string')
FAMILIES = ('plain', 'nullable', 'result', 'result_or_false', 'result_or_bool')
TYPES = [(p, f) for p in PAYLOADS for f in FAMILIES]
EQ = ('==', '!=', '===', '!==')
ORDER = ('<', '<=', '>', '>=')
LOGIC = ('&&', '||', 'and', 'or', 'xor')
ARITH = ('+', '-', '*', '/')
BITS = ('%', '&', '|', '^', '<<', '>>')
BINARY = ARITH + BITS + ('**',) + ORDER + EQ + LOGIC + ('<=>', '.')
COMPOUND = tuple(op + '=' for op in ARITH + BITS + ('**', '.'))
UNARY = ('+', '-', '~', '!', '++prefix', '++postfix', '--prefix', '--postfix')

def source(t):
    p, f = t
    return p if f == 'plain' else '?' + p if f == 'nullable' else f + '<' + p + '>'

def runtime(t):
    p, f = t
    return p + '_t' if f == 'plain' else f + '<' + p + '_t>'

# Group metadata is deliberately separate from signature observations.
GROUPS = {
 'done': ('verified', 'Completed bounded slices', 'language_repair_progress.md; s2s_scalar_operators.md'),
 'E1': ('verified', 'Nullable float equality/identity', 'nullable_float_equality.md; nullable_float_equality_results.json'),
 'E2R': ('verified', 'Structured-error Result matching-payload equality/identity', 'result_equality.md; result_equality_results.json'),
 'E2FB': ('verified', 'False-sentinel bool equality/identity', 'false_bool_equality.md; false_bool_equality_results.json'),
 'E2BB': ('verified', 'Two-sentinel bool equality/identity', 'bool_sentinel_equality.md; bool_sentinel_equality_results.json'),
 'E2NB': ('verified', 'Non-bool sentinel same-carrier equality and carrier/plain strict identity', 'nonbool_sentinel_equality.md; nonbool_sentinel_equality_results.json'),
 'E2': ('verified', 'Sentinel/plain non-bool ordinary equality', 'sentinel_plain_equality.md; sentinel_plain_equality_results.json'),
 'E3I': ('verified', 'Cross-family matching-payload strict identity', 'cross_identity.md; cross_identity_results.json'),
 'E3': ('needs-design', 'Cross-family matching-payload ordinary equality', 'runtime/specs/spec.md sections 6.10, 7a; distinguish equality from identity normalization'),
 'E4I': ('verified', 'Mixed int/float strict identity', 'mixed_identity.md; mixed_identity_results.json'),
 'E4NR': ('verified', 'Nullable/structured Result mixed int/float ordinary equality', 'mixed_equality.md; mixed_equality_results.json'),
 'E4S': ('verified', 'Sentinel-carrier mixed int/float ordinary equality', 'sentinel_mixed_equality.md; sentinel_mixed_equality_results.json'),
 'E4': ('needs-design', 'Cross-family mixed int/float ordinary equality', 'runtime/specs/spec.md section 7a; mixed equality/promotion and NaN decisions'),
 'E5BNI': ('verified', 'Bool/numeric strict identity', 'bool_numeric_identity.md; bool_numeric_identity_results.json'),
 'E5SI': ('verified', 'Heterogeneous string strict identity', 'string_other_identity.md; string_other_identity_results.json'),
 'E5': ('needs-design', 'Other heterogeneous ordinary equality, including bool sentinels', 'runtime/specs/spec.md sections 6.10, 7a; payload/sentinel distinctions'),
 'U1': ('verified', 'Numeric carrier prefix/postfix updates', 'carrier_updates.md; carrier_updates_results.json; V-R10 repaired'),
 'C1IA': ('verified', 'Canonical-int arithmetic compounds, same carrier/plain', 'int_carrier_compounds.md; int_carrier_compounds_results.json'),
 'C1IB': ('verified', 'Canonical-int remainder/bitwise/shift compounds, same carrier/plain', 'int_bitwise_compounds.md; int_bitwise_compounds_results.json'),
 'C1IX': ('verified', 'Canonical-int cross-family compounds', 'int_cross_compounds.md; int_cross_compounds_results.json'),
 'C1': ('verified', 'Exact-float compound updates', 'float_compounds.md; float_compounds_results.json'),
 'C2W': ('verified', 'Float-destination mixed compounds', 'widening_compounds.md; widening_compounds_results.json; specs/expression_evaluation.md'),
 'C2': ('needs-design', 'Int-destination mixed compound updates', 'specs/expression_evaluation.md compound updates; conversion/write-back policy'),
 'C3': ('needs-design', 'Canonical-int power assignment', 'specs/integer_power.md explicitly leaves **= outside contract'),
 'O1': ('verified', 'Mixed numeric ordering with int-to-float conversion', 'mixed_ordering.md; mixed_ordering_results.json; specs/expression_evaluation.md'),
 'O2': ('needs-design', 'Float or mixed numeric spaceship', 's2s_scalar_operators.md: bounded spaceship gate; unordered NaN policy'),
 'S1': ('needs-design', 'String concatenation / concatenation assignment', 'specs/expression_evaluation.md; nullable string conversion exception needs separate review'),
 'P1': ('separate-existing-path', 'Literal null comparison/presence', 'nullable presence and boxed comparison owners; not all cells freshly verified'),
 'P2': ('separate-existing-path', 'Nullable string condition negation', 's2s_scalar_operators.md preserved nullable-string ! behavior'),
 'X1': ('scope-exclusion', 'Bool/numeric logical combinations', 'numeric_logical_l05.md; numeric_xor.md: explicit bounded exclusion, not a permanent language ban'),
 'X2': ('scope-exclusion', 'String logical operators', 'guarded_string.md: no guarded string logical admission in approved slice'),
 'X3': ('scope-exclusion', 'Noncanonical-int power payload', 'specs/integer_power.md: other payloads need separately agreed operation'),
 'X4': ('scope-exclusion', 'Other payload/operator combinations', 'No approved V-L07 payload contract; do not infer an accidental regression from rejection'),
}
rows = []
def add(kind, a, op, b, group):
    status, _, _ = GROUPS[group]
    rows.append(dict(signature_id='|'.join((kind, source(a), op, source(b) if b else '')),
                     kind=kind, lhs=source(a), operator=op, rhs=source(b) if b else '',
                     group=group, status=status, matrix_files=''))

def classify(a, op, b, compound=False):
    p, f = a; q, g = b
    same = p == q; numeric = p in ('int', 'float') and q in ('int', 'float')
    if compound:
        if p == q == 'int' and op[:-1] in ARITH and (f == g or f == 'plain' or g == 'plain'): return 'C1IA'
        if p == q == 'int' and op[:-1] in BITS and (f == g or f == 'plain' or g == 'plain'): return 'C1IB'
        if p == q == 'int' and op[:-1] in ARITH + BITS: return 'C1IX'
        if op == '.=' and p == q == 'string': return 'S1'
        if op == '**=': return 'C3' if p == q == 'int' else 'X3'
        if same and (p == 'int' and op[:-1] in ARITH + BITS or p == 'float' and op[:-1] in ARITH): return 'C1'
        if numeric and not same and op[:-1] in ARITH: return 'C2W' if p == 'float' else 'C2'
        return 'X4'
    if op in EQ:
        if same:
            if f in ('plain', 'nullable') and g in ('plain', 'nullable'):
                return 'E1' if p == 'float' else 'done'
            if f == g or f == 'plain' or g == 'plain':
                if p == 'bool' and f in ('plain','result_or_false') and g in ('plain','result_or_false'): return 'E2FB'
                if p == 'bool' and f in ('plain','result_or_bool') and g in ('plain','result_or_bool'): return 'E2BB'
                if p in ('int','float','string') and (f in ('result_or_false','result_or_bool') or g in ('result_or_false','result_or_bool')) and (f == g or op in ('===','!==')): return 'E2NB'
                return 'E2R' if f in ('plain','result') and g in ('plain','result') else 'E2'
            return 'E3I' if op in ('===','!==') else 'E3'
        if numeric:
            if op in ('===','!=='): return 'E4I'
            if any(f in ('plain', family) and g in ('plain', family) for family in ('nullable','result')): return 'E4NR'
            if any(f in ('plain', family) and g in ('plain', family) for family in ('result_or_false','result_or_bool')): return 'E4S'
            return 'E4'
        if op in ('===','!==') and ((p == 'bool' and q in ('int','float')) or (q == 'bool' and p in ('int','float'))): return 'E5BNI'
        if op in ('===','!==') and 'string' in (p,q): return 'E5SI'
        return 'E5'
    if op in LOGIC:
        if numeric or p == q == 'bool': return 'done'
        if 'string' in (p, q): return 'X2'
        return 'X1'
    if op == '**': return 'done' if p == q == 'int' else 'X3'
    if op in ARITH: return 'done' if numeric else 'X4'
    if op in BITS: return 'done' if p == q == 'int' else 'X4'
    if op in ORDER:
        if same and p in ('int', 'float', 'string'): return 'done'
        return 'O1' if numeric else 'X4'
    if op == '<=>':
        if same and p in ('int', 'string'): return 'done'
        return 'O2' if numeric else 'X4'
    return 'S1' if p == q == 'string' else 'X4'

for a, b in itertools.product(TYPES, repeat=2):
    if a[1] == b[1] == 'plain': continue
    for op in BINARY: add('binary', a, op, b, classify(a, op, b))
    for op in COMPOUND: add('compound', a, op, b, classify(a, op, b, True))
for a in TYPES:
    p, f = a
    if f == 'plain': continue
    for op in UNARY:
        if op in ('++prefix','++postfix','--prefix','--postfix'):
            group = 'U1' if p in ('int', 'float') else 'X4'
        elif op in {'int':('+','-','~','!'), 'float':('+','-','!'), 'bool':('!',), 'string':()}[p]: group = 'done'
        else: group = 'P2' if a == ('string','nullable') and op == '!' else 'X4'
        add('unary', a, op, None, group)
    for op in EQ:
        add('binary', a, op, ('null','plain'), 'P1')
        add('binary', ('null','plain'), op, a, 'P1')

# Reconcile every matrix wrapper operator definition, including separately owned selectors.
items = {'add':'+','subtract':'-','multiply':'*','divide':'/','modulo':'%',
         'equal':'==','not_equal':'!=','identical':'===','not_identical':'!==',
         'less_than':'<','less_than_or_equal':'<=','greater_than':'>','greater_than_or_equal':'>=',
         'logical_and':'&&','logical_or':'||','logical_not':'!', 'unary_plus':'+','unary_minus':'-',
         'bitwise_not':'~','bitwise_and':'&','bitwise_or':'|','bitwise_xor':'^','shift_left':'<<','shift_right':'>>',
         'pre_increment':'++prefix','post_increment':'++postfix','pre_decrement':'--prefix','post_decrement':'--postfix'}
items.update({k+'_assign':v+'=' for k,v in list(items.items()) if k in ('add','subtract','multiply','divide','modulo','bitwise_and','bitwise_or','bitwise_xor','shift_left','shift_right')})
type_map = {runtime(t):source(t) for t in TYPES}
lookup = {(r['kind'],r['lhs'],r['operator'],r['rhs']):r for r in rows}
index_path = ROOT/'specs/operator_matrix/data/semantics.index.json'
matrix = []; inputs = {str(index_path.relative_to(ROOT)): hashlib.sha256(index_path.read_bytes()).hexdigest()}
for d in json.loads(index_path.read_text())['definitions']:
    if not d['family_id'].startswith('operators_'): continue
    if not any(any(w in d.get(k,'') for w in ('nullable<','result<','result_or_')) for k in ('lhs_type','rhs_type')): continue
    if d['family_id'] == 'operators_conditional_selection':
        classification = 'separate-selection-owner'; signature = ''
    else:
        kind = 'compound' if d['family_id']=='operators_compound_assignment' else 'unary' if d['family_id']=='operators_unary' else 'binary'
        key = (kind,type_map[d['lhs_type']],items[d['item_id']],type_map.get(d.get('rhs_type'),''))
        row = lookup[key]; classification = row['group']; signature = row['signature_id']
        row['matrix_files'] += (';' if row['matrix_files'] else '') + d['row_file']
    path = ROOT/'specs/operator_matrix/data'/d['row_file']
    inputs[str(path.relative_to(ROOT))] = hashlib.sha256(path.read_bytes()).hexdigest()
    with path.open() as stream: states = Counter(x['status'] for x in csv.DictReader(stream,delimiter='\t'))
    matrix.append(dict(d, inventory_group=classification, signature_id=signature, semantic_row_statuses=dict(states)))
assert len(rows) == len(lookup)
done = Counter(r['kind'] for r in rows if r['status']=='verified')
assert done == {'binary':2592,'unary':64,'compound':432}, done
assert len(matrix)==376
with (OUT/'signatures.csv').open('w') as stream:
    writer=csv.DictWriter(stream,fieldnames=list(rows[0]));writer.writeheader();writer.writerows(rows)
remaining = [r for r in rows if r['status'] in ('remaining-support','needs-design')]
with (OUT/'remaining.csv').open('w') as stream:
    writer=csv.DictWriter(stream,fieldnames=list(rows[0]));writer.writeheader();writer.writerows(remaining)
counts=Counter(r['group'] for r in rows)
for name in ['runtime/specs/spec.md', 'runtime/specs/config.json', 'specs/expression_evaluation.md', 'specs/integer_power.md', 'compiler/my-try/04_analyze/operators/guarded.php', 'compiler/my-try/04_analyze/operators/wrapper_equality.php', 'compiler/my-try/docs/planning/nullable_bool_string_equality_results.json', 'compiler/my-try/docs/planning/nullable_float_equality_results.json', 'compiler/my-try/docs/planning/result_equality_results.json', 'compiler/my-try/docs/planning/false_bool_equality_results.json', 'compiler/my-try/docs/planning/bool_sentinel_equality_results.json', 'compiler/my-try/docs/planning/nonbool_sentinel_equality_results.json', 'compiler/my-try/docs/planning/sentinel_plain_equality_results.json', 'compiler/my-try/docs/planning/carrier_updates_results.json', 'compiler/my-try/docs/planning/int_carrier_compounds_results.json', 'compiler/my-try/docs/planning/int_bitwise_compounds_results.json', 'compiler/my-try/docs/planning/int_cross_compounds_results.json', 'compiler/my-try/docs/planning/float_compounds_results.json', 'compiler/my-try/docs/planning/mixed_ordering_results.json', 'compiler/my-try/docs/planning/widening_compounds_results.json', 'compiler/my-try/docs/planning/cross_identity_results.json', 'compiler/my-try/docs/planning/mixed_identity_results.json', 'compiler/my-try/docs/planning/bool_numeric_identity_results.json', 'compiler/my-try/docs/planning/string_other_identity_results.json', 'compiler/my-try/docs/planning/mixed_equality_results.json', 'compiler/my-try/docs/planning/sentinel_mixed_equality_results.json']:
    inputs[name] = hashlib.sha256((ROOT/name).read_bytes()).hexdigest()
summary={'doc_status':'planning','date':'2026-10-20','method':'Static inventory, no compiler/runtime execution; statuses are planning dispositions, not fresh admission results.',
         'canonical_payloads':PAYLOADS,'families':FAMILIES,'rows':len(rows),'status_counts':dict(Counter(r['status'] for r in rows)),
         'groups':{k:dict(status=v[0],title=v[1],authority_or_evidence=v[2],signatures=counts[k]) for k,v in GROUPS.items()},
         'verified':dict(done),'matrix_wrapper_definitions':len(matrix),'matrix_separate_selectors':144,
         'remaining_list_rows':len(remaining),'input_hashes':inputs}
(OUT/'summary.json').write_text(json.dumps(summary,indent=2)+'\n')
(OUT/'matrix_reconciliation.json').write_text(json.dumps(matrix,indent=2)+'\n')
print(json.dumps({k:summary[k] for k in ('rows','status_counts','verified','remaining_list_rows')},indent=2))
for k,c in counts.items(): print(k,c,GROUPS[k][1])

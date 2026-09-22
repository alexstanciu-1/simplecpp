"""Source capability states and complete operation matching; fixed protocol expectations."""
def build():
    cases=[]
    for role in range(1,7):
        for state in range(3):
            for kind in range(6):
                for imported in ([False] if kind==0 else [False,True]):
                    implemented=0 if role in [4,6] else role
                    accept=((state==0)==(kind!=0)) and (kind==0 or (not imported and kind==implemented))
                    cases.append(dict(mode='cap',role=role,state=state,kind=kind,imported=imported,accept=accept))
    rows=[
        [1,'default_construct','uninitialized_aligned','live_owned','none','none','disjoint',1],
        [3,'copy_construct','uninitialized_aligned','live_owned','const_live','live_preserved','disjoint',3],
        [4,'move_construct','uninitialized_aligned','live_owned','mutable_live','requires_move_contract','disjoint',0],
        [5,'copy_assign','live','live_owned','const_live','live_preserved','self_assignment_or_disjoint',5],
        [6,'move_assign','live','live_owned','mutable_live','requires_move_contract','self_assignment_or_disjoint',0],
        [2,'destroy','live','dead','none','none','exclusive_destination',2]]
    for row in rows:cases.append(dict(mode='semantics',role=row[0],name=row[1],before=row[2],after=row[3],access=row[4],source_after=row[5],aliasing=row[6],kind=row[7],accept=True))
    return cases

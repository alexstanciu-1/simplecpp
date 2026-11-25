"""Only changes to record allocation/map order are cache-equivalent; accepted owners are nominal."""
def build():
    names=['rebuilt references','directory','manifest bytes','catalog identity','type name','type key','type added',
           'callable name','callable key','callable added','native provider','native id','native target','native layout',
           'native owner identity','native key','source export identity','source key','receipt bytes','project export identity',
           'project export key','null current bindings','null previous bindings','both null bindings',
           'null current project','null previous project','both null projects','map order','empty current imports',
           'empty current sources','empty project exports','same binding object','same project object']
    return [dict(mode=i,name=name,accept=i in [0,23,26,27,31,32]) for i,name in enumerate(names)]

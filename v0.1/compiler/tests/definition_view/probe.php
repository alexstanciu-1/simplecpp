<?php
declare(strict_types=1);
namespace view_test;
final class Probe {
    private static function check(bool $ok): void { echo $ok ? "true\n" : "false\n"; }
    private static function definition(string $name, string $namespace_name): \type_model\Named_Definition {
        $p=new \type_model\Lifetime_Policy(); $p->copy=1; $p->construction=1; $p->assignment=1; $p->expiring=1;
        $ops /** vector<\type_model\Lifecycle_Operation> */ = [];
        return new \type_model\Named_Definition($name,$namespace_name,\type_model\Representation::integer(32),new \type_model\Lifetime_Contract($p,$ops),true,'',false,false,true);
    }
    public static function run(): void {
        $provided=Probe::definition('word',''); $rows /** vector<\type_model\Named_Definition> */ = [$provided];
        $catalog=new \type_model\Type_Catalog('provider','catalog-key','language_values',$rows,$provided,$provided,null);
        $types=\type_model\Type_Store::fresh(new \type_model\Type_Context('config','provider','target'));
        $source=Probe::definition('Source',''); $source_id=\resolve_types\Type_Cache::materialize($types,$source);
        $collision=Probe::definition('word',''); \resolve_types\Type_Cache::materialize($types,$collision);
        $qualified=Probe::definition('word','local'); \resolve_types\Type_Cache::materialize($types,$qualified);
        $view=new \resolve_types\Definition_View($catalog,$types);
        Probe::check($view->content_key()==='catalog-key'); Probe::check($view->representation_scope()==='language_values');
        Probe::check($view->entry_return_type()===$provided);
        Probe::check($view->find_type('word','')===$provided); Probe::check($view->find_type('word','local')===$qualified);
        Probe::check($view->find_type('Source','')===$source); Probe::check($view->find_type('missing','')===null);
        Probe::check($view->find_type('Source','other')===null);
        $candidate=$types->fork(); $candidate->invalidate_definition($source_id); $replacement=Probe::definition('Source','');
        \resolve_types\Type_Cache::materialize($candidate,$replacement); $new_view=new \resolve_types\Definition_View($catalog,$candidate);
        Probe::check($view->find_type('Source','')===$source); Probe::check($new_view->find_type('Source','')===$replacement);
        Probe::check($new_view->find_type('word','')===$provided); Probe::check($catalog->size()===1);
        $candidate->reference_type('pending',''); $failed=false;
        try { $new_view->find_type('pending',''); } catch (\LogicException $error) { $failed=true; } Probe::check($failed);
        Probe::check($view->find_type('pending','')===null);
    }
}

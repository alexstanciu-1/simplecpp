<?php
declare(strict_types=1);
namespace runtime_lease_test;
final class Probe {
    private static function check(bool $value): void { echo $value ? "true\n" : "false\n"; }
    private static function contender(string $path, bool $shared): int {
        $mode = $shared ? '1' : '2';
        $script = "import fcntl,sys\nf=open(sys.argv[1],'r')\ntry: fcntl.flock(f," . $mode . "|fcntl.LOCK_NB)\nexcept BlockingIOError: sys.exit(2)\n";
        $arguments /** vector<string> */ = ['-c',$script,$path];
        $process = process_spawn('/usr/bin/python3',$arguments,'',3000,'');
        while (!process_poll($process)) {}
        $result = process_output($process); process_close($process);
        return $result->exit_code;
    }
    private static function package(): \load_runtime\Runtime_Package {
        $policy = new \type_model\Lifetime_Policy();
        $operations /** vector<\type_model\Lifecycle_Operation> */ = [];
        $life = new \type_model\Lifetime_Contract($policy,$operations);
        $definition = new \type_model\Named_Definition('I','',\type_model\Representation::integer(64),$life,true,'',false,false,false);
        $definitions /** vector<\type_model\Named_Definition> */ = [$definition];
        $catalog = new \type_model\Type_Catalog('p','key','language_values',$definitions,$definition,$definition,null);
        $types /** hash<\load_runtime\Runtime_Type> */ = []; $callables /** vector<\type_model\Runtime_Callable> */ = [];
        $modules /** hash<string> */ = []; $paths /** vector<string> */ = []; $arguments /** vector<string> */ = [];
        $families /** hash<\type_model\Storage_Family> */ = []; $imports /** hash<\prepare_backend\Source_Operation_Export> */ = [];
        return new \load_runtime\Runtime_Package('p','/provider','t','d','clang',$arguments,$types,$callables,$modules,$paths,'manifest',$catalog,$catalog,$families,null,null,$imports);
    }
    private static function scoped(string $path, bool $failure): void {
        $reservation = new \scpp\Lock_Reservation();
        if (!$reservation->acquire($path,true)) { throw new \RuntimeException('Unexpected contention'); }
        $lease = new \load_runtime\Runtime_Lease($reservation,Probe::package());
        Probe::check(Probe::contender($path,false) === 2);
        if ($failure) { throw new \RuntimeException('Simulated validation failure'); }
    }
    private static function before_transfer(string $path): void {
        $reservation = new \scpp\Lock_Reservation();
        if (!$reservation->acquire($path,true)) { throw new \RuntimeException('Unexpected contention'); }
        throw new \RuntimeException('Simulated package rejection');
    }
    public static function run(string $path): void {
        $reservation = new \scpp\Lock_Reservation(); Probe::check(!$reservation->active());
        Probe::check($reservation->acquire($path,true));
        $alias = $reservation; $package = Probe::package();
        $lease = new \load_runtime\Runtime_Lease($reservation,$package); $lease_alias = $lease;
        Probe::check(!$alias->active() && $lease->active() && ($lease->package === $package));
        Probe::check(Probe::contender($path,false) === 2); Probe::check(Probe::contender($path,true) === 0);
        $alias->release(); Probe::check(Probe::contender($path,false) === 2);
        $bad = false; try { $moved = $alias->transfer(); } catch (\RuntimeException $error) { $bad = true; } Probe::check($bad);
        $lease_alias->release(); $lease->release(); Probe::check(!$lease->active());
        Probe::check(Probe::contender($path,false) === 0);
        Probe::check($reservation->acquire($path,false));
        $other = new \scpp\Lock_Reservation(); Probe::check(!$other->acquire($path,true) && !$other->active());
        $again = false; try { $reservation->acquire($path,false); } catch (\RuntimeException $error) { $again = true; } Probe::check($again);
        $reservation->release();
        Probe::scoped($path,false); Probe::check(Probe::contender($path,false) === 0);
        try { Probe::scoped($path,true); } catch (\RuntimeException $error) {}
        Probe::check(Probe::contender($path,false) === 0);
        try { Probe::before_transfer($path); } catch (\RuntimeException $error) {}
        Probe::check(Probe::contender($path,false) === 0);
        $empty = new \scpp\Lock_Reservation(); $rejected = false;
        try { $invalid = new \load_runtime\Runtime_Lease($empty,$package); } catch (\RuntimeException $error) { $rejected = true; }
        Probe::check($rejected);
    }
}

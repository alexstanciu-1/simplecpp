<?php
declare(strict_types=1);
namespace resolve_types;
/** Source symbol IDs; zero means no user body. The composition owner only reads this input. */
/** @scpp-struct */
final class Lifecycle_Bodies {
    public int $constructor /** uint32 */ = 0;
    public int $destructor /** uint32 */ = 0;
    public int $copy /** uint32 */ = 0;
    public int $assignment /** uint32 */ = 0;
}

<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Command\Traits;

// describes why command has been locked up
trait ErrorTrait
{
    public function __toError()
    {
        $missing = [];
        if (property_exists($this, 'waitScope')) {
            foreach ($this->waitScope ?? [] as $name => $n) {
                $missing[] = "scope:{$name}";
            }
        }

        if (property_exists($this, 'waitContext')) {
            foreach ($this->waitContext ?? [] as $name => $n) {
                $missing[] = "{$name}";
            }
        }

        return sprintf("%s(%s)", get_class($this), join(", ", $missing));
    }
}
<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Select;

interface SourceFactoryInterface
{
    /**
     * Get database source associated with given entity role.
     *
     * @param string $role
     * @return SourceInterface
     */
    public function getSource(string $role): SourceInterface;
}
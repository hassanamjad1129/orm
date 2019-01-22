<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Relation;

use Spiral\Cycle\Command\CommandInterface;
use Spiral\Cycle\Command\ContextCarrierInterface as CC;
use Spiral\Cycle\Exception\RelationException;
use Spiral\Cycle\Heap\Node;

/**
 * Manages single branch type between parent entity and other objects.
 */
interface RelationInterface
{
    /**
     * Relation name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Must return true to trigger queue.
     *
     * @return bool
     */
    public function isCascade(): bool;

    /**
     * Init related entity value(s). Returns tuple [value, value to store as relation context].
     *
     * @param array $data
     * @return array
     *
     * @throws RelationException
     */
    public function init(array $data): array;

    /**
     * Extract the related values from the entity field value.
     *
     * @param mixed $value
     * @return mixed
     *
     * @throws RelationException
     */
    public function extract($value);

    /**
     * Returns tuple of [promise to insert into entity, promise to store as relation context].
     *
     * @param Node $parentNode
     * @return array
     *
     * @throws RelationException
     */
    public function initPromise(Node $parentNode): array;

    /**
     * Create branch of operations required to store the relation.
     *
     * @param CC     $parentStore
     * @param object $parentEntity
     * @param Node   $parentNode
     * @param object $related
     * @param object $original
     * @return CommandInterface
     *
     * @throws RelationException
     */
    public function queue(CC $parentStore, $parentEntity, Node $parentNode, $related, $original): CommandInterface;
}
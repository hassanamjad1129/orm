<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\ORM\Command\Branch;

use Spiral\ORM\Command\CarrierInterface;

/**
 * Doing noting.
 *
 * @codeCoverageIgnore
 */
final class Nil implements CarrierInterface
{
    /**
     * {@inheritdoc}
     */
    public function waitContext(string $key, bool $required = true)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function getContext(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function push(string $key, $value, bool $update = false, int $stream = self::DATA)
    {
        // nothing to do
    }

    /**
     * @inheritdoc
     */
    public function isExecuted(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isReady(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        // nothing to do
    }

    /**
     * {@inheritdoc}
     */
    public function complete()
    {
        // nothing to do
    }

    /**
     * {@inheritdoc}
     */
    public function rollBack()
    {
        // nothing to do
    }
}
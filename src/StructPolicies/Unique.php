<?php
declare(strict_types=1);
namespace Soatok\AnthroKit\StructPolicies;

/**
 * Interface Unique
 * @package Soatok\Website\Engine\Policies
 */
interface Unique
{
    /**
     * @param int $id
     * @return string
     * @throws \Error
     */
    public function getCacheKey(int $id = 0): string;
}

<?php declare(strict_types=1);

namespace App\ApiPlatform\Doctrine\Common\Filter;

/**
 * All strategies are case-insensitive
 */
interface TextFilterInterface
{
    public const STRATEGY_EXACT = 'exact';

    public const STRATEGY_PARTIAL = 'partial';
}

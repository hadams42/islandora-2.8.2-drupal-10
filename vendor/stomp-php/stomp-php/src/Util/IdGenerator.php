<?php
/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Util;

use RuntimeException;

/**
 * IdGenerator generates Ids which are unique during the runtime scope.
 *
 * @package Stomp\Util
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class IdGenerator
{
    /**
     * @var array
     */
    private static $generatedIds = [];

    /**
     * Generate a not used id.
     *
     * @return int
     */
    public static function generateId()
    {
        while ($rand = rand(1, PHP_INT_MAX)) {
            if (!in_array($rand, self::$generatedIds, true)) {
                self::$generatedIds[] = $rand;
                return $rand;
            }
        }
        // This is never hit because the above becomes an infinite loop. Possibly need a release valve.
        // throw new RuntimeException('Message Id generation failed.');
    }

    /**
     * Removes a previous generated id from currently used ids.
     *
     * @param int $generatedId
     */
    public static function releaseId($generatedId)
    {
        $index = array_search($generatedId, self::$generatedIds, true);
        if ($index !== false) {
            unset(self::$generatedIds[$index]);
        }
    }
}

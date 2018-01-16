<?php
/**
 * @copyright 2017 Kullar Kert
 * @license   https://opensource.org/licenses/MIT MIT
 */

namespace kullar84\MobileID;

use \Illuminate\Support\Facades\Facade;

/**
 * MobileIDFacade class for dealing Laravel Facade
 *
 * @author        Kullar Kert <kullar.kert@gmail.com>
 * @license       https://opensource.org/licenses/MIT MIT
 * @package       kullar84\MobileID
 * @copyright     2017 Kullar Kert
 */
class MobileIDFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'MobileID';
    }
}
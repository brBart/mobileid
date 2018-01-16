<?php
/**
 * @copyright 2017 Kullar Kert
 * @license   https://opensource.org/licenses/MIT MIT
 */

namespace kullar84\MobileID;

use Exception;

/**
 * MobileIDException class for dealing Mobile ID exceptions
 *
 * @author        Kullar Kert <kullar.kert@gmail.com>
 * @license       https://opensource.org/licenses/MIT MIT
 * @package       kullar84\MobileID
 * @copyright     2017 Kullar Kert
 */
class MobileIDException extends Exception
{

    /**
     * Constructor.
     *
     * @param string     $message  error message
     * @param integer    $code     error code
     * @param \Throwable $previous The previous exception used for the exception chaining.
     */
    public function __construct($message = null, $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
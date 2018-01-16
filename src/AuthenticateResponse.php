<?php
/**
 * @copyright 2017 Kullar Kert
 * @license   https://opensource.org/licenses/MIT MIT
 */

namespace kullar84\MobileID;

/**
 * Mobile ID authentication response
 *
 * @author    Kullar Kert <kullar.kert@gmail.com>
 * @license   https://opensource.org/licenses/MIT MIT
 * @package   kullar84\MobileID
 * @copyright 2017 Kullar Kert
 */
class AuthenticateResponse
{
    /**
     * Response status
     *
     * @var string
     */
    public $status = null;

    /**
     * Error message
     *
     * @var string
     */
    public $error;

    /**
     * Challenge response (verification code)
     *
     * @var string
     */
    public $challengeResponse;

    /**
     * Firstname
     *
     * @var string
     */
    public $firstName;

    /**
     * Lastname
     *
     * @var string
     */
    public $lastName;


    /**
     * ID code
     *
     * @var string
     */
    public $idCode;

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Is status pending?
     *
     * @return bool
     */
    public function isPending()
    {
        return ($this->status === 'pending' ? true : false);
    }

    /**
     * Was there a success?
     *
     * @return bool
     */
    public function isSuccess()
    {
        return ($this->status === 'success' ? true : false);
    }

    /**
     * Was there an error?
     *
     * @return bool
     */
    public function isError()
    {
        return ($this->status === 'error' ? true : false);
    }

    /**
     * Get challange response
     *
     * @return string
     */
    public function getChallengeResponse()
    {
        return $this->challengeResponse;
    }


    /**
     * Get firstname
     *
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * Get lastname
     *
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * Get ID code
     *
     * @return string
     */
    public function getIDCode()
    {
        return $this->idCode;
    }
}
<?php
/**
 * Customer
 *
 * @category  Credova
 * @package   Credova_Payments
 * @author    Credova <info@credova.com>
 * @copyright 2024 Credova
 * @license   http://opensource.org/licenses/osl-3.0.php (OSL 3.0)
 * @link      https://credova.com/
 */

namespace Credova\Payments\Model\Data;

use Credova\Payments\Api\Data\CustomerInterface;

class Customer extends \Magento\Framework\Api\AbstractExtensibleObject implements CustomerInterface
{
    /**
     * @return string|null
     */
    public function getPublicId()
    {
        return $this->_get(self::PUBLIC_ID);
    }//end getPublicId()

    /**
     * @param  string $publicId
     * @return $this
     */
    public function setPublicId($publicId)
    {
        return $this->setData(self::PUBLIC_ID, $publicId);
    }//end setPublicId()

    /**
     * @return string|null
     */
    public function getFirstName()
    {
        return $this->_get(self::FIRST_NAME);
    }//end getFirstName()

    /**
     * @param  string $firstName
     * @return $this
     */
    public function setFirstName($firstName)
    {
        return $this->setData(self::FIRST_NAME, $firstName);
    }//end setFirstName()

    /**
     * @return string|null
     */
    public function getLastName()
    {
        return $this->_get(self::LAST_NAME);
    }//end getLastName()

    /**
     * @param  string $lastName
     * @return $this
     */
    public function setLastName($lastName)
    {
        return $this->setData(self::LAST_NAME, $lastName);
    }//end setLastName()

    /**
     * @return string|null
     */
    public function getPhoneNumber()
    {
        return $this->_get(self::PHONE_NUMBER);
    }//end getPhoneNumber()

    /**
     * @param  string $phoneNumber
     * @return $this
     */
    public function setPhoneNumber($phoneNumber)
    {
        return $this->setData(self::PHONE_NUMBER, $phoneNumber);
    }//end setPhoneNumber()

    /**
     * @return string|null
     */
    public function getEmail()
    {
        return $this->_get(self::EMAIL);
    }//end getEmail()

    /**
     * @param  string $email
     * @return $this
     */
    public function setEmail($email)
    {
        return $this->setData(self::EMAIL, $email);
    }//end setEmail()

    /**
     * @return string|null
     */
    public function getError()
    {
        return $this->_get(self::ERROR);
    }//end getEmail()

    /**
     * @param  string $email
     * @return $this
     */
    public function setError($error)
    {
        return $this->setData(self::ERROR, $error);
    }//end setEmail()

    /**
     * @return float|null
     */
    public function getNeededAmount()
    {
        return $this->_get(self::NEEDED_AMOUNT);
    }//end getNeededAmount()
    
    /**
     * @param  float $neededAmount
     * @return $this
     */
    public function setNeededAmount($neededAmount)
    {
        return $this->setData(self::NEEDED_AMOUNT, $neededAmount);
    }//end setNeededAmount()
}//end class

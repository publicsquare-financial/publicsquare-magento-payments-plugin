<?php
/**
 * Customer
 *
 * @category  PublicSquare
 * @package   PublicSquare_Payments
 * @author    PublicSquare <info@publicsquare.com>
 * @copyright 2024 PublicSquare
 * @license   http://opensource.org/licenses/osl-3.0.php (OSL 3.0)
 * @link      https://publicsquare.com/
 */

namespace PublicSquare\Payments\Model\Data;

use PublicSquare\Payments\Api\Data\CustomerInterface;

class Customer extends \Magento\Framework\Api\AbstractExtensibleObject implements CustomerInterface
{
    /**
     * @return string|null
     */
    public function getId()
    {
        return $this->_get(self::ID);
    }//end getPublicId()

    /**
     * @param  string $publicId
     * @return $this
     */
    public function setId($id)
    {
        return $this->setData(self::ID, $id);
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
}//end class

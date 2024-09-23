<?php
/**
 * CustomerInterface
 *
 * @category  PublicSquare
 * @package   PublicSquare_Payments
 * @author    PublicSquare <info@publicsquare.com>
 * @copyright 2024 PublicSquare
 * @license   http://opensource.org/licenses/osl-3.0.php (OSL 3.0)
 * @link      https://publicsquare.com/
 */

namespace PublicSquare\Payments\Api\Data;

interface CustomerInterface extends \Magento\Framework\Api\CustomAttributesDataInterface
{
    /**
* #@+
     * Constants defined for keys of the data array. Identical to the name of the getter in snake case
     */
    const ID            = 'id';
    const FIRST_NAME    = 'first_name';
    const LAST_NAME     = 'last_name';
    const PHONE_NUMBER  = 'phone_number';
    const EMAIL         = 'email';
    const ERROR         = 'error';

    /**
     * @return string|null
     */
    public function getId();

    /**
     * @param  string $id
     * @return $this
     */
    public function setId($id);

    /**
     * @return string|null
     */
    public function getFirstName();

    /**
     * @param  string $firstName
     * @return $this
     */
    public function setFirstName($firstName);

    /**
     * @return string|null
     */
    public function getLastName();

    /**
     * @param  string $lastName
     * @return $this
     */
    public function setLastName($lastName);

    /**
     * @return string|null
     */
    public function getPhoneNumber();

    /**
     * @param  string $phoneNumber
     * @return $this
     */
    public function setPhoneNumber($phoneNumber);

    /**
     * @return string|null
     */
    public function getEmail();

    /**
     * @param  string $email
     * @return $this
     */
    public function setEmail($email);

    /**
     * @return string|null
     */
    public function getError();
    
    /**
     * @param  string $email
     * @return $this
     */
    public function setError($error);
}//end interface

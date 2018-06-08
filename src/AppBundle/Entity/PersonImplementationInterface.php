<?php


namespace AppBundle\Entity;


interface PersonImplementationInterface
{
    /**
     * @return string
     */
    public function getObjectType();

    /**
     * Get id
     *
     * @return integer
     */
    public function getId();

    /**
     * Set firstName
     *
     * @param string $firstName
     *
     * @return PersonImplementationInterface
     */
    public function setFirstName($firstName);

    /**
     * Get firstName
     *
     * @return string
     */
    public function getFirstName();

    /**
     * Set lastName
     *
     * @param string $lastName
     *
     * @return PersonImplementationInterface
     */
    public function setLastName($lastName);

    /**
     * Get lastName
     *
     * @return string
     */
    public function getLastName();

    /**
     * Set emailAddress
     *
     * @param string $emailAddress
     *
     * @return PersonImplementationInterface
     */
    public function setEmailAddress($emailAddress);

    /**
     * Get emailAddress
     *
     * @return string
     */
    public function getEmailAddress();

    /**
     * Get accessToken
     *
     * @return string
     */
    public function getAccessToken();

    /**
     * Returns the roles granted to the user.
     *
     * <code>
     * public function getRoles()
     * {
     *     return array('ROLE_USER');
     * }
     * </code>
     *
     * Alternatively, the roles might be stored on a ``roles`` property,
     * and populated in any number of different ways when the user object
     * is created.
     *
     * @return (Role|string)[] The user roles
     */
    public function getRoles();

    /**
     * Returns the username used to authenticate the user.
     *
     * @return string The username
     */
    public function getUsername();

    /**
     * Returns the full name of the user.
     *
     * @return string The username
     */
    public function getFullName();
}
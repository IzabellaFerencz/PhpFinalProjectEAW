<?php
namespace App\Tests\Entity;

use  App\Entity\UserProfile;
use PHPUnit\Framework\TestCase;

class UserProfileTest extends TestCase
{
    public function testValidProfile()
    {
        $profile = new UserProfile();
        $profile->setAddress("test address");
        $profile->setFirstname("fname");
        $profile->setLastname("lname");
        $profile->setPhonenr("0729447272");

        $result = $profile->isValid();

        $this->assertEquals(true, $result);
    }

    public function testNoAddressProfile()
    {
        $profile = new UserProfile();
        $profile->setFirstname("fname");
        $profile->setLastname("lname");
        $profile->setPhonenr("0729447272");

        $result = $profile->isValid();

        $this->assertEquals(false, $result);
    }

    public function testNoFirstnameProfile()
    {
        $profile = new UserProfile();
        $profile->setAddress("test address");
        $profile->setLastname("lname");
        $profile->setPhonenr("0729447272");

        $result = $profile->isValid();

        $this->assertEquals(false, $result);
    }

    public function testNoLastnameProfile()
    {
        $profile = new UserProfile();
        $profile->setAddress("test address");
        $profile->setFirstname("fname");
        $profile->setPhonenr("0729447272");

        $result = $profile->isValid();

        $this->assertEquals(false, $result);
    }

    public function testNoPhoneProfile()
    {
        $profile = new UserProfile();
        $profile->setAddress("test address");
        $profile->setFirstname("fname");
        $profile->setLastname("lname");

        $result = $profile->isValid();

        $this->assertEquals(false, $result);
    }
}
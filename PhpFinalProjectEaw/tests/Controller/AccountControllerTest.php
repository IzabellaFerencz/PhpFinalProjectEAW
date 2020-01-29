<?php
namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AccountControllerTest extends WebTestCase
{
    public function testLogin()
    {
        $client = static::createClient();

        $client->request('GET', '/account/login');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function testRegister()
    {
        $client = static::createClient();

        $client->request('GET', '/account/register');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
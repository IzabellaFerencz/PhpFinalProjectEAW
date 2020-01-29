<?php
namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PostControllerTest extends WebTestCase
{
    public function testGetPosts()
    {
        $client = static::createClient();

        $client->request('GET', '/post/');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function testGetPost()
    {
        $client = static::createClient();

        $client->request('GET', '/post/8');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
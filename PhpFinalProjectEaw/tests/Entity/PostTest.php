<?php
namespace App\Tests\Entity;

use  App\Entity\Post;
use PHPUnit\Framework\TestCase;

class PostTest extends TestCase
{
    public function testValidPost()
    {
        $post = new Post();
        $post->setTitle("test title");
        $post->setDescription("test description");
        $post->setPrice(20);
        $post->setStatus("Active");
        $result = $post->isValid();

        $this->assertEquals(true, $result);
    }

    public function testNoTitlePost()
    {
        $post = new Post();
        $post->setDescription("test description");
        $post->setPrice(20);
        $post->setStatus("Active");
        $result = $post->isValid();

        $this->assertEquals(false, $result);
    }

    public function testNoDescriptionPost()
    {
        $post = new Post();
        $post->setTitle("test title");
        $post->setPrice(20);
        $post->setStatus("Active");
        $result = $post->isValid();

        $this->assertEquals(false, $result);
    }

    public function testNegativePricePost()
    {
        $post = new Post();
        $post->setTitle("test title");
        $post->setDescription("test description");
        $post->setPrice(-20);
        $post->setStatus("Active");
        $result = $post->isValid();

        $this->assertEquals(false, $result);
    }

    public function testNoPricePost()
    {
        $post = new Post();
        $post->setTitle("test title");
        $post->setDescription("test description");
        $post->setStatus("Active");
        $result = $post->isValid();

        $this->assertEquals(true, $result);
        $this->assertEquals(0, $post->getPrice());
    }

    public function testNoStatusPost()
    {
        $post = new Post();
        $post->setTitle("test title");
        $post->setDescription("test description");
        $post->setPrice(20);
        $result = $post->isValid();

        $this->assertEquals(false, $result);
    }

    public function testInvalidStatusPost()
    {
        $post = new Post();
        $post->setTitle("test title");
        $post->setDescription("test description");
        $post->setPrice(20);
        $post->setStatus("TEST");
        $result = $post->isValid();

        $this->assertEquals(false, $result);
    }
}
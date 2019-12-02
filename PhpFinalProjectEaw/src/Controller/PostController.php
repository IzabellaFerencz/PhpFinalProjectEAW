<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\User;
use App\Form\PostType;
use App\Repository\PostRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/post")
 */
class PostController extends AbstractController
{
    /**
     * @Route("/", name="post_index", methods={"GET"})
     */
    public function index(PostRepository $postRepository): Response
    {
        return $this->render('post/index.html.twig', [
            'posts' => $postRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="newpost", methods={"GET"})
     */
    public function newpost()
    {
        $session = $this->get('session');
        $username = $session->get('username');
        if($username==''){
            return $this->render('account/error.html.twig', [
                'Message' => "You must be logged in to create a post!",
                ]);
        }
        return $this->render('post/newpost.html.twig', [
        ]);
    }

    /**
     * @Route("/new", name="post_new", methods={"POST"})
     */
    public function new(Request $request): Response
    {
        $session = $this->get('session');
        $username = $session->get('username');
        $user = $this->getDoctrine()->getRepository(User::class)->findOneByUsername($username);
        if($user==null){
            return $this->render('account/error.html.twig', [
                'Message' => "You must be logged in to create a post!",
                ]);
        }
        $title = $_POST["title"];
        $description = $_POST["description"];
        $status = $_POST["status"];
        try {
            $post = new Post();
            $post->setTitle($title);
            $post->setDescription($description);
            $post->setStatus($status);
            $post->setUserid($user);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($post);
            $entityManager->flush();
        } catch (\Throwable $th) {
            return $this->render('account/error.html.twig', [
                'Message' => "Something went wrong!",
                ]);
        }

        return $this->redirectToRoute('post_index');        
    }

    /**
     * @Route("/{id}", name="post_show", methods={"GET"})
     */
    public function show(Post $post): Response
    {
        return $this->render('post/show.html.twig', [
            'post' => $post,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="post_edit_get", methods={"GET"})
     */
    public function editpost($id)
    {
        $post = $this->getDoctrine()->getRepository(Post::class)->find($id);
        if($post == null)
        {
            return $this->render('account/error.html.twig', [
                'Message' => "No post was found with id=".$id,
                ]);
        }
        $session = $this->get('session');
        $username = $session->get('username');
        $user = $this->getDoctrine()->getRepository(User::class)->findOneByUsername($username);
        if($user == null || $user != $post->getUserid())
        {
            return $this->render('account/error.html.twig', [
                'Message' => "You are not authorized to edit this post!",
                ]);
        }
        return $this->render('post/edit.html.twig', [
            'post' => $post,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="post_edit", methods={"POST"})
     */
    public function edit($id): Response
    {
        $post = $this->getDoctrine()->getRepository(Post::class)->find($id);
        if($post == null)
        {
            return $this->render('account/error.html.twig', [
                'Message' => "No post was found with id=".$id,
                ]);
        }
        $session = $this->get('session');
        $username = $session->get('username');
        $user = $this->getDoctrine()->getRepository(User::class)->findOneByUsername($username);
        if($user == null || $user != $post->getUserid())
        {
            return $this->render('account/error.html.twig', [
                'Message' => "You are not authorized to edit this post!",
                ]);
        }

        $title = $_POST["title"];
        $description = $_POST["description"];
        $status = $_POST["status"];

        $post->setTitle($title);
        $post->setDescription($description);
        $post->setStatus($status);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->flush();

        return $this->render('post/show.html.twig', [
            'post' => $post,
        ]);
    }

    /**
     * @Route("/{id}", name="post_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Post $post): Response
    {
        if ($this->isCsrfTokenValid('delete'.$post->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($post);
            $entityManager->flush();
        }

        return $this->redirectToRoute('post_index');
    }
}

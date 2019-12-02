<?php

namespace App\Controller;

use App\Entity\PostRequest;
use App\Entity\Post;
use App\Entity\User;
use App\Form\PostRequestType;
use App\Repository\PostRequestRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/post/request")
 */
class PostRequestController extends AbstractController
{
    /**
     * @Route("/my_requests", name="post_request_index", methods={"GET"})
     */
    public function index()
    {
        $session = $this->get('session');
        $username = $session->get('username');
        $user = $this->getDoctrine()->getRepository(User::class)->findOneByUsername($username);
        if($user == null)
        {
            return $this->render('account/error.html.twig', [
                'Message' => "You are not authorized!",
                ]);
        }

        $repo = $this->getDoctrine()->getRepository(PostRequest::class);

        return $this->render('post_request/index.html.twig', [
            'post_requests' => $repo->findByUserId($user->getId()),
            'accept_actions'=>false,
        ]);
    }

    /**
     * @Route("/view_post_requests/{postid}", name="view_post_requests", methods={"GET"})
     */
    public function view_post_requests($postid)
    {
        $post = $this->getDoctrine()->getRepository(Post::class)->find($postid);
        if($post == null)
        {
            return $this->render('account/error.html.twig', [
                'Message' => "No post found with id=".$postid,
                ]);
        }

        $session = $this->get('session');
        $username = $session->get('username');
        $user = $this->getDoctrine()->getRepository(User::class)->findOneByUsername($username);
        if($user == null || $user != $post->getUserId())
        {
            return $this->render('account/error.html.twig', [
                'Message' => "You are not authorized!",
                ]);
        }

        $repo = $this->getDoctrine()->getRepository(PostRequest::class);

        return $this->render('post_request/index.html.twig', [
            'post_requests' => $repo->findByPostId($postid),
            'accept_actions'=>true,
        ]);
    }

    /**
     * @Route("/reply/{id}/{reply}", name="post_request_reply", methods={"GET"})
     */
    public function reply($id, $reply)
    {
        $postRequest = $this->getDoctrine()->getRepository(PostRequest::class)->find($id);
        if($postRequest == null )
        {
            return $this->render('account/error.html.twig', [
                'Message' => "No post request was found with id=".$id,
                ]);
        }
        $session = $this->get('session');
        $username = $session->get('username');
        $user = $this->getDoctrine()->getRepository(User::class)->findOneByUsername($username);
        if($user == null)
        {
            return $this->render('account/error.html.twig', [
                'Message' => "You are not authorized!",
                ]);
        }

        $post = $postRequest->getPostid();
        if($user != $post->getUserId())
        {
            return $this->render('account/error.html.twig', [
                'Message' => "You are not authorized!",
                ]);
        }

        if($post->getStatus() != "Active")
        {
            return $this->render('account/error.html.twig', [
                'Message' => "Post is not Active!",
                ]);
        }

        $entityManager = $this->getDoctrine()->getManager();
        $postRequest->setStatus($reply);
        if($reply == "Accepted")
        {
            $post->setStatus("Resolved");
        }
        $entityManager->flush();
        return $this->redirectToRoute('view_post_requests/'.$post->getId());
    }

    /**
     * @Route("/send/{postid}", name="send_post_request", methods={"GET"})
     */
    public function send($postid)
    {
        $post = $this->getDoctrine()->getRepository(Post::class)->find($postid);
        if($post == null)
        {
            return $this->render('account/error.html.twig', [
                'Message' => "No post was found with id=".$id,
                ]);
        }
        $session = $this->get('session');
        $username = $session->get('username');
        $user = $this->getDoctrine()->getRepository(User::class)->findOneByUsername($username);
        if($user == null)
        {
            return $this->render('account/error.html.twig', [
                'Message' => "You are not authorized to send requests to this post!",
                ]);
        }

        if($user == $post->getUserid())
        {
            return $this->render('account/error.html.twig', [
                'Message' => "You cant send requests to your post!",
                ]);
        }
        $postRequest = new PostRequest();
        $postRequest->setUserid($user);
        $postRequest->setPostid($post);
        $postRequest->setStatus("Waiting");
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($postRequest);
        $entityManager->flush();

        return $this->render('post_request/show.html.twig', [
            'post_request' => $postRequest,
        ]);
    }

    /**
     * @Route("/new", name="post_request_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $postRequest = new PostRequest();
        $form = $this->createForm(PostRequestType::class, $postRequest);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($postRequest);
            $entityManager->flush();

            return $this->redirectToRoute('post_request_index');
        }

        return $this->render('post_request/new.html.twig', [
            'post_request' => $postRequest,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="post_request_show", methods={"GET"})
     */
    public function show($id)
    {
        $postRequest = $this->getDoctrine()->getRepository(PostRequest::class)->find($id);
        $session = $this->get('session');
        $username = $session->get('username');
        $user = $this->getDoctrine()->getRepository(User::class)->findOneByUsername($username);
        if($user == null)
        {
            return $this->render('account/error.html.twig', [
                'Message' => "You are not authorized to view requests for this post!",
                ]);
        }

        if($user != $postRequest->getUserid() && $user != $postRequest->getPostId()->getUserId())
        {
            return $this->render('account/error.html.twig', [
                'Message' => "You are not authorized!",
                ]);
        }
        return $this->render('post_request/show.html.twig', [
            'post_request' => $postRequest,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="post_request_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, PostRequest $postRequest): Response
    {
        $form = $this->createForm(PostRequestType::class, $postRequest);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('post_request_index');
        }

        return $this->render('post_request/edit.html.twig', [
            'post_request' => $postRequest,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="post_request_delete", methods={"DELETE"})
     */
    public function delete(Request $request, PostRequest $postRequest): Response
    {
        if ($this->isCsrfTokenValid('delete'.$postRequest->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($postRequest);
            $entityManager->flush();
        }

        return $this->redirectToRoute('post_request_index');
    }
}

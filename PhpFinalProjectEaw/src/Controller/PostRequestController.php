<?php

namespace App\Controller;

use App\Entity\UserRoles;
use App\Entity\Roles;
use App\Entity\PostRequest;
use App\Entity\Post;
use App\Entity\User;
use App\Entity\UserProfile;
use App\Form\PostRequestType;
use App\Repository\PostRequestRepository;
use App\Helpers\EmailSender;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/post/request")
 */
class PostRequestController extends AbstractController
{

    private function checkIsLoggedUserAdmin()
    {
        $session = $this->get('session');
        $username = $session->get('username');
        $user = $this->getDoctrine()->getRepository(User::class)->findOneByUsername($username);
        if($user == null)
        {
            return false;
        }
        $roles =  $this->getDoctrine()->getRepository(UserRoles::class)->findByUserId($user->getId());
        foreach($roles as $role)
        {
            if($role->getRoleid()->getRolename()=="ROLE_ADMIN")
            {
                return true;
            }
        }
        return false;
    }

    /**
     * @Route("/my_requests", name="post_request_index", methods={"GET"})
     */
    public function index()
    {
        $isAdmin = $this->checkIsLoggedUserAdmin();

        $session = $this->get('session');
        $username = $session->get('username');
        $user = $this->getDoctrine()->getRepository(User::class)->findOneByUsername($username);
        if($user == null)
        {
            return $this->render('account/error.html.twig', [
                'Message' => "You are not authorized!",
                'IsAdmin'=>$isAdmin
                ]);
        }

        $repo = $this->getDoctrine()->getRepository(PostRequest::class);

        return $this->render('post_request/index.html.twig', [
            'post_requests' => $repo->findByUserId($user->getId()),
            'accept_actions'=>false,
            'IsAdmin'=>$isAdmin
        ]);
    }

    /**
     * @Route("/view_post_requests/{postid}", name="view_post_requests", methods={"GET"})
     */
    public function view_post_requests($postid)
    {
        $isAdmin = $this->checkIsLoggedUserAdmin();

        $post = $this->getDoctrine()->getRepository(Post::class)->find($postid);
        if($post == null)
        {
            return $this->render('account/error.html.twig', [
                'Message' => "No post found with id=".$postid,
                'IsAdmin'=>$isAdmin
                ]);
        }

        $session = $this->get('session');
        $username = $session->get('username');
        $user = $this->getDoctrine()->getRepository(User::class)->findOneByUsername($username);
        if($user == null || $user != $post->getUserId())
        {
            return $this->render('account/error.html.twig', [
                'Message' => "You are not authorized!",
                'IsAdmin'=>$isAdmin
                ]);
        }

        $repo = $this->getDoctrine()->getRepository(PostRequest::class);

        return $this->render('post_request/index.html.twig', [
            'post_requests' => $repo->findByPostId($postid),
            'accept_actions'=>true,
            'IsAdmin'=>$isAdmin
        ]);
    }

    /**
     * @Route("/reply/{id}/{reply}", name="post_request_reply", methods={"GET"})
     */
    public function reply($id, $reply)
    {
        $isAdmin = $this->checkIsLoggedUserAdmin();

        $postRequest = $this->getDoctrine()->getRepository(PostRequest::class)->find($id);
        if($postRequest == null )
        {
            return $this->render('account/error.html.twig', [
                'Message' => "No post request was found with id=".$id,
                'IsAdmin'=>$isAdmin
                ]);
        }
        $session = $this->get('session');
        $username = $session->get('username');
        $user = $this->getDoctrine()->getRepository(User::class)->findOneByUsername($username);
        if($user == null)
        {
            return $this->render('account/error.html.twig', [
                'Message' => "You are not authorized!",
                'IsAdmin'=>$isAdmin
                ]);
        }

        $post = $postRequest->getPostid();
        if($user != $post->getUserId())
        {
            return $this->render('account/error.html.twig', [
                'Message' => "You are not authorized!",
                'IsAdmin'=>$isAdmin
                ]);
        }

        if($post->getStatus() != "Active")
        {
            return $this->render('account/error.html.twig', [
                'Message' => "Post is not Active!",
                'IsAdmin'=>$isAdmin
                ]);
        }

        $entityManager = $this->getDoctrine()->getManager();
        $postRequest->setStatus($reply);
        if($reply == "Accepted")
        {
            $post->setStatus("Resolved");
        }
        $entityManager->flush();

        $profileId = $postRequest->getUserid()->getUserprofileid();
        $profile = $this->getDoctrine()->getRepository(UserProfile::class)->find($profileId);

        if($profile != null)
        {
            $emailSender = new EmailSender();
            $displayName = $profile->getFirstname()." ".$profile->getLastname();
            $subject = "Request ".$reply;
            $postTitle = $postRequest->getPostid()->getTitle();
            $content = "Hello ".$displayName.", <br>Your request for post with title ".$postTitle." has been ".$reply."<br>You can view the status of your requests by accessing the <strong>My Requests</strong> section on our website.";
            $emailSender->sendMail($profile->getEmail(),$displayName,$subject,$content);
        }

        return $this->redirectToRoute('/view_post_requests/'.$post->getId());
    }

    /**
     * @Route("/send/{postid}", name="send_post_request", methods={"GET"})
     */
    public function send($postid)
    {
        $isAdmin = $this->checkIsLoggedUserAdmin();

        $post = $this->getDoctrine()->getRepository(Post::class)->find($postid);
        if($post == null)
        {
            return $this->render('account/error.html.twig', [
                'Message' => "No post was found with id=".$id,
                'IsAdmin'=>$isAdmin
                ]);
        }
        $session = $this->get('session');
        $username = $session->get('username');
        $user = $this->getDoctrine()->getRepository(User::class)->findOneByUsername($username);
        if($user == null)
        {
            return $this->render('account/error.html.twig', [
                'Message' => "You are not authorized to send requests to this post!",
                'IsAdmin'=>$isAdmin
                ]);
        }

        if($user == $post->getUserid())
        {
            return $this->render('account/error.html.twig', [
                'Message' => "You cant send requests to your post!",
                'IsAdmin'=>$isAdmin
                ]);
        }
        $postRequest = new PostRequest();
        $postRequest->setUserid($user);
        $postRequest->setPostid($post);
        $postRequest->setStatus("Waiting");
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($postRequest);
        $entityManager->flush();

        $profileId = $postRequest->getPostid()->getUserid()->getUserprofileid();
        $profile = $this->getDoctrine()->getRepository(UserProfile::class)->find($profileId);

        if($profile != null)
        {
            $emailSender = new EmailSender();
            $displayName = $profile->getFirstname()." ".$profile->getLastname();
            $subject = "New request recieved";
            $requestUser = $postRequest->getUserid()->getUsername();
            $postTitle = $postRequest->getPostid()->getTitle();
            $content = "Hello ".$displayName.", <br>You recieved a new request from ".$requestUser." for your post with title: ".$postTitle."<br>You can view your requests by accessing the <strong>View Requests</strong> section of your post.";
            $emailSender->sendMail($profile->getEmail(),$displayName,$subject,$content);
        }

        return $this->render('post_request/show.html.twig', [
            'post_request' => $postRequest,
            'IsAdmin'=>$isAdmin
        ]);
    }

    /**
     * @Route("/new", name="post_request_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $isAdmin = $this->checkIsLoggedUserAdmin();

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
            'IsAdmin'=>$isAdmin
        ]);
    }

    /**
     * @Route("/{id}", name="post_request_show", methods={"GET"})
     */
    public function show($id)
    {
        $isAdmin = $this->checkIsLoggedUserAdmin();

        $postRequest = $this->getDoctrine()->getRepository(PostRequest::class)->find($id);
        if($postRequest == null)
        {
            return $this->render('account/error.html.twig', [
                'Message' => "No request found with id=".$id,
                'IsAdmin'=>$isAdmin
                ]);
        }
        $session = $this->get('session');
        $username = $session->get('username');
        $user = $this->getDoctrine()->getRepository(User::class)->findOneByUsername($username);
        if($user == null)
        {
            return $this->render('account/error.html.twig', [
                'Message' => "You are not authorized to view requests for this post!",
                'IsAdmin'=>$isAdmin
                ]);
        }

        if($user != $postRequest->getUserid() && $user != $postRequest->getPostId()->getUserId())
        {
            return $this->render('account/error.html.twig', [
                'Message' => "You are not authorized!",
                'IsAdmin'=>$isAdmin
                ]);
        }
        return $this->render('post_request/show.html.twig', [
            'post_request' => $postRequest,
            'IsAdmin'=>$isAdmin
        ]);
    }

    /**
     * @Route("/{id}/edit", name="post_request_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, PostRequest $postRequest): Response
    {
        $isAdmin = $this->checkIsLoggedUserAdmin();

        $form = $this->createForm(PostRequestType::class, $postRequest);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('post_request_index');
        }

        return $this->render('post_request/edit.html.twig', [
            'post_request' => $postRequest,
            'form' => $form->createView(),
            'IsAdmin'=>$isAdmin
        ]);
    }

    /**
     * @Route("/{id}", name="post_request_delete", methods={"DELETE"})
     */
    public function delete(Request $request, PostRequest $postRequest): Response
    {
        $isAdmin = $this->checkIsLoggedUserAdmin();

        if ($this->isCsrfTokenValid('delete'.$postRequest->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($postRequest);
            $entityManager->flush();
        }

        return $this->redirectToRoute('post_request_index');
    }
}

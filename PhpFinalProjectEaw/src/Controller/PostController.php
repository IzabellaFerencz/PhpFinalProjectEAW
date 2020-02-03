<?php

namespace App\Controller;
use App\Entity\Post;
use App\Entity\User;
use App\Entity\UserRoles;
use App\Entity\Roles;
use App\Form\PostType;
use App\Repository\PostRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Helpers\LuceneSearcher;
use Doctrine\Common\Collections\ArrayCollection;


/**
 * @Route("/post")
 */
class PostController extends AbstractController
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
     * @Route("/", name="post_index", methods={"GET"})
     */
    public function index(PostRepository $postRepository): Response
    {  
        $isAdmin = $this->checkIsLoggedUserAdmin();
        return $this->render('post/index.html.twig', [
            'posts' => $postRepository->findAll(),
            'IsAdmin' => $isAdmin
        ]);
    }

    /**
     * @Route("/search", name="post_search", methods={"POST"})
     */
    public function search(PostRepository $postRepository): Response
    {
        $isAdmin = $this->checkIsLoggedUserAdmin();
        $allPosts = $postRepository->findAll();
        foreach($allPosts as $post)
        {
            LuceneSearcher::updateLuceneIndex($post);
        }

        $searchTerm = $_POST["searchTerm"];
        $hits = LuceneSearcher::getLuceneIndex()->find($searchTerm);

        $results = new ArrayCollection();
        foreach($hits as $hit) {
            $document = $hit->getDocument();
            $res = $postRepository-> find($document->key);
            if($res != null)
            {
                $results -> add($res);
            }
        }

        return $this->render('post/index.html.twig', [
            'posts' => $results,
            'IsAdmin' => $isAdmin
        ]);
    }

    /**
     * @Route("/my_posts", name="my_posts", methods={"GET"})
     */
    public function my_posts(PostRepository $postRepository): Response
    {
        $isAdmin = $this->checkIsLoggedUserAdmin();
        $session = $this->get('session');
        $username = $session->get('username');
        $user = $this->getDoctrine()->getRepository(User::class)->findOneByUsername($username);
        if($user==null){
            return $this->render('account/error.html.twig', [
                'Message' => "You must be logged in!",
                ]);
        }
        return $this->render('post/index.html.twig', [
            'posts' => $postRepository->findByUserId($user->getId()),
            'IsAdmin'=>$isAdmin
        ]);
    }

    /**
     * @Route("/new", name="newpost", methods={"GET"})
     */
    public function newpost()
    {
        $isAdmin = $this->checkIsLoggedUserAdmin();
        $session = $this->get('session');
        $username = $session->get('username');
        if($username==''){
            return $this->render('account/error.html.twig', [
                'Message' => "You must be logged in to create a post!",
                ]);
        }
        return $this->render('post/newpost.html.twig', [
            'Title' => "",
            'Description' => "",
            'Message' => "",
            'Price' =>"",
            'IsAdmin'=>$isAdmin
        ]);
    }

    /**
     * @Route("/new", name="post_new", methods={"POST"})
     */
    public function new(Request $request): Response
    {
        $isAdmin = $this->checkIsLoggedUserAdmin();
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
        $price = $_POST["price"];

        if($title == "" || $description == "" || $status == "" || $price == "")
        {
            return $this->render('post/newpost.html.twig', [
                'Title' => $title,
                'Description' => $description,
                'Price' => $price,
                'Message' => "All Fields are mandatory",
                'IsAdmin'=>$isAdmin
            ]);
        }

        if($status != "Active")
        {
            return $this->render('post/newpost.html.twig', [
                'Title' => $title,
                'Description' => $description,
                'Price' => $price,
                'Message' => "All new post must be in active state!",
                'IsAdmin'=>$isAdmin
            ]);
        }

        try {
            $post = new Post();
            $post->setTitle($title);
            $post->setDescription($description);
            $post->setPrice($price);
            $post->setStatus($status);
            $post->setUserid($user);

            if(!$post->isValid())
            {
                return $this->render('post/newpost.html.twig', [
                    'Title' => $title,
                    'Description' => $description,
                    'Price' => $price,
                    'Message' => "Post is in invalid state.",
                    'IsAdmin'=>$isAdmin
                ]);
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($post);
            $entityManager->flush();
        } catch (\Throwable $th) {
            return $this->render('account/error.html.twig', [
                'Message' => "Something went wrong!",
                'IsAdmin'=>$isAdmin
                ]);
        }

        return $this->redirectToRoute('post_index');        
    }

    /**
     * @Route("/{id}", name="post_show", methods={"GET"})
     */
    public function show($id): Response
    {
        $isAdmin = $this->checkIsLoggedUserAdmin();
        $post = $this->getDoctrine()->getRepository(Post::class)->find($id);
        if($post == null)
        {
            return $this->render('account/error.html.twig', [
                'Message' => "No post found with given id!",
                'IsAdmin'=>$isAdmin
                ]);
        }
        return $this->render('post/show.html.twig', [
            'post' => $post,
            'IsAdmin'=>$isAdmin
        ]);
    }

    /**
     * @Route("/{id}/edit", name="post_edit_get", methods={"GET"})
     */
    public function editpost($id)
    {
        $isAdmin = $this->checkIsLoggedUserAdmin();
        $post = $this->getDoctrine()->getRepository(Post::class)->find($id);
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
        if($user == null || $user != $post->getUserid())
        {
            return $this->render('account/error.html.twig', [
                'Message' => "You are not authorized to edit this post!",
                'IsAdmin'=>$isAdmin
                ]);
        }
        return $this->render('post/edit.html.twig', [
            'post' => $post,
            'Message' => "",
            'IsAdmin'=>$isAdmin
        ]);
    }

    /**
     * @Route("/{id}/edit", name="post_edit", methods={"POST"})
     */
    public function edit($id): Response
    {
        $isAdmin = $this->checkIsLoggedUserAdmin();
        $post = $this->getDoctrine()->getRepository(Post::class)->find($id);
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
        if($user == null || $user != $post->getUserid())
        {
            return $this->render('account/error.html.twig', [
                'Message' => "You are not authorized to edit this post!",
                'IsAdmin'=>$isAdmin
                ]);
        }

        $title = $_POST["title"];
        $description = $_POST["description"];
        $status = $_POST["status"];
        $price = $_POST["price"];

        if($title == "" || $description == "" || $status == "" || $price == "")
        {
            return $this->render('post/edit.html.twig', [
                'post' => $post,
                'Message' => "All Fields are mandatory",
                'IsAdmin'=>$isAdmin
            ]);
        }

        if($status != "Active" && $status != "Resolved" && $status != "Expired")
        {
            return $this->render('post/edit.html.twig', [
                'post' => $post,
                'Message' => "Invalid value for status",
                'IsAdmin'=>$isAdmin
            ]);
        }

        $post->setTitle($title);
        $post->setDescription($description);
        $post->setStatus($status);
        $post->setPrice($price);

        if(!$post->isValid())
        {
            return $this->render('post/edit.html.twig', [
                'post' => $post,
                'Message' => "Post is in invalid state.",
                'IsAdmin'=>$isAdmin
            ]);
        }

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->flush();

        return $this->render('post/show.html.twig', [
            'post' => $post,
            'IsAdmin'=>$isAdmin
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

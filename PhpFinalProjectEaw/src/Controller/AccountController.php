<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User;
use App\Entity\UserProfile;

/**
 * @Route("/account")
 */
class AccountController extends AbstractController
{
    /**
     * @Route("/register", name="register", methods={"GET"})
     */
    public function register()
    {
        return $this->render('account/register.html.twig', [
        ]);
    }

    /**
     * @Route("/register", name="register_user", methods={"POST"})
     */
    public function register_user()
    {
        $username = $_POST["username"];
        $password = $_POST["password"];
        $confpassword = $_POST["confpassword"];
        if($password != $confpassword)
        {
            return $this->render('account/error.html.twig', [
                'Message' => "Password and confirmation password do not match",
                ]);
        }
        $newuser = new User();
        $newuser->setUsername($username);
        $newuser->setPassword(password_hash($password,PASSWORD_DEFAULT));
        try {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($newuser);
            $entityManager->flush();
        } catch (\Throwable $th) {
            return $this->render('account/error.html.twig', [
                'Message' => "Username already exists. Please use a different username or log in to your account.",
                ]);
        }

        return $this->redirectToRoute('post_index');
    }

    /**
     * @Route("/login", name="login", methods={"GET"})
     */
    public function login()
    {
        return $this->render('account/login.html.twig', [
        ]);
    }

    /**
     * @Route("/login", name="loginuser", methods={"POST"})
     */
    public function loginuser()
    {
        $username = $_POST["username"];
        $password = $_POST["password"];

        $user = $this->getDoctrine()->getRepository(User::class)->findOneByUsername($username);

        if(password_verify($password, $user->getPassword()))
        {
            $session = $this->get('session');
            $session->set('username',$username);
            return $this->render('account/error.html.twig', [
                'Message' => "Good",
                ]);
        }
        else
        {
            return $this->render('account/error.html.twig', [
                'Message' => "bad",
                ]);
        }

    }

    /**
     * @Route("/logout", name="logout", methods={"GET"})
     */
    public function logout()
    {
        $session = $this->get('session');
        $session->set('username','');
        return $this->redirectToRoute('post_index');

    }

    /**
     * @Route("/profile", name="profile", methods={"GET"})
     */
    public function profile()
    {
        try {
            $session = $this->get('session');
            $username = $session->get('username');
            if($username==''){
                return $this->render('account/error.html.twig', [
                    'Message' => "You must be logged in!",
                    ]);
            }
            $user = $this->getDoctrine()->getRepository(User::class)->findOneByUsername($username);
            $userprofile = $user->getUserprofileid();
            if($userprofile == null)
            {
                return $this->render('account/createprofile.html.twig', [
            ]);
            }
            return $this->render('account/profile.html.twig', [
                'profile' => $userprofile,
                ]);
        } catch (\Throwable $th) {
            return $this->render('account/error.html.twig', [
                'Message' => $th,
                ]);
        }
    }

    /**
     * @Route("/createprofile", name="create_profile", methods={"GET"})
     */
    public function create_profile()
    {
        $session = $this->get('session');
        $username = $session->get('username');
        if($username==''){
            return $this->render('account/error.html.twig', [
                'Message' => "You must be logged in to create a profile!",
                ]);
        }
        return $this->render('account/createprofile.html.twig', [
            ]);
    }

    /**
     * @Route("/createprofile", name="create_profile_post", methods={"POST"})
     */
    public function create_profile_post()
    {
        try {
            $session = $this->get('session');
            $username = $session->get('username');
            $user = $this->getDoctrine()->getRepository(User::class)->findOneByUsername($username);
            if($user == null)
            {
                return $this->render('account/error.html.twig', [
                    'Message' => "You must be logged in to create a profile!",
                    ]);
            }
            if($user->getUserprofileid()!=null)
            {
                return $this->render('account/error.html.twig', [
                    'Message' => "You already have profile. You can edit your existing profile.",
                    ]);
            }
            $fname = $_POST["firstname"];
            $lname = $_POST["lastname"];
            $phone = $_POST["phonenr"];
            $adr = $_POST["address"];

            $profile = new UserProfile();
            $profile->setFirstname($fname);
            $profile->setLastname($lname);
            $profile->setAddress($adr);
            $profile->setPhonenr($phone);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($profile);
            $entityManager->flush();
            $user->setUserprofileid($profile);
            $entityManager->flush();


        } catch (\Throwable $th) {
            return $this->render('account/error.html.twig', [
                'Message' =>"Something went wrong",
                ]);
        }

        return $this->redirectToRoute('profile');

    }

    /**
     * @Route("/editprofile", name="edit_profile", methods={"GET"})
     */
    public function edit_profile()
    {
        try {
            $session = $this->get('session');
            $username = $session->get('username');
            $user = $this->getDoctrine()->getRepository(User::class)->findOneByUsername($username);
            if($user == null)
            {
                return $this->render('account/error.html.twig', [
                    'Message' => "You must be logged in to edit your profile!",
                    ]);
            }
            $userprofile = $user->getUserprofileid();
            return $this->render('account/editprofile.html.twig', [
                'profile'=>$userprofile,
                ]);
        } catch (\Throwable $th) {
            return $this->render('account/error.html.twig', [
                'Message' =>"Something went wrong",
                ]);
        }
    }

    /**
     * @Route("/editprofile", name="edit_profile_post", methods={"POST"})
     */
    public function edit_profile_post()
    {
        try {
            $session = $this->get('session');
            $username = $session->get('username');
            $user = $this->getDoctrine()->getRepository(User::class)->findOneByUsername($username);
            if($user == null)
            {
                return $this->render('account/error.html.twig', [
                    'Message' => "You must be logged in to edit your profile!",
                    ]);
            }
            $profile = $user->getUserprofileid();
            if($profile==null)
            {
                return $this->render('account/error.html.twig', [
                    'Message' => "You dont have a profile. Please create one first.",
                    ]);
            }
            $fname = $_POST["firstname"];
            $lname = $_POST["lastname"];
            $phone = $_POST["phonenr"];
            $adr = $_POST["address"];

            $profile->setFirstname($fname);
            $profile->setLastname($lname);
            $profile->setAddress($adr);
            $profile->setPhonenr($phone);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->flush();

        } catch (\Throwable $th) {
            return $this->render('account/error.html.twig', [
                'Message' =>"Something went wrong",
                ]);
        }

        return $this->redirectToRoute('profile');
    }

    /**
     * @Route("/deleteprofile", name="delete_profile_check", methods={"GET"})
     */
    public function delete_profile_check()
    {
        $session = $this->get('session');
        $username = $session->get('username');
        if($username==''){
            return $this->render('account/error.html.twig', [
                'Message' =>"You must be logged in.",
                ]);
        }
        return $this->render('account/deleteprofile.html.twig', [
            ]);
    }

    /**
     * @Route("/deleteprofile_confirmed", name="delete_profile", methods={"GET"})
     */
    public function delete_profile()
    {
        try {
            $session = $this->get('session');
            $username = $session->get('username');
            $user = $this->getDoctrine()->getRepository(User::class)->findOneByUsername($username);
            if($user == null)
            {
                return $this->render('account/error.html.twig', [
                    'Message' => "You must be logged in to delete your profile!",
                    ]);
            }
            $profile = $user->getUserprofileid();
            if($profile==null)
            {
                return $this->render('account/error.html.twig', [
                    'Message' => "You dont have a profile. Please create one first.",
                    ]);
            }
            $entityManager = $this->getDoctrine()->getManager();
            $user->setUserprofileid(null);
            $entityManager->flush();
            $entityManager->remove($profile);
            $entityManager->flush();
            return $this->redirectToRoute('post_index');
        } catch (\Throwable $th) {
            return $this->render('account/error.html.twig', [
                'Message' =>"Something went wrong",
                ]);
        }
    }
}

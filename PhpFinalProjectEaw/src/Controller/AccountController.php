<?php

namespace App\Controller;

use App\Entity\UserRoles;
use App\Entity\Roles;
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
     * @Route("/register", name="register", methods={"GET"})
     */
    public function register()
    {
        $isAdmin = $this->checkIsLoggedUserAdmin();

        return $this->render('account/register.html.twig', [
            'Username' => "",
            'Message' => "",
            'IsAdmin'=>$isAdmin
        ]);
    }

    /**
     * @Route("/register", name="register_user", methods={"POST"})
     */
    public function register_user()
    {
        $isAdmin = $this->checkIsLoggedUserAdmin();

        $username = $_POST["username"];
        $password = $_POST["password"];
        if($username == "" || $password == "")
        {
            return $this->render('account/register.html.twig', [
                'Username' => $username,
                'Message' => "Username and password can't be empty",
                'IsAdmin'=>$isAdmin
                ]);
        }
        $confpassword = $_POST["confpassword"];
        if($password != $confpassword)
        {
            return $this->render('account/register.html.twig', [
                'Username' => $username,
                'Message' => "Password and confirmation password do not match",
                'IsAdmin'=>$isAdmin
                ]);
            
        }
        $newuser = new User();
        $newuser->setUsername($username);
        $newuser->setPassword(password_hash($password,PASSWORD_DEFAULT));
        try {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($newuser);
            $entityManager->flush();
            $session = $this->get('session');
            $session->set('username',$username);
        } catch (\Throwable $th) {

            return $this->render('account/register.html.twig', [
                'Username' => $username,
                'Message' => "Username already exists. Please use a different username or log in to your account.",
                'IsAdmin'=>$isAdmin
                ]);
        }

        return $this->redirectToRoute('post_index');
    }

    /**
     * @Route("/login", name="login", methods={"GET"})
     */
    public function login()
    {
        $isAdmin = $this->checkIsLoggedUserAdmin();

        return $this->render('account/login.html.twig', [
            'Username' => "",
            'Message' => "",
            'IsAdmin'=>$isAdmin
        ]);
    }

    /**
     * @Route("/login", name="loginuser", methods={"POST"})
     */
    public function loginuser()
    {
        $isAdmin = $this->checkIsLoggedUserAdmin();

        $username = $_POST["username"];
        $password = $_POST["password"];
        if($username == "" || $password == "")
        {
            return $this->render('account/login.html.twig', [
                'Username' => $username,
                'Message' => "Username and password cant be empty",
                'IsAdmin'=>$isAdmin
                ]);
        }
        $user = $this->getDoctrine()->getRepository(User::class)->findOneByUsername($username);

        if($user != null && password_verify($password, $user->getPassword()))
        {
            $session = $this->get('session');
            $session->set('username',$username);
            return $this->redirectToRoute('post_index');
        }
        else
        {
            return $this->render('account/login.html.twig', [
                'Username' => $username,
                'Message' => "Incorrect username or password",
                'IsAdmin'=>$isAdmin
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
        $isAdmin = $this->checkIsLoggedUserAdmin();

        try {
            $session = $this->get('session');
            $username = $session->get('username');
            if($username==''){
                return $this->render('account/error.html.twig', [
                    'Message' => "You must be logged in!",
                    'IsAdmin'=>$isAdmin
                    ]);
            }
            $user = $this->getDoctrine()->getRepository(User::class)->findOneByUsername($username);
            $userprofile = $user->getUserprofileid();
            if($userprofile == null)
            {
                return $this->redirectToRoute('create_profile');
            }
            return $this->render('account/profile.html.twig', [
                'profile' => $userprofile,
                'IsAdmin'=>$isAdmin
                ]);
        } catch (\Throwable $th) {
            return $this->render('account/error.html.twig', [
                'Message' => $th,
                'IsAdmin'=>$isAdmin
                ]);
        }
    }

    /**
     * @Route("/createprofile", name="create_profile", methods={"GET"})
     */
    public function create_profile()
    {
        $isAdmin = $this->checkIsLoggedUserAdmin();

        $session = $this->get('session');
        $username = $session->get('username');
        if($username==''){
            return $this->render('account/error.html.twig', [
                'Message' => "You must be logged in to create a profile!",
                'IsAdmin'=>$isAdmin
                ]);
        }
        return $this->render('account/createprofile.html.twig', [
            'Firstname' => "",
            'Lastname' => "",
            'PhoneNr' => "",
            'Address' => "",
            'Email' => "",
            'Message' => "",
            'IsAdmin'=>$isAdmin
            ]);
    }

    /**
     * @Route("/createprofile", name="create_profile_post", methods={"POST"})
     */
    public function create_profile_post()
    {
        $isAdmin = $this->checkIsLoggedUserAdmin();

        try {
            $session = $this->get('session');
            $username = $session->get('username');
            $user = $this->getDoctrine()->getRepository(User::class)->findOneByUsername($username);
            if($user == null)
            {
                
                return $this->render('account/error.html.twig', [
                    'Message' => "You must be logged in to create a profile!",
                    'IsAdmin'=>$isAdmin
                    ]);
            }
            if($user->getUserprofileid()!=null)
            {
                return $this->render('account/error.html.twig', [
                    'Message' => "You already have profile. You can edit your existing profile.",
                    'IsAdmin'=>$isAdmin
                    ]);
            }
            $fname = $_POST["firstname"];
            $lname = $_POST["lastname"];
            $phone = $_POST["phonenr"];
            $adr = $_POST["address"];
            $mail = $_POST["email"];

            if($fname == "" || $lname == "" || $phone == "" || $adr == "" || $mail == "")
            {
                return $this->render('account/createprofile.html.twig', [
                    'Firstname' => $fname,
                    'Lastname' => $lname,
                    'PhoneNr' => $phone,
                    'Address' => $adr,
                    'Message' => "All fields are mandatory!",
                    'IsAdmin'=>$isAdmin
                    ]);
            }

            $profile = new UserProfile();
            $profile->setFirstname($fname);
            $profile->setLastname($lname);
            $profile->setAddress($adr);
            $profile->setPhonenr($phone);
            $profile->setEmail($mail);

            if(!$profile->isValid())
            {
                return $this->render('account/createprofile.html.twig', [
                    'Firstname' => $fname,
                    'Lastname' => $lname,
                    'PhoneNr' => $phone,
                    'Address' => $adr,
                    'Email' => $mail,
                    'Message' => "Invalid profile!",
                    'IsAdmin'=>$isAdmin
                    ]);
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($profile);
            $entityManager->flush();
            $user->setUserprofileid($profile);
            $entityManager->flush();


        } catch (\Throwable $th) {
            return $this->render('account/error.html.twig', [
                'Message' =>"Something went wrong",
                'IsAdmin'=>$isAdmin
                //'Message' =>$profile,
                ]);
        }

        return $this->redirectToRoute('profile');

    }

    /**
     * @Route("/editprofile", name="edit_profile", methods={"GET"})
     */
    public function edit_profile()
    {
        $isAdmin = $this->checkIsLoggedUserAdmin();

        try {
            $session = $this->get('session');
            $username = $session->get('username');
            $user = $this->getDoctrine()->getRepository(User::class)->findOneByUsername($username);
            if($user == null)
            {
                return $this->render('account/error.html.twig', [
                    'Message' => "You must be logged in to edit your profile!",
                    'IsAdmin'=>$isAdmin
                    ]);
            }
            $userprofile = $user->getUserprofileid();
            return $this->render('account/editprofile.html.twig', [
                'profile'=>$userprofile,
                'Message' => "",
                'IsAdmin'=>$isAdmin
                ]);
        } catch (\Throwable $th) {
            return $this->render('account/error.html.twig', [
                'Message' =>"Something went wrong",
                'IsAdmin'=>$isAdmin
                ]);
        }
    }

    /**
     * @Route("/editprofile", name="edit_profile_post", methods={"POST"})
     */
    public function edit_profile_post()
    {
        $isAdmin = $this->checkIsLoggedUserAdmin();

        try {
            $session = $this->get('session');
            $username = $session->get('username');
            $user = $this->getDoctrine()->getRepository(User::class)->findOneByUsername($username);
            if($user == null)
            {
                return $this->render('account/error.html.twig', [
                    'Message' => "You must be logged in to edit your profile!",
                    'IsAdmin'=>$isAdmin
                    ]);
            }
            $profile = $user->getUserprofileid();
            if($profile==null)
            {
                return $this->render('account/error.html.twig', [
                    'Message' => "You dont have a profile. Please create one first.",
                    'IsAdmin'=>$isAdmin
                    ]);
            }
            $fname = $_POST["firstname"];
            $lname = $_POST["lastname"];
            $phone = $_POST["phonenr"];
            $adr = $_POST["address"];
            $mail = $_POST["email"];

            if($fname == "" || $lname == "" || $phone == "" || $adr == "" || $mail == "")
            {
                return $this->render('account/editprofile.html.twig', [
                    'profile' => $profile,
                    'Message' => "All fields are mandatory!",
                    'IsAdmin'=>$isAdmin
                    ]);
            }

            $profile->setFirstname($fname);
            $profile->setLastname($lname);
            $profile->setAddress($adr);
            $profile->setPhonenr($phone);
            $profile->setEmail($mail);

            if(!$profile->isValid())
            {
                return $this->render('account/editprofile.html.twig', [
                    'profile' => $profile,
                    'Message' => "Invalid profile!",
                    'IsAdmin'=>$isAdmin
                    ]);
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->flush();

        } catch (\Throwable $th) {
            return $this->render('account/error.html.twig', [
                'Message' =>"Something went wrong",
                'IsAdmin'=>$isAdmin
                ]);
        }

        return $this->redirectToRoute('profile');
    }

    /**
     * @Route("/deleteprofile", name="delete_profile_check", methods={"GET"})
     */
    public function delete_profile_check()
    {
        $isAdmin = $this->checkIsLoggedUserAdmin();

        $session = $this->get('session');
        $username = $session->get('username');
        if($username==''){
            return $this->render('account/error.html.twig', [
                'Message' =>"You must be logged in.",
                'IsAdmin'=>$isAdmin
                ]);
        }
        return $this->render('account/deleteprofile.html.twig', [
            'IsAdmin'=>$isAdmin
            ]);
    }

    /**
     * @Route("/deleteprofile_confirmed", name="delete_profile", methods={"GET"})
     */
    public function delete_profile()
    {
        $isAdmin = $this->checkIsLoggedUserAdmin();

        try {
            $session = $this->get('session');
            $username = $session->get('username');
            $user = $this->getDoctrine()->getRepository(User::class)->findOneByUsername($username);
            if($user == null)
            {
                return $this->render('account/error.html.twig', [
                    'Message' => "User not found!",
                    'IsAdmin'=>$isAdmin
                    ]);
            }
            $profile = $user->getUserprofileid();
            if($profile==null)
            {
                return $this->render('account/error.html.twig', [
                    'Message' => "You dont have a profile. Please create one first.",
                    'IsAdmin'=>$isAdmin
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
                'IsAdmin'=>$isAdmin
                ]);
        }
    }
}

<?php

namespace App\Controller;

use App\Entity\UserRoles;
use App\Entity\User;
use App\Entity\Roles;
use App\Form\UserRolesType;
use App\Repository\UserRolesRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/user/roles")
 */
class UserRolesController extends AbstractController
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

    private function verifyUser(): bool
    {
        $session = $this->get('session');
        $username = $session->get('username');
        $user = $this->getDoctrine()->getRepository(User::class)->findOneByUsername($username);
        if($user == null)
        {
           return false;
        }
        $roles = $this->getDoctrine()->getRepository(UserRoles::class)->findByUserId($user->getId());
        foreach ($roles as $role) 
        {
            if($role->getRoleId()->getRolename() == "ROLE_ADMIN")
            {
                return true;
            }
        }
        return false;
    }

    /**
     * @Route("/rolesofuser/{id}", name="roles_of_user", methods={"GET"})
     */
    public function showUserWithRoles($id): Response
    {
        if($this->verifyUser()==false)
        {
            return $this->render('account/error.html.twig', [
                'Message' => "You are not authorized",
                'IsAdmin'=>false
                ]);
        }
        $user = $this->getDoctrine()->getRepository(User::class)->find($id);
        if($user == null)
        {
            return $this->render('account/error.html.twig', [
                'Message' => "No user found with given id!",
                'IsAdmin'=>true
                ]);
        }
        $roles = $this->getDoctrine()->getRepository(UserRoles::class)->findByUserId($id);

        return $this->render('user_roles/user_with_roles.html.twig', [
            'user' => $user,
            'roles' => $roles,
            'IsAdmin'=>true
        ]);
    }

    /**
     * @Route("/index", name="users", methods={"GET"})
     */
    public function viewUsers(): Response
    {
        if($this->verifyUser()==false)
        {
            return $this->render('account/error.html.twig', [
                'Message' => "You are not authorized",
                'IsAdmin'=>false
                ]);
        }
        $users =$this->getDoctrine()->getRepository(User::class)->findAll();
        return $this->render('user_roles/viewusers.html.twig', [
            'users' => $users,
            'IsAdmin'=>true
            ]);
    }

    /**
     * @Route("/addrole/{userId}", name="add_role", methods={"GET"})
     */
    public function assignRole($userId): Response
    {
        if($this->verifyUser()==false)
        {
            return $this->render('account/error.html.twig', [
                'Message' => "You are not authorized",
                'IsAdmin'=>false
                ]);
        }
        $user =$this->getDoctrine()->getRepository(User::class)->find($userId);
        if($user == null)
        {
            return $this->render('account/error.html.twig', [
                'Message' => "No user with given id!",
                'IsAdmin'=>true
                ]);
        }
        $roles = $this->getDoctrine()->getRepository(Roles::class)->findAll();
        return $this->render('user_roles/addrole.html.twig', [
            'user' => $user,
            'roles' => $roles,
            'IsAdmin'=>true
            ]);
    }

    /**
     * @Route("/addrole", name="add_role_post", methods={"POST"})
     */
    public function assignRolePost(): Response
    {
        if($this->verifyUser()==false)
        {
            return $this->render('account/error.html.twig', [
                'Message' => "You are not authorized",
                'IsAdmin'=>false
                ]);
        }
        $userid = $_POST["userid"];
        $roleid = $_POST["role"];
        $user =$this->getDoctrine()->getRepository(User::class)->find($userid);
        $role =$this->getDoctrine()->getRepository(Roles::class)->find($roleid);
        if($user == null)
        {
            return $this->render('account/error.html.twig', [
                'Message' => "No user with given id!",
                'IsAdmin'=>true
                ]);
        }
        if($role == null)
        {
            return $this->render('account/error.html.twig', [
                'Message' => "No role with given id!",
                'IsAdmin'=>true
                ]);
        }

        try {
            $existingrole = $this->getDoctrine()->getRepository(UserRoles::class)->findByUserIdAndRoleId($userid, $roleid);
            if($existingrole != null)
            {
                return $this->render('account/error.html.twig', [
                    'Message' => "Role is already assigned to user!",
                    'IsAdmin'=>true
                    ]);
            }
            $userRole = new UserRoles();
            $userRole->setUserid($user);
            $userRole->setRoleid($role);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($userRole);
            $entityManager->flush();
        } catch (\Throwable $th) {
            return $this->render('account/error.html.twig', [
                'Message' => "Failed to add role!",
                'IsAdmin'=>true
                ]);
        }
        return $this->redirectToRoute('roles_of_user', ['id' => $userRole->getUserid()]);
    }

    /**
     * @Route("/{id}/delete", name="user_roles_delete", methods={"GET"})
     */
    public function delete(Request $request, UserRoles $userRole): Response
    {
        if($this->verifyUser()==false)
        {
            return $this->render('account/error.html.twig', [
                'Message' => "You are not authorized",
                'IsAdmin'=>false
                ]);
        }
        try 
        {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($userRole);
            $entityManager->flush();
        } 
        catch (\Throwable $th)
        {
            //throw $th;
        }


        return $this->redirectToRoute('roles_of_user', ['id' => $userRole->getUserid()]);
    }
}

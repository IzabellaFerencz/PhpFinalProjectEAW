<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRolesRepository")
 */
class UserRoles
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Roles")
     * @ORM\JoinColumn(nullable=false)
     */
    private $roles;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserid(): ?User
    {
        return $this->user;
    }

    public function setUserid(?User $userid): self
    {
        $this->user = $userid;

        return $this;
    }

    public function getRoleid(): ?Roles
    {
        return $this->roles;
    }

    public function setRoleid(?Roles $roleid): self
    {
        $this->roles = $roleid;

        return $this;
    }

    public function __toString() {
        return (string)$this->getId();
    }
}

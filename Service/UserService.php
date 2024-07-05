<?php

namespace MauticPlugin\MZagmajsterHandyToolsBundle\Service;

use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Model\UserModel;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserService
{
    private UserModel $userModel;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserModel $userModel, UserPasswordHasherInterface $passwordHasher)
    {
        $this->userModel      = $userModel;
        $this->passwordHasher = $passwordHasher;
    }

    public function changePassword(int $id, string $newPassword): void
    {
        /** @var User|null $user */
        $user = $this->userModel->getEntity($id);

        if (null === $user) {
            throw new \InvalidArgumentException('User not found.');
        }

        $this->userModel->resetPassword($user, $this->passwordHasher, $newPassword);
        $this->userModel->saveEntity($user);
    }
}

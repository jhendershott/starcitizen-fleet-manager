<?php

namespace App\Infrastructure\Security;

use App\Domain\User;
use App\Domain\UserRepositoryInterface;
use HWI\Bundle\OAuthBundle\Security\Core\User\OAuthUserProvider as BaseProvider;
use Ramsey\Uuid\Uuid;

class OAuthUserProvider extends BaseProvider
{
    /**
     * @var UserRepositoryInterface
     */
    private $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function loadUserByUsername($username)
    {
        $citizen = $this->userRepository->getByUsername($username);
        if ($citizen !== null) {
            return $citizen;
        }

        // register one
        return $this->registerNewUser($username);
    }

    private function registerNewUser(string $username): User
    {
        $newUser = new User(Uuid::uuid4(), $username);
        $newUser->createdAt = new \DateTimeImmutable();
        $newUser->token = User::generateToken();

        $this->userRepository->create($newUser);

        return $newUser;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsClass($class)
    {
        return User::class === $class;
    }
}
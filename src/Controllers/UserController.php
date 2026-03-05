<?php

namespace App\Controllers;

use App\Security\User;
use App\Security\UserRepository;
use App\Kernel\AbstractController;
use App\Kernel\Connector\DatabaseException;
use App\Kernel\Connector\Hydrator;
use App\Kernel\GetEnvDatas;
use App\Kernel\Interfaces\ResponseInterface;
use App\Kernel\Responses\ErrorResponse;
use App\Kernel\utils\Serializer;

class UserController extends AbstractController
{
    private UserRepository $repo;

    public function __construct()
    {
        parent::__construct();
        $this->repo = new UserRepository();
    }

    public function get(): ResponseInterface
    {
        $id = $this->request->getData('id') ?? null;
        if ($id) {
            return $this->getOne($id);
        }
        return $this->getAll();
    }

    public function add(): ResponseInterface
    {
        $userDatas = $this->request->getAllDatas();
        $userDatas = $this->checkDatas($userDatas);
        if (!$userDatas) {
            return $this->returnError(422);
        }
        $user = Hydrator::hydrate(new User(), $userDatas);
        /**
         * @var User $user
         */
        $user->addRole('USER');
        $user->setNewPassword($user->getPassword());
        $user->setPassword(null);
        /**
         * @var User $savedUser
         */
        $savedUser = $this->repo->save($user);
        if (!$savedUser) {
            if (null === $savedUser) {
                throw new DatabaseException("User is null");
            } else {
                return $this->returnError(500);
            }
        }
        $returnArray = [
            'id' => $savedUser->getId(),
            'name' => $savedUser->getName(),
            'firstname' => $savedUser->getFirstname(),
            'role' => $savedUser->getRoles(),
            'username' => $savedUser->getUsername()
        ];
        return $this->returnJson($returnArray, 201);
    }

    public function update(): ResponseInterface
    {
        $id = $this->request->getData('id') ?? 0;
        $userDatas = $this->request->getAllDatas();
        $userDatas = $this->checkDatas($userDatas);
        if (!$userDatas) {
            return $this->returnError(422);
        }
        $user = $this->repo->find($id);
        if(null === $user) {
            return $this->returnError(404);
        }
        $user = $this->updateUser($user, $userDatas);
        $result = $this->repo->save($user);
        if ($result) {
            return $this->returnJson(null,204);
        } else {
            if (null === $result) {
                throw new DatabaseException("User is null");
            } else {
                return $this->returnError(500);
            }
        }
    }

    public function delete(): ResponseInterface
    {
        $id = $this->request->getData('id') ?? 0;
        $user = $this->repo->find($id);
        if(null === $user) {
            return $this->returnError(404);
        }
        $result = $this->repo->delete($user);
        if(!$result) {
            throw new DatabaseException('User not remove');
        }
        return $this->returnJson(null, 204);
    }

    private function getOne($id): ResponseInterface
    {
        /**
         * @var User $user
         */
        $user = $this->repo->find($id);
        if (null === $user) {
            return $this->returnError(404);
        }
        if (!($user instanceof User)) {
            $response = new ErrorResponse(500, GetEnvDatas::getEnvInstance()->get('DEBUG_MODE'));
            return $response;
        }
        $returnArray = [
            'id' => $user->getId(),
            'name' => $user->getName(),
            'firstname' => $user->getFirstName(),
            'role' => $user->getRoles()
        ];
        return $this->returnJson($returnArray);
    }

    private function getAll(): ResponseInterface
    {
        $result = $this->repo->findAll();
        $returnArray = [];
        foreach ($result as $user) {
            /**
             * @var User $user 
             */
            $serialized = Serializer::serialize($user, [User::class =>['password', 'newpassword']]);
            $returnArray[] = $serialized;//$arrayUser;
        }
        return $this->returnJson($returnArray);
    }

    private function checkDatas(array $datas): array | false
    {
        //Here, check if datas are OK
        return $datas;
        if (false) {
            return false;
        }
    }

    private function updateUser(User $user, array $datas): User
    {
        foreach ($datas as $key => $value) {
            if ($key === 'id') {
                continue;
            }
            if ($key === 'password') {
                $newFunction = 'setNewPassword';
            }
            $newFunction = 'set' . ucfirst($key);
            $user->$newFunction($value);
        }
        return $user;
    }
}

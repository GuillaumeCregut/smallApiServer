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
        if(!$userDatas) {
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
        if(!$savedUser) {
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

        return $this->returnJson();
    }

    public function delete(): ResponseInterface
    {

        return $this->returnJson();
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
            $response = new ErrorResponse(500, GetEnvDatas::getEnvInstance()->get('debug_mode'));
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
        foreach($result as $user) {
            /**
             * @var User $user 
             */
            $arrayUser = [
                'id' => $user->getId(),
                'name' => $user->getName(),
                'firstname' => $user->getFirstname(),
                'role' => $user->getRoles(),
            ];
            $returnArray[] = $arrayUser;
        } 
        return $this->returnJson($returnArray);
    }

    private function checkDatas(array $datas): array | false
    {
        //Here, check if datas are OK
        return $datas;
        if(false) {
            return false;
        }
    }
}

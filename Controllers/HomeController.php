<?php

namespace App\Controllers;

use App\Kernel\Responses\JsonResponse;
use App\Kernel\AbstractController;
use App\Kernel\Interfaces\ResponseInterface;
use App\Kernel\Responses\ClientErrorResponse;

class HomeController extends AbstractController
{

    public function __construct()
    {
        parent::__construct();
    }

    public function getDatas(): ResponseInterface
    {
        //check if we want only one data or all datas
        if($this->request->getData('id')) {
            //get one data from DB
            $datas = '';
        } else {
           //Get all datas from DB
            $datas = [];
        }
        return $this->returnJson($datas,200);
    }

    public function addData(): ResponseInterface
    {
        //Check incoming datas
        $error = false;
        $datas = $this->request->getAllDatas();
        //In case wrong datas 
        if ($error) {
            return new ClientErrorResponse(404);
        } else {
            //Add entity to DB
            $newData= [];
            return $this->returnJson($newData, 201);
        }
    }

    public function changeData(): ResponseInterface
    {
        //Check incoming datas
        $error = false;
        $datas = $this->request->getAllDatas();
        //In case wrong datas 
        if ($error) {
            return new ClientErrorResponse(422);
        } else {
            //Update Database;
            return $this->returnJson(204);
        }
    }
    public function deleteData(): ResponseInterface
    {
        //example of authentication check
        if (!$this->isUserAuth()) {
            return $this->returnError(401);
        } 
        //Do something that delete entity from database
        $response = new JsonResponse(204);
        return $response;
    }
}

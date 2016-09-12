<?php

// src/AppBundle/Controller/UploadController.php
namespace AppBundle\Controller;

// ...
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

// ...
class UploadController extends Controller
{
    /**
     * @Route("/upload")
     */
    public function uploadForm()
    {
        return $this->render('upload/index.html.twig');
    }
}

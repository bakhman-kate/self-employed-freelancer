<?php

namespace App\Controller;

use App\Service\InnValidation;
use Memcached;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{
    /**
     * @Route("/", name="index")
     */
    public function index(Request $request, InnValidation $innValidation)
    {
        $code = 200;
        $message = '';
        $inn = $request->request->get('inn');

        if (!empty($inn)) {
            if (!empty((int) $inn)) {
                if (!$innValidation->checkIndividualInn($inn)) {
                    $message = 'Неверный ИНН';
                } else {
                    $memcached = new Memcached();
                    $memcached->addServer('localhost', 11211);

                    $status = $innValidation->getTaxPayerStatus($inn, date('Y-m-d'), $memcached);
                    if (array_key_exists('code', $status)) {
                        $code = $status['code'];
                    }
                    if (array_key_exists('message', $status)) {
                        $message = $status['message'];
                    }
                }
            } else {
                $message = 'ИНН должен содержать только цифры';
            }
        }

        return $this->render('index/index.html.twig', [
            'code' => $code,
            'message' => $message,
        ]);
    }
}

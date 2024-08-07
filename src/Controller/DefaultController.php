<?php

namespace App\Controller;

use PhpParser\Node\Expr\Cast\Array_;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


use App\Service\ProductScraperService;

class DefaultController extends AbstractController
{
    private $scraperService;

    public function __construct(ProductScraperService $scraperService)
    {
        $this->scraperService = $scraperService;

    }

    #[Route('/default', name: 'app_default')]
    public function index(): Response
    {
        $products = $this->scraperService->scrapeProducts();
        $this->scraperService->exportProductsToCSV($products,'csv/products.csv');

        //dump($this->scraperService->getHtmlContent("https://www.petsathome.com/"));die;
        dump($products);die;

        return $this->render('default/index.html.twig', [
            'controller_name' => 'DefaultController',
        ]);
    }
}

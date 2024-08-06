<?php

namespace App\Controller;

use PhpParser\Node\Expr\Cast\Array_;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpClient\HttpClient;

class DefaultController extends AbstractController
{

    private $client;
    private $browser;

    public function __construct()
    {
        $this->client = HttpClient::create();
        $this->browser = new HttpBrowser(HttpClient::create());

    }

    private function scrapingScrapeMe() {
        // https://www.zenrows.com/blog/goutte-web-scraping#extract-one-element
        
        $crawler = $this->browser->request('GET', 'https://scrapeme.live/shop/');
        // get the response returned by the server
        $response = $this->browser->getResponse();
        // extract the HTML content and print it
        $html = $response->getContent();

        $products = [];

        $crawler->filter("li.product")->each(function ($productHTMLElement) use (&$products) {
            // scraping logic
            $url = $productHTMLElement->filter("a")->eq(0)->attr("href");
            $image = $productHTMLElement->filter("img")->eq(0)->attr("src");
            $name = $productHTMLElement->filter("h2")->eq(0)->text();
            $price = $productHTMLElement->filter("span")->eq(0)->text();
        
            // instantiate a new product object
            $product = [
                "url" => $url,
                "image" => $image,
                "name" => $name,
                "price" => $price
            ];
            // add it to the list
            $products[] = $product;
        });

        return $products;
    }

    private function toCSVScrapMe($products) {
        // create the output CSV file   
        $csvFile = fopen("csv/products.csv", "w");

        // write the header row
        $header = ["url", "image", "name", "price"];
        fputcsv($csvFile, $header);

        // add each product to the CSV file
        foreach ($products as $product) {
            fputcsv($csvFile, $product);
        }

        // close the CSV file
        fclose($csvFile);
    }


    #[Route('/default', name: 'app_default')]
    public function index(): Response
    {
        $products = $this->scrapingScrapeMe();
        $this->toCSVScrapMe($products);

        dump($products);die;

        return $this->render('default/index.html.twig', [
            'controller_name' => 'DefaultController',
        ]);
    }
}

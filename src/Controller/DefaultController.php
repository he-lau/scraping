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
    private $browser;

    public function __construct()
    {
        $this->browser = new HttpBrowser(HttpClient::create());

    }


    private function scrapingScrapeMe($pageCounter=1,$pageLimit=99) {
        // https://www.zenrows.com/blog/goutte-web-scraping

        // the first page to visit in the crawling logic
        $firstPageToScrape = "https://scrapeme.live/shop/page/1/";

        // the Set of pages discovered during the crawling logic
        $pagesDiscovered = [$firstPageToScrape];
        // the list of remaining pages to scrape
        $pagesToScrape = [$firstPageToScrape]; 

        $products = [];

        while (count($pagesToScrape) != 0 && $pageCounter <= $pageLimit) {
            // first elemnent of the queue
            $pageUrl = array_shift($pagesToScrape);
    
            //dump($pageUrl);
        
            // get current 
            $crawler = $this->browser->request("GET", $pageUrl);
        
            // Crawling logic to find and queue the "Next" page link
            $crawler->filter("a.page-numbers.next")->each(function ($paginationHTMLElement) use (&$pagesDiscovered, &$pagesToScrape) {
                // Extract the "Next" page URL
                $nextPageLink = $paginationHTMLElement->attr("href");
                //dump($nextPageLink);

                // Check if the next page link has already been discovered
                if (!in_array($nextPageLink, $pagesDiscovered)) {
                    // Add it to the queue to scrape and mark it as discovered
                    $pagesToScrape[] = $nextPageLink;
                    $pagesDiscovered[] = $nextPageLink;
                }
            });

        
            // scraping logic
            $crawler->filter("li.product")->each(function ($productHTMLElement) use (&$products) {
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
        
            // increment the iterator counter
            $pageCounter++;
        }

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

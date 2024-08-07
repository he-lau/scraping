<?php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\BrowserKit\HttpBrowser;

//https://www.zenrows.com/blog/goutte-web-scraping
//https://scrapeops.io/web-scraping-playbook/how-to-bypass-cloudflare/

class ProductScraperService
{
    private $browser;

    public function __construct()
    {
        $this->browser = new HttpBrowser(HttpClient::create());
    }

    public function scrapeProducts(int $pageLimit = 5): array
    {
        $firstPageToScrape = "https://scrapeme.live/shop/page/1/";
        $pagesDiscovered = [$firstPageToScrape];
        $pagesToScrape = [$firstPageToScrape]; 
        $products = [];
        $pageCounter = 1;

        // tant qu'un lien non visité & nombre de page non atteint
        while (!empty($pagesToScrape) && $pageCounter <= $pageLimit) {
            // on recupere & supprime le premier element du tableau à parcourrir
            $pageUrl = array_shift($pagesToScrape);
            // recupere le contenu de la page via HTTP GET
            $crawler = $this->browser->request("GET", $pageUrl);
            // on parcours les boutons "suivant"
            $crawler->filter("a.page-numbers.next")->each(function ($paginationHTMLElement) use (&$pagesDiscovered, &$pagesToScrape) {
                // lien courant
                $nextPageLink = $paginationHTMLElement->attr("href");
                // si pas encore parcouru, on l'ajoute à la liste
                if (!in_array($nextPageLink, $pagesDiscovered)) {
                    $pagesToScrape[] = $nextPageLink;
                    $pagesDiscovered[] = $nextPageLink;
                }
            });

            // recuperer l'ensemble des articles de lapage courante
            $crawler->filter("li.product")->each(function ($productHTMLElement) use (&$products) {

                // TODO : clicker sur chaque element pour récuperer le stock
                $linkHTMLelement = $productHTMLElement->filter("a")->eq(0); 
                $link = $linkHTMLelement->attr("href");

                echo "Scraping product page: " . $link . PHP_EOL;
                
                $productPageCrawler = $this->browser->request('GET', $link);
                $stock = $productPageCrawler->filter("p.stock")->text();


                $products[] = [
                    "url" => $link,
                    "image" => $productHTMLElement->filter("img")->eq(0)->attr("src"),
                    "name" => $productHTMLElement->filter("h2")->eq(0)->text(),
                    "price" => $productHTMLElement->filter("span")->eq(0)->text(),
                    "stock" => $stock
                ];
            });

            $pageCounter++;
        }

        return $products;
    }


    public function exportProductsToCSV(array $products, string $filePath): void
    {
        $csvFile = fopen($filePath, "w");
        fputcsv($csvFile, ["url", "image", "name", "price", "stock"]);

        foreach ($products as $product) {
            fputcsv($csvFile, $product);
        }

        fclose($csvFile);
    }

    public function getHtmlContent($uri) {
        $browser = new HttpBrowser(HttpClient::create());
        //$crawler = $browser->request("GET", $uri);
        $response = $browser->getResponse();
        $html = $response->getContent();
        return $html;
    }
}

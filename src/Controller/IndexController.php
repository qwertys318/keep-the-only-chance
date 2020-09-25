<?php namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class IndexController extends AbstractController
{
    private string $siteName;
    private string $domain;

    public function __construct(
        string $siteName,
        string $domain
    )
    {
        $this->siteName = $siteName;
        $this->domain = $domain;
    }

    public function index()
    {
        $jsConfig = [
            'siteName' => $this->siteName,
            'domain' => $this->domain,
            'year' => date('Y'),
        ];
        $data = [
            'jsConfig' => addslashes(json_encode($jsConfig, JSON_HEX_QUOT | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE)),
            'year' => date('Y'),
            'appName' => 'site',
            'pageName' => null,
            'frontDevPort' => '8091',
            'styles' => [
                'https://fonts.googleapis.com/css?family=Roboto:100,300,400,500,700,900',
                'https://cdn.jsdelivr.net/npm/@mdi/font@latest/css/materialdesignicons.min.css',
            ],
            'scripts' => [],
        ];

        return $this->render('app.html.twig', $data);
    }
}

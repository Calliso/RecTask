<?php

namespace App\Controller;

use DateTime;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class IndexController extends AbstractController
{
    private const BASE_CURRENCY = 'PLN';
    private const ALLOWED_CURRENCIES = ["EUR", "USD", "GBP", "CZK"];


    public function __construct(
        private HttpClientInterface $httpClient
    ) {
    }

    #[Route('/', name: 'home')]
    public function index(Request $request): Response
    {
        $form = $this->createApiForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $date = $form->getData()['date'];
            $returnArr = [];
            $returnArr['now'] = json_decode($this->getDataFromApi(), true);
            $returnArr['selectedDate'] = json_decode($this->getDataFromApi($date), true);
            $returnArr['difference'] = $this->calculateDiff($returnArr);
            if(!$this->validateData($returnArr)){
                return new JsonResponse(json_encode(['messege' => 'Błąd API']), Response::HTTP_BAD_REQUEST);
            }
            return new JsonResponse(json_encode($returnArr), Response::HTTP_OK);
        }
        return $this->render('index/index.html.twig', [
            'controller_name' => 'IndexController',
            'form' => $form->createView()
        ]);
    }


    /**
     *
     * Tworzy formularz wysyłki zapytania do API
     * @return Form
     */
    private function createApiForm(): Form
    {
        return $this->createFormBuilder()
            ->add('date', DateType::class, [
                'widget' => 'single_text',
                'required' => true
                ])
            ->getForm();
    }

    /**
     * Wysyła zapytanie do API i odpowiednio obsługuje odpowiedź
     * @param DateTime|null $date
     * @return string
     */
    private function getDataFromApi(?DateTime $date = null): string
    {
        $date = is_null($date) ? new DateTime() : $date;
        $date = $date->format('Y-m-d');
        $linkRoute = "https://api.frankfurter.app/$date";
        $response = $this->httpClient->request(
            'GET',
            $linkRoute,
            [
                'query' => [
                    'base' => self::BASE_CURRENCY,
                    'symbols' => implode(',', self::ALLOWED_CURRENCIES)
                ]
            ]
        );
        $statusCode = $response->getStatusCode();

        // jakiś error listener 
        if($statusCode != Response::HTTP_OK){
            $this->handleResponse($statusCode);
        }
        return $response->getContent();
    }

    /**
     * Oblicza procentową różnice (wybrana data/dzisiaj)
     * @param array $data
     * @return array
     */
    private function calculateDiff(array $data): array
    {
        $differenceArray = [];
        foreach($data['now']['rates'] as $currency => $value){
            $differenceArray[$currency] = ($data['selectedDate']['rates'][$currency]/$value) -1;
        }

        return $differenceArray;
    }

    /**
     * Undocumented function
     *
     * @param [type] $data
     * @return boolean
     */
    private function validateData($data):bool
    {
        if(!in_array('rates', $this->array_keys_r($data))){
            return false;
        }
        return true;
    }

    /**
     * Customowe errory
     * @param integer $statusCode
     * @return void
     */
    private function handleResponse(int $statusCode){
        switch($statusCode){
            case Response::HTTP_INTERNAL_SERVER_ERROR:
                throw new Exception('blad serwera', $statusCode);
            //case .......
        }
    }

    public function array_keys_r($array) {
        $keys = array_keys($array);
      
        foreach ($array as $i)
          if (is_array($i))
            $keys = array_merge($keys, $this->array_keys_r($i));
      
        return $keys;
      }
}

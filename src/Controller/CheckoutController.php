<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class CheckoutController extends AbstractController
{
    #[Route('/checkout', name: 'app_checkout')]
    public function index(): Response
    {
        $tokenProvider = $this->container->get('security.csrf.token_manager');

        // Symfony émet un nouveau jeton dont l'id est `stripe_token`
        $token = $tokenProvider->getToken('stripe_token')->getValue();

        // La clef d'API ne dit naturellement pas être “en dur” dans le code
        // Une bonne idée serait de la mettre dans le fichier `.env` et de la chiffrer avec `Secrets`
        \Stripe\Stripe::setApiKey($this->getParameter('stripe_sk'));

        // Les éléments du panier
        $item_1 = [
            'price_data' => [
                'currency' => 'eur',
                'product_data' => [
                'name' => 'T-shirt',
                ],
                'unit_amount' => 2000,
            ],
            'quantity' => 1,
            ];
        $item_2 = [
            'price_data' => [
            'currency' => 'eur',
            'product_data' => [
                'name' => 'Confiture',
            ],
            'unit_amount' => 3000,
            ],
            'quantity' => 2,
        ];

        // Création de la session Stripe
        $session = \Stripe\Checkout\Session::create([
            'line_items' => [$item_1, $item_2],
            'mode' => 'payment',
            'success_url' => 'https://localhost:8000/checkout_success/'.$token,
            'cancel_url' => 'https://localhost:8000/checkout_error'
        ]);

        // Redirection vers la passerelle de paiement
        // Le code de statut 303 indique que notre serveur ne possède pas de représentation de la ressource demandée et qu'il redirige l'utilisateur vers un URL adéquat.         
        return $this->redirect($session->url, 303);

        return $this->render('checkout/index.html.twig', [
            'controller_name' => 'CheckoutController',
        ]);
    }

    
    #[Route('/checkout_success/{token}', name: 'app_checkout_success')]
    public function checkout_success(string $token): Response
    {
        // Symfony vérifie que le jeton correspond bien
        // à celui qui a été émis dans la requête initiale 
        if ($this->isCsrfTokenValid('stripe_token', $token)) {
            return $this->render('checkout/success.html.twig', [
                'controller_name' => 'CheckoutController',
            ]);
        } else {
            //dd('BAD stripe_token');
            return $this->redirectToRoute('app_checkout_error', [], Response::HTTP_SEE_OTHER);
        }
        
    }
    #[Route('/checkout_error', name: 'app_checkout_error')]
    public function checkout_error(): Response
    {
        return $this->render('checkout/error.html.twig', [
            'controller_name' => 'CheckoutController',
        ]);
    }
}

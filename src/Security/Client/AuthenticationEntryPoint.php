<?php
namespace App\Security\Client;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class AuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    private $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public function start(Request $request, AuthenticationException $authException = null): RedirectResponse
    {
        // add a custom flash message and redirect to the login page
        //$request->getSession()->getFlashBag()->add('note', 'You have to login in order to access this page.');
        //$session = $request->get('session');
        //$session->getFlashBag()->add('note', 'You have to login in order to access this page.');
        /*
        $this->flashBag->add(
            'note',
            'You have to login in order to access this page.'
        );
        */
        return new RedirectResponse($this->urlGenerator->generate('app_client_login'));
    }
}
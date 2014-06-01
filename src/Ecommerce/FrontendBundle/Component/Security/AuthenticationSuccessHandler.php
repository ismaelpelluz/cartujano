<?php
namespace Ecommerce\FrontendBundle\Component\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Doctrine\ORM\EntityManager;

/**
 * Custom authentication success handler
 */
class AuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    private $router;

    /**
     * Constructor
     * @param RouterInterface   $router
     * @param EntityManager     $em
     */
    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * This is called when an interactive authentication attempt succeeds. This
     * is called by authentication listeners inheriting from AbstractAuthenticationListener.
     * @param Request        $request
     * @param TokenInterface $token
     *
     * @return Response The response to return
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {
        if (in_array('ROLE_USER', $token->getUser()->getRoles())) {
            $user = $token->getUser();

            if ($user->getValidated()) {
                $uri = $this->router->generate('user_profile');
            } else {
                $uri = $this->router->generate('validate_user');
            }
        }

        return new RedirectResponse($uri);
    }
}
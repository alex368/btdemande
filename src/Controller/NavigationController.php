<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class NavigationController extends AbstractController
{
    #[Route('/back', name: 'app_back', methods: ['GET'])]
    public function back(Request $request): RedirectResponse
    {
        $fallback = $this->isGranted('IS_AUTHENTICATED_FULLY')
            ? $this->generateUrl('app_dashboard')
            : $this->generateUrl('app_login');

        if (!$request->hasSession()) {
            return $this->redirect($fallback);
        }

        $previousUri = (string) $request->getSession()->get('nav.previous_uri', '');
        if ($previousUri === '') {
            return $this->redirect($fallback);
        }

        // Security: only allow internal relative URLs
        if (str_starts_with($previousUri, 'http://') || str_starts_with($previousUri, 'https://') || str_starts_with($previousUri, '//')) {
            return $this->redirect($fallback);
        }

        return $this->redirect($previousUri);
    }
}


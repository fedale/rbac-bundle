<?php

namespace Fedale\RbacBundle\Security;

use Fedale\RbacBundle\Contract\AccessManagerInterface;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * Convenience for controllers (services with autowiring): adds a can() method
 * that delegates to AccessManagerInterface, to sit alongside isGranted().
 *
 *   class InvoiceController extends AbstractController
 *   {
 *       use CanTrait;
 *
 *       public function edit(Invoice $invoice): Response
 *       {
 *           if (!$this->can('EDIT_INVOICE', $invoice)) {
 *               throw $this->createAccessDeniedException();
 *           }
 *           // ...
 *       }
 *   }
 */
trait CanTrait
{
    private ?AccessManagerInterface $accessManager = null;

    #[Required]
    public function setAccessManager(AccessManagerInterface $accessManager): void
    {
        $this->accessManager = $accessManager;
    }

    protected function can(string $item, mixed $subject = null): bool
    {
        if (null === $this->accessManager) {
            throw new \LogicException(
                'AccessManager not injected: the controller must be a service with autowiring enabled.'
            );
        }

        return $this->accessManager->can($item, $subject);
    }
}

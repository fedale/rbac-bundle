<?php

namespace Fedale\RbacBundle\Twig;

use Fedale\RbacBundle\Contract\AccessManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Exposes the RBAC decision in templates via the `can()` function, the view-layer
 * counterpart of CanTrait::can(). Registered only when Twig is installed.
 *
 *   {% if can('EDIT_INVOICE', invoice) %} ... {% endif %}
 *
 * Native `is_granted('ROLE_X')` still covers plain role checks; `can()` adds the
 * permission / contextual-rule check (and also works on roles).
 */
final class RbacExtension extends AbstractExtension
{
    public function __construct(
        private readonly AccessManagerInterface $accessManager,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('can', $this->can(...)),
        ];
    }

    public function can(string $item, mixed $subject = null): bool
    {
        return $this->accessManager->can($item, $subject);
    }
}

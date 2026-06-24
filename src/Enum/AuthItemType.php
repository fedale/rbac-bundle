<?php

namespace Fedale\RbacBundle\Enum;

/**
 * Auth item type, faithful to Yii2 RBAC's `type` field (where it is, however,
 * an integer 1/2). Here we use a string-backed enum for DB readability.
 *
 *   - ROLE       -> Role (e.g. ROLE_ADMIN), also evaluated by isGranted().
 *   - PERMISSION -> Permission (e.g. EDIT_INVOICE), evaluated by can()/#[IsGranted].
 */
enum AuthItemType: string
{
    case ROLE = 'role';
    case PERMISSION = 'permission';
}

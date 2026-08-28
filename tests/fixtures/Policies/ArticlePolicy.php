<?php

namespace Kreetancraft\UserManagement\Tests\Fixtures\Policies;

/**
 * A stand-in for a HOST application policy.
 *
 * sync-permissions discovers policies by scanning configured directories, so
 * the suite needs one that is not the package's own.
 */
class ArticlePolicy
{
    public function viewAny($user): bool
    {
        return true;
    }

    public function create($user): bool
    {
        return true;
    }

    public function delete($user, $article): bool
    {
        return true;
    }

    /** Not in the configured method list, so it must NOT produce a permission. */
    public function publish($user, $article): bool
    {
        return true;
    }
}

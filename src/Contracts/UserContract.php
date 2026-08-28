<?php

namespace Kreetancraft\UserManagement\Contracts;

/**
 * Both sides of user persistence.
 *
 * Depend on this only where a class genuinely needs to read *and* write —
 * completing an invitation, for example. Everywhere else prefer the narrower
 * ManagesUsers or QueriesUsers.
 */
interface UserContract extends ManagesUsers, QueriesUsers {}

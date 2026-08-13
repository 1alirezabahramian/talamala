<?php

declare(strict_types=1);

namespace Talamala\Domain\Identity;

/**
 * Separate from Customer Level and from onboarding state.
 */
enum CustomerAccessStatus: string
{
    case Active = 'active';
    case Limited = 'limited';
    case Suspended = 'suspended';
    case Blocked = 'blocked';
}

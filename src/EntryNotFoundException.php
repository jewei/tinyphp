<?php

declare(strict_types=1);

namespace TinyPHP;

use Exception;
use Psr\Container\NotFoundExceptionInterface;

final class EntryNotFoundException extends Exception implements NotFoundExceptionInterface
{
}

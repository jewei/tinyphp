<?php

declare(strict_types=1);

namespace TinyPHP;

use Exception;
use Psr\Container\ContainerExceptionInterface;

final class InvalidEntryException extends Exception implements ContainerExceptionInterface
{
}

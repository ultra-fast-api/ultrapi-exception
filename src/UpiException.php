<?php

/**
 * UpiException class
 * 
 * This class is a part of the UpiCore project.
 * It is responsible for handling custom exceptions in the application.
 * It extends the base Exception class and adds localization support
 * and custom exception handling.
 * 
 * @author Gokhan Korul <me@gokhankorul.dev>
 * @package UpiCore\Exception
 */

declare(strict_types=1);

namespace UpiCore\Exception;

class UpiException extends \Exception
{
    public function __construct(string $key, ?string ...$args)
    {
        $lang = new \UpiCore\Localization\Language();

        $pureText = $lang->getPureText($key, ...$args);

        list($status, $message) = \UpiCore\Localization\Message\HTTPMessage::parseTextMessage($pureText);

        $this->code = $status ?: 503;
        $this->message = $message;

        self::exceptionHandler();
    }

    public static function exceptionHandler(\Closure $exceptionHandler = null)
    {
        $handler = function (\Throwable $exception) use ($exceptionHandler) {
            if (!($exception instanceof \UpiCore\Exception\UpiException)) {
                $exceptionHandler($exception);
            } else {
                $exception->__toString();
            }
        };

        set_exception_handler($handler);
    }

    public function __toString(): string
    {
        $routerContext = new \UpiCore\Router\UpiRouterContext();

        return $routerContext->create()
            ->withContent(\UpiCore\Router\UpiRouter::getInterpretation())
            ->withStatus($this->code)
            ->withMessage($this->message)
            ->toResponse();
    }

    /**
     * Prepare all messages
     *
     * @return array
     */
    public function prepareMessage(): array
    {
        $toStringExp = [
            "errMessage" => $this->message,
        ];

        if (\ERROR_DETAILS)
            $toStringExp['errDetails'] = $this->errorDetails();

        return $toStringExp;
    }

    public function getDetails()
    {
        return $this->getFileName() . ': ' . $this->getLine();
    }

    public function getFileName()
    {
        $parse = explode(DIRECTORY_SEPARATOR, $this->getFile());
        return explode('.', end($parse))[0];
    }

    public function errorDetails()
    {
        return [
            "Stack" => $this->getTraceAsString(),
            "Trace" => $this->getDetails()
        ];
    }
}

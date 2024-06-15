<?php

/**
 * UpiException class
 * 
 * This class is a part of the UpiCore project.
 * It is responsible for handling custom exceptions in the application.
 * It extends the base Exception class and adds localization support
 * and custom exception handling.
 * 
 * @package UpiCore\Exception
 * @license MIT License
 * @author Gokhan Korul <me@gokhankorul.dev>
 * @link https://ultrapi.dev
 */

declare(strict_types=1);

namespace UpiCore\Exception;

class UpiException extends \Exception
{
    /**
     * Custom exception handler
     *
     * @var \Closure|null
     */
    private static $exceptionHandler = null;

    public function __construct(string $key, ?string ...$args)
    {
        $lang = new \UpiCore\Localization\Language();

        $pureText = $lang->getPureText($key, ...$args);

        list($status, $message) = \UpiCore\Localization\Message\HTTPMessage::parseTextMessage($pureText);

        $this->code = $status ?: 503;
        $this->message = $message;

        null !== self::$exceptionHandler ?: self::exceptionHandler();
    }

    public static function exceptionHandler(\Closure $exceptionHandler = null)
    {
        $handler = function (\Throwable $exception) use ($exceptionHandler) {
            if (!($exception instanceof \UpiCore\Exception\UpiException)) {
                $exceptionHandler($exception, self::class);
            } else {
                $exception->returnResult();
            }
        };

        \set_exception_handler($handler);
    }

    public static function setExceptionHandler(\Closure $handler): void
    {
        self::exceptionHandler(self::$exceptionHandler = $handler);
    }

    public function returnResult(): void
    {
        $routerContext = new \UpiCore\Router\RouterContext();

        $routerContext->createResponse()
            ->withContent(\UpiCore\Router\Router::getInterpretation())
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

        $errorDetails = !\defined('ERROR_DETAILS') ? 0 : ERROR_DETAILS;

        if ($errorDetails)
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

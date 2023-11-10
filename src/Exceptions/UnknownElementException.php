<?php

namespace Hexafuchs\LaminasSecurity\Exceptions;

use Exception;
use Throwable;

class UnknownElementException extends Exception
{
    /**
     * Name of the element (singular)
     */
    protected const ELEMENT_NAME_SINGULAR = 'element';

    /**
     * Name of the element (plural)
     */
    protected const ELEMENT_NAME_PLURAL = 'elements';

    /**
     * @var string $requestedElement
     */
    protected string $requestedElement;

    /**
     * @var string[] $knownElements
     */
    protected array $knownElements;

    /**
     * @param string $requestedElement
     * @param string[] $knownElements
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(
        string     $requestedElement,
        array      $knownElements,
        int        $code = 0,
        ?Throwable $previous = null
    )
    {
        $this->requestedElement = $requestedElement;
        $this->knownElements    = $knownElements;

        parent::__construct($this->buildMessage(), $code, $previous);
    }

    /**
     * Builds a message based on the requested and the known elements
     *
     * @return string
     */
    protected function buildMessage(): string
    {
        $knownElementsCount = count($this->knownElements);

        if ($knownElementsCount === 0) {
            return sprintf(
                'Unknown %1$s "%2$s" requested.',
                $this::ELEMENT_NAME_SINGULAR,
                $this->requestedElement
            );
        } elseif ($knownElementsCount === 1) {
            return sprintf(
                'Unknown %1$s "%2$s" requested. Available %1$s: %3$s',
                $this::ELEMENT_NAME_SINGULAR,
                $this->requestedElement,
                $this->joinElements()
            );
        } else {
            return sprintf(
                'Unknown %1$s "%3$s" requested. Available %2$s: %4$s',
                $this::ELEMENT_NAME_SINGULAR,
                $this::ELEMENT_NAME_PLURAL,
                $this->requestedElement,
                $this->joinElements()
            );
        }
    }

    /**
     * Joins multiple elements using natural language.
     *
     * @return string
     */
    protected function joinElements(): string
    {
        $availableElements = array_map(fn(string $element) => '"' . $element . '"', $this->knownElements);
        $finalItem         = array_pop($availableElements);

        return sprintf('%s and %s', implode(', ', $availableElements), $finalItem);
    }

    /**
     * Returns the requested element
     *
     * @return string
     */
    public function getRequestedElement(): string
    {
        return $this->requestedElement;
    }

    /**
     * Returns a list of all known elements
     *
     * @return string[]
     */
    public function getKnownElements(): array
    {
        return $this->knownElements;
    }
}
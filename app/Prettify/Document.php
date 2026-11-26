<?php

namespace Forage\Prettify;

use Dom\HTMLDocument;

class Document
{
    /**
     * The HTML5 document instance.
     */
    protected HTMLDocument $document;

    /**
     * Initialize the Document instance.
     */
    final public function __construct(string $html)
    {
        $this->document = HTMLDocument::createFromString('<!doctype html><body>' . $html, \LIBXML_NOERROR, 'UTF-8');
    }

    /**
     * Make a new instance of the Document.
     */
    public static function make(string $html): self
    {
        return new static($html);
    }

    /**
     * Loop through each element in the fragment and execute the provided callback.
     */
    public function each(callable $callback): self
    {
        foreach ($this->document->body->querySelectorAll('*') as $node) {
            $callback($node);
        }

        return $this;
    }

    /**
     * Get the serialized fragment HTML.
     */
    public function html(): string
    {
        return trim($this->document->body->innerHTML);
    }

    /**
     * Call the given method on the root document.
     */
    public function __call(string $name, array $arguments): mixed
    {
        return $this->document->{$name}(...$arguments);
    }

    /**
     * Get the given property from the root document.
     */
    public function __get(string $name): mixed
    {
        return $this->document->{$name};
    }
}

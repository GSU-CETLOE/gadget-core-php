<?php

declare(strict_types=1);

namespace Gadget\Io;

use Gadget\Exception\DOMException;

final class DOM
{
    /** @var array<string,string> */
    private const CLEAN_STRING_REGEX = [
        '/[ \t\n\r\x{000b}\x{0080}\x{0093}\x{00a0}\x{00c2}\x{00e2}]+/u' => " ",
        '/[\x{2013}]+/u' => "-",
        '/[\x{2018}\x{2019}]+/u' => "'",
        '/[\x{201c}\x{201d}]+/u' => "\""
    ];


    /**
     * @param non-empty-string $contents
     * @return \DOMDocument
     */
    public static function loadHTML(string $contents): \DOMDocument
    {
        $document = new \DOMDocument();
        return (@$document->loadHTML($contents) === true)
            ? $document
            : throw new DOMException("Unable to load HTML");
    }


    /**
     * @param \DOMDocument $document
     * @param string $id
     * @return \DOMNode
     */
    public static function getElementById(
        \DOMDocument $document,
        string $id
    ): \DOMNode {
        $node = $document->getElementById($id);
        return ($node instanceof \DOMNode)
            ? $node
            : throw new DOMException(["Element not found: %s", [$id]]);
    }


    /**
     * @param \DOMNode $node
     * @param int|null $nodeType
     * @return \Generator<\DOMNode>
     */
    public static function childNodes(
        \DOMNode $node,
        int|null $nodeType = XML_ELEMENT_NODE
    ): iterable {
        for ($idx = 0; $idx < $node->childNodes->length; $idx++) {
            $item = $node->childNodes->item($idx);
            if ($item instanceof \DOMNode && $item->nodeType === ($nodeType ?? $item->nodeType)) {
                yield $item;
            }
        }
    }


    /**
     * @param \DOMNode $node
     * @return \Generator<string,\DOMAttr>
     */
    public static function nodeAttributes(\DOMNode $node): iterable
    {
        if ($node->attributes instanceof \DOMNamedNodeMap) {
            foreach ($node->attributes as $name => $attr) {
                if (is_string($name)) {
                    yield $name => $attr;
                }
            }
        }
    }


    /**
     * @param \DOMNode $node
     * @param string $name
     * @param bool $recursive
     * @return \DOMNode[]
     */
    public static function findChildrenByName(
        \DOMNode $node,
        string $name,
        bool $recursive = false
    ): array {
        $children = [];
        foreach (self::childNodes($node) as $item) {
            if ($item->nodeName === $name) {
                $children[] = $item;
            }

            if ($recursive) {
                array_push($children, ...self::findChildrenByName($item, $name));
            }
        }

        return $children;
    }


    /**
     * @param \DOMNode $node
     * @param string $name
     * @return \DOMNode
     */
    public static function findChildByName(
        \DOMNode $node,
        string $name
    ): \DOMNode {
        $child = self::findChildrenByName($node, $name)[0] ?? null;
        return $child !== null
            ? $child
            : throw new DOMException(["Element not found: %s", [$name]]);
    }


    /**
     * @param \DOMNode|string|null $value
     * @return string
     */
    public static function cleanNodeValue(\DOMNode|string|null $value): string
    {
        $nodeValue = trim(preg_replace(
            array_keys(self::CLEAN_STRING_REGEX),
            array_values(self::CLEAN_STRING_REGEX),
            ($value instanceof \DOMNode ? $value->nodeValue : $value) ?? ''
        ) ?? '');

        if ($value instanceof \DOMNode) {
            $value->nodeValue = '';
            foreach (self::childNodes($value, null) as $item) {
                $value->removeChild($item);
            }

            /** @var \DOMDocument $ownerDocument */
            $ownerDocument = $value->ownerDocument;
            $value->appendChild($ownerDocument->createTextNode($nodeValue));
        }

        return $nodeValue;
    }
}

<?php

declare(strict_types=1);

namespace Gadget\Tests\Io;

use Gadget\Exception\DOMException;
use Gadget\Io\DOM;
use PHPUnit\Framework\TestCase;

final class DOMTest extends TestCase
{
    private const HTML = '<html><body><div id="main" class="box" data-x="1">'
        . '<p>One</p>text<span><p>Two</p></span></div></body></html>';


    /** @return \DOMNode the div with id "main" from the fixture document */
    private static function mainDiv(): \DOMNode
    {
        return DOM::getElementById(DOM::loadHTML(self::HTML), 'main');
    }


    public function testLoadHTMLParsesDocument(): void
    {
        $document = DOM::loadHTML(self::HTML);

        self::assertSame(1, $document->getElementsByTagName('body')->length);
    }


    public function testGetElementByIdThrowsWhenMissing(): void
    {
        $this->expectException(DOMException::class);
        $this->expectExceptionMessage('Element not found: nope');

        DOM::getElementById(DOM::loadHTML(self::HTML), 'nope');
    }


    public function testChildNodesFiltersByNodeType(): void
    {
        $main = self::mainDiv();

        self::assertSame(['p', 'span'], self::nodeNames(DOM::childNodes($main)));
        self::assertSame(['p', '#text', 'span'], self::nodeNames(DOM::childNodes($main, null)));
        self::assertSame(['#text'], self::nodeNames(DOM::childNodes($main, XML_TEXT_NODE)));
    }


    public function testNodeAttributesYieldsNameToAttr(): void
    {
        $attributes = iterator_to_array(DOM::nodeAttributes(self::mainDiv()));

        self::assertSame(['id', 'class', 'data-x'], array_keys($attributes));
        self::assertContainsOnlyInstancesOf(\DOMAttr::class, $attributes);
        self::assertSame('box', $attributes['class']->value);
    }


    public function testNodeAttributesOnTextNodeYieldsNothing(): void
    {
        $text = DOM::childNodes(self::mainDiv(), XML_TEXT_NODE);

        self::assertSame([], iterator_to_array(DOM::nodeAttributes(iterator_to_array($text, false)[0])));
    }


    public function testFindChildrenByName(): void
    {
        $main = self::mainDiv();

        self::assertSame(['One'], self::textValues(DOM::findChildrenByName($main, 'p')));
        self::assertSame(['One', 'Two'], self::textValues(DOM::findChildrenByName($main, 'p', true)));
        self::assertSame([], DOM::findChildrenByName($main, 'table', true));
    }


    public function testFindChildByNameReturnsFirstMatch(): void
    {
        self::assertSame('One', DOM::findChildByName(self::mainDiv(), 'p')->textContent);
    }


    public function testFindChildByNameThrowsWhenMissing(): void
    {
        $this->expectException(DOMException::class);
        $this->expectExceptionMessage('Element not found: table');

        DOM::findChildByName(self::mainDiv(), 'table');
    }


    public function testCleanNodeValueNormalizesStrings(): void
    {
        $dirty = "  \u{201c}Hi\u{201d}\u{00a0}there \u{2013} it\u{2019}s\t\nfine  ";

        self::assertSame("\"Hi\" there - it's fine", DOM::cleanNodeValue($dirty));
        self::assertSame('', DOM::cleanNodeValue(null));
    }


    public function testCleanNodeValueReplacesNodeContentWithCleanText(): void
    {
        $main = self::mainDiv();

        self::assertSame('OnetextTwo', DOM::cleanNodeValue($main));
        self::assertSame(['#text'], self::nodeNames(DOM::childNodes($main, null)));
        self::assertSame('OnetextTwo', $main->textContent);
    }


    /**
     * @param iterable<\DOMNode> $nodes
     * @return list<string>
     */
    private static function nodeNames(iterable $nodes): array
    {
        $names = [];
        foreach ($nodes as $node) {
            $names[] = $node->nodeName;
        }
        return $names;
    }


    /**
     * @param iterable<\DOMNode> $nodes
     * @return list<string>
     */
    private static function textValues(iterable $nodes): array
    {
        $values = [];
        foreach ($nodes as $node) {
            $values[] = $node->textContent;
        }
        return $values;
    }
}

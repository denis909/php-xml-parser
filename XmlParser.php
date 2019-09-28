<?php

namespace denis909\XmlParser;

use Exception;
use DOMDocument;

abstract class XmlParser
{

    public $count;

    protected function parseNode($element, $currentPath, &$node_errors = [])
    {
        if (!strpos($currentPath, '[') && !strpos($currentPath, ']'))
        {
            $this->debug('path: ' . $currentPath);
        }

        if ($element->nodeType == XML_TEXT_NODE) // 3
        {
            return;
        }

        $childs = $element->childNodes;

        if (!$childs)
        {
            return;
        }

        for ($i = 0; $i < $childs->length; $i++)
        {
            $child = $childs->item($i);

            if ($child->nodeName != 'item')
            {
                continue;
            }

            foreach($this->config as $path => $callback)
            {
                if ($path == $currentPath)
                {
                    if ($child->nodeType != XML_TEXT_NODE) // 3
                    {
                        $this->$callback($child, $node_errors);
                    }
                }
            }

            $childPath = (string) $child->getNodePath();

            $this->parseNode($child, $childPath, $node_errors);
        }
    }

    public function loadFile(string $filename, &$error = null)
    {
        if (!is_file($filename))
        {
            throw new Exception('File not found: ' . $filename);
        }

        $dom = new DOMDocument("1.0", "utf-8");

        if ($dom->load($filename) === false)
        {
            $error = 'DOM error.';

            return false;
        }

        return $dom;
    }

    public function parse($dom, &$error = null, &$node_errors = null)
    {
        $this->count = 0;

        $root = $dom->documentElement;

        if ($root->nodeName == 'error')
        {
            $error = $root->nodeValue;

            return false;
        }

        $path = (string) $root->getNodePath();

        $this->parseNode($root, $path, $node_errors);

        return true;
    }

    public function parseFile(string $filename, &$error = null, &$node_errors = null)
    {
        $dom = $this->loadFile($filename, $error);

        if (!$dom)
        {
            return false;
        }

        if (!$this->parse($dom, $error, $node_errors))
        {
            return false;
        }

        return true;
    }

    protected function tagValue($element, string $tagName)
    {
        $elements = $element->getElementsByTagName($tagName);

        if ($elements->length > 1)
        {
            throw new Exception('Can\'t get node value from multiple element: ' . $tagName);
        }

        if ($elements->length > 0)
        {
            $el = $elements->item(0);

            return $el->nodeValue;
        }

        return null;
    }

}
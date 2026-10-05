<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMNode;

final class HtmlSanitizer
{
    private const TAGS = ['p', 'br', 'strong', 'em', 'ul', 'ol', 'li', 'h2', 'h3', 'h4', 'blockquote', 'a', 'table', 'thead', 'tbody', 'tr', 'th', 'td', 'pre', 'code', 'div', 'span', 'img', 'figure', 'figcaption', 'sub', 'sup'];

    public function clean(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return $html;
        }
        $document = new DOMDocument('1.0', 'UTF-8');
        @$document->loadHTML('<?xml encoding="utf-8" ?><div>'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $root = $document->getElementsByTagName('div')->item(0);
        if (! $root) {
            return '';
        }
        $this->sanitizeChildren($root);

        return implode('', array_map(fn (DOMNode $node) => $document->saveHTML($node), iterator_to_array($root->childNodes)));
    }

    private function sanitizeChildren(DOMNode $parent): void
    {
        foreach (iterator_to_array($parent->childNodes) as $node) {
            if ($node instanceof DOMElement) {
                if (! in_array(strtolower($node->tagName), self::TAGS, true)) {
                    while ($node->firstChild) {
                        $parent->insertBefore($node->firstChild, $node);
                    }
                    $parent->removeChild($node);

                    continue;
                }
                foreach (iterator_to_array($node->attributes) as $attribute) {
                    $allowed = ($node->tagName === 'a' && in_array($attribute->name, ['href', 'title'], true))
                        || ($node->tagName === 'img' && in_array($attribute->name, ['src', 'alt', 'title'], true))
                        || (in_array($node->tagName, ['h2', 'h3'], true) && $attribute->name === 'id')
                        || (in_array($node->tagName, ['div', 'span'], true) && $attribute->name === 'class' && in_array($attribute->value, ['formula-block', 'article-callout'], true));
                    if (! $allowed) {
                        $node->removeAttribute($attribute->name);
                    }
                }
                if ($node->tagName === 'a') {
                    $href = $node->getAttribute('href');
                    if (! str_starts_with($href, '/') && ! filter_var($href, FILTER_VALIDATE_URL)) {
                        $node->removeAttribute('href');
                    }
                    if (preg_match('/^(javascript|data):/i', $href)) {
                        $node->removeAttribute('href');
                    }
                }
                if ($node->tagName === 'img') {
                    $src = $node->getAttribute('src');
                    if (! str_starts_with($src, '/') && ! filter_var($src, FILTER_VALIDATE_URL)) {
                        $node->removeAttribute('src');
                    }
                    if (preg_match('/^(javascript|data):/i', $src)) {
                        $node->removeAttribute('src');
                    }
                }
            }
            $this->sanitizeChildren($node);
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Parsedown;


class Parsedown extends \ParsedownExtra {

    private $mentionProcessor;

    private $linkProcessor;

    private $imageProcessor;
    
    
    private $mentions = [];
    
    private $links = [];
    
    private $images = [];


    public function __construct() {
        parent::__construct();

        $this->setSafeMode(true);
        $this->InlineTypes['@'][] = 'Mention';
        $this->inlineMarkerList .= '@';
    }


    public function setMentionProcessor(?callable $processor) : void {
        $this->mentionProcessor = $processor;
    }

    public function setLinkProcessor(?callable $processor) : void {
        $this->linkProcessor = $processor;
    }

    public function setImageProcessor(?callable $processor) : void {
        $this->imageProcessor = $processor;
    }
    
    
    public function getParsedMentions() : array {
        return $this->mentions;
    }
    
    public function getParsedLinks() : array {
        return $this->links;
    }
    
    public function getParsedImages() : array {
        return $this->images;
    }
    
    
    public function text($text) {
        $this->mentions = [];
        $this->links = [];
        $this->images = [];
        return parent::text($text);
    }
    


    protected function inlineMention($Excerpt) {
        if (!isset($this->mentionProcessor)) {
            return null;
        }

        if (preg_match('/(?<!\S)@([a-z0-9]+(?:[._]+[a-z0-9]+)*)/i', $Excerpt['context'], $m)) {
            $info = call_user_func($this->mentionProcessor, $m[1]);

            if (!$info) {
                return null;
            } else if (!is_array($info)) {
                $info = ['href' => $info];
            }
            
            $this->mentions[] = $m[0];

            return [
                'extent' => strlen($m[0]),
                'element' => [
                    'name' => 'a',
                    'text' => $m[0],
                    'attributes' => $info,
                ],
            ];
        }

        return null;
    }

    protected function inlineLink($Excerpt) {
        $link = parent::inlineLink($Excerpt);

        if (isset($this->linkProcessor) && is_array($link)) {
            $link = call_user_func($this->linkProcessor, $link);
        }

        if (is_array($link) && isset($link['element']['attributes']['href'])) {
            $this->links[] = $link['element']['attributes']['href'];
        }

        return $link;
    }

    protected function inlineImage($Excerpt) {
        $image = parent::inlineImage($Excerpt);

        if (isset($this->imageProcessor) && is_array($image)) {
            $image = call_user_func($this->imageProcessor, $image);
        }

        if (is_array($image) && isset($image['element']['attributes']['src'])) {
            $this->images[] = $image['element']['attributes']['src'];
        }

        return $image;
    }

}

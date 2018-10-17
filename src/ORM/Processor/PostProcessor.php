<?php

declare(strict_types=1);

namespace App\ORM\Processor;

use App\Entity\Topic;
use App\Entity\User;
use App\ORM\Manager\TopicSubscriptionManager;
use App\ORM\Manager\UserManager;
use App\Parsedown\Parsedown;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use Nette\Application\LinkGenerator;


class PostProcessor {

    private $userManager;

    private $topicSubscriptionManager;

    private $parsedown;

    private $httpClient;

    private $linkGenerator;

    private $baseUrl;


    /** @var User[] */
    private $users;


    public function __construct(
        UserManager $userManager,
        TopicSubscriptionManager $topicSubscriptionManager,
        Parsedown $parsedown,
        Client $httpClient,
        LinkGenerator $linkGenerator,
        string $baseUrl
    ) {
        $this->userManager = $userManager;
        $this->topicSubscriptionManager = $topicSubscriptionManager;
        $this->parsedown = $parsedown;
        $this->httpClient = $httpClient;
        $this->linkGenerator = $linkGenerator;
        $this->baseUrl = $baseUrl;

        $this->parsedown->setMentionProcessor([$this, 'processMention']);
        $this->parsedown->setLinkProcessor([$this, 'processLink']);
        $this->parsedown->setImageProcessor([$this, 'processImage']);
    }


    public function processPostContent(Topic $topic, string $content) : string {
        $mentions = $this->extractPossibleMentions($content);
        $this->users = $mentions ? $this->userManager->getByLogins($mentions) : [];
        $content = $this->parsedown->text($content);

        $mentioned = array_diff_key($this->users, array_flip($this->parsedown->getParsedMentions()));
        $subscribe = array_filter($mentioned, function(User $user) : bool {
            return $user->getAccount()->isSubscribeMentions();
        });

        if ($subscribe) {
            $this->topicSubscriptionManager->subscribeMentionedUsers($topic, $subscribe);
        }

        $this->users = null;
        return $content;
    }


    public function processMention(string $mention) : ?array {
        if (!isset($this->users[$mention])) {
            return null;
        }

        $url = $this->linkGenerator->link('Public:Profile:default', ['user' => $this->users[$mention]]);
        $url = preg_replace('~^https?://[^/]+~i', '', $url);

        return [
            'href' => $url,
            'class' => 'profile-link',
            'title' => $this->users[$mention]->getName(),
        ];
    }

    public function processLink(array $link) : ?array {
        if (isset($link['element']['attributes']['href'])) {
            $url = $link['element']['attributes']['href'];

            if (preg_match('~^(javascript|data):~i', $url, $m)) {
                throw new InvalidUrlException("'{$m[0]}' links aren't allowed");
            }
        }

        return $link;
    }

    public function processImage(array $image) : ?array {
        if (isset($image['element']['attributes']['src'])) {
            $url = $image['element']['attributes']['src'];

            if (preg_match('~^(javascript|data):~i', $url, $m)) {
                throw new InvalidUrlException("'{$m[0]}' images aren't allowed");
            }

            $requests = 1;
            $currentUrl = $url;
            $previousUrl = null;

            do {
                try {
                    $response = $this->httpClient->head($currentUrl, [
                        'base_uri' => $previousUrl ?? $this->baseUrl,
                        'allow_redirects' => false,
                    ]);

                    if ($response->getStatusCode() >= 300 && $response->getStatusCode() <= 399) {
                        if ($redirectUrl = $response->getHeaderLine('Location')) {
                            $previousUrl = $currentUrl;
                            $currentUrl = $redirectUrl;
                        } else {
                            throw InvalidUrlException::other($url, 'Invalid image - %s cannot be loaded');
                        }
                    } else if (!preg_match('~^image/(?:jpeg|png|gif|webp|tiff|svg\+xml|x-icon)$~i', $response->getHeaderLine('Content-Type'))) {
                        throw InvalidUrlException::other($url, 'Invalid image - %s is not an image');
                    } else {
                        $image['element']['attributes']['src'] = $currentUrl;
                        return $image;
                    }
                } catch (ClientException $e) {
                    throw InvalidUrlException::e4xx($url, 'image');
                } catch (ServerException $e) {
                    throw InvalidUrlException::e5xx($url, 'image');
                } catch (RequestException $e) {
                    throw InvalidUrlException::other($url, 'Invalid image - %s cannot be loaded');
                }
            } while (++$requests < 4);

            throw InvalidUrlException::tooManyRedirects($url, 'image');
        }

        return $image;
    }


    private function extractPossibleMentions(string $text) : array {
        preg_match_all('~(?<!\S)@([a-z0-9]+(?:[._]+[a-z0-9]+)*)~i', $text, $m);
        return array_unique($m[1]);
    }

}

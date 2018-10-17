<?php

declare(strict_types=1);

namespace App\AdminModule\Commands;

use App\Entity\Category;
use App\Entity\Mail;
use App\Entity\Notification;
use App\Entity\Post;
use App\Entity\Topic;
use App\Entity\User;
use App\Mail\Builder;
use App\Mail\Mailer;
use App\ORM\Manager\MailManager;
use App\ORM\Manager\NotificationManager;
use Kdyby\Doctrine\EntityManager;
use Nette\Application\LinkGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class DispatchNotificationsCommand extends Command {

    private const NOTIFICATION_TYPES = [
        'instant' => Notification::INSTANT,
        'weekly' => Notification::WEEKLY,
        'daily' => Notification::DAILY,
    ];

    private $notificationManager;

    private $mailManager;

    private $em;

    private $mailer;

    private $linkGenerator;


    public function __construct(
        NotificationManager $notificationManager,
        MailManager $mailManager,
        EntityManager $em,
        Mailer $mailer,
        LinkGenerator $linkGenerator
    ) {
        parent::__construct();
        $this->notificationManager = $notificationManager;
        $this->mailManager = $mailManager;
        $this->em = $em;
        $this->mailer = $mailer;
        $this->linkGenerator = $linkGenerator;
    }


    protected function configure() : void {
        $this->setName('notifications:dispatch')
            ->addArgument('type', InputArgument::REQUIRED, 'instant|daily|weekly');
    }


    protected function execute(InputInterface $input, OutputInterface $output) : int {
        try {
            $level = $this->normalizeLevel($input->getArgument('type'));
        } catch (\InvalidArgumentException $e) {
            $output->writeln($e->getMessage());
            return 1;
        }

        foreach ($this->notificationManager->getNotifiableUsers($level) as $user) {
            $this->dispatchNotifications($user, $level);
        }

        return 0;
    }


    private function dispatchNotifications(User $user, int $level) : void {
        $posts = $this->notificationManager->dispatchNotifications($user, $level);

        if ($level > Notification::INSTANT) {
            $builder = $this->mailer->createBuilder($user, 'notification.aggregated');
            $mail = $builder->buildMail();
            $this->mailManager->persistAndFlush($mail);

            $builder->setParam('posts', $posts);
            $builder->setHeader('Message-ID', sprintf('<n%d@forum.nittro.org>', $mail->getId()));
            $builder->setHeader('Precedence', 'list');
            $builder->setHeader('X-Auto-Response-Suppress', 'All');
            $this->mailer->send($builder->buildMessage());
        } else {
            foreach ($posts as $post) {
                $builder = $this->mailer->createBuilder($user, 'notification.instant');
                $builder->setContext($post->getTopic(), $post);
                $builder->useToken();
                $mail = $builder->buildMail();
                $this->mailManager->persistAndFlush($mail);

                $unsubscribe = $this->linkGenerator->link('Public:Mail:unsubscribe', [
                    'msgid' => $mail->getId(),
                    'token' => $builder->getToken(),
                ]);

                $builder->setParam('unsubscribe', $unsubscribe);
                $builder->setHeader('Message-ID', sprintf('<n%d.%d.%d@forum.nittro.org>', $mail->getId(), $post->getId(), $post->getTopic()->getId()));
                $builder->setHeader('List-ID', sprintf('<t%d@forum.nittro.org>', $post->getTopic()->getId()));
                $builder->setHeader('List-Archive', sprintf('<%s>', $this->linkGenerator->link('Public:Topic:default', ['topic' => $post->topic])));
                $builder->setHeader('List-Unsubscribe', sprintf('<%s>', $unsubscribe));
                $builder->setHeader('Precedence', 'list');
                $builder->setHeader('X-Auto-Response-Suppress', 'All');
                $this->mailer->send($builder->buildMessage());
            }
        }

        $this->em->clear(Category::class);
        $this->em->clear(Topic::class);
        $this->em->clear(Post::class);
        $this->em->clear(Notification::class);
        $this->em->clear(Mail::class);
    }


    private function normalizeLevel(string $type) : int {
        if (!isset(self::NOTIFICATION_TYPES[$type])) {
            throw new \InvalidArgumentException(sprintf('Unknown notification type: "%s"', $type));
        }

        return self::NOTIFICATION_TYPES[$type];
    }

}

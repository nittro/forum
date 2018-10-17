<?php

declare(strict_types=1);

namespace App\AdminModule\Commands;

use App\Entity\Notification;
use App\Entity\User;
use App\Mail\Mailer;
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

    private $em;

    private $mailer;

    private $linkGenerator;


    public function __construct(
        NotificationManager $notificationManager,
        EntityManager $em,
        Mailer $mailer,
        LinkGenerator $linkGenerator
    ) {
        parent::__construct();
        $this->notificationManager = $notificationManager;
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
            $this->mailer->send($user, 'notification.aggregated', [
                'posts' => $posts,
                'user' => $user,
            ]);
        } else {
            foreach ($posts as $post) {
                $this->mailer->send($user, 'notification.instant', [
                    'post' => $post,
                    'user' => $user,
                ], [
                    'List-Archive' => sprintf('<%s>', $this->linkGenerator->link('Public:Topic:default', ['topic' => $post->topic])),
                ]);
            }
        }

        $this->em->clear();
    }


    private function normalizeLevel(string $type) : int {
        if (!isset(self::NOTIFICATION_TYPES[$type])) {
            throw new \InvalidArgumentException(sprintf('Unknown notification type: "%s"', $type));
        }

        return self::NOTIFICATION_TYPES[$type];
    }

}
